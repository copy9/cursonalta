<?php
// Recebe os avisos do Mercado Pago (pagamentos, assinaturas e cobranças recorrentes).
// Confere a assinatura do aviso, consulta o recurso na API e atualiza o acesso do aluno.
// Configure este endereço no painel do Mercado Pago: https://cursonalta.com/api/mp-webhook.php

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false]);

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = [];

$type = isset($body['type']) ? (string)$body['type'] : (isset($_GET['type']) ? (string)$_GET['type'] : '');
$dataId = isset($_GET['data_id']) ? (string)$_GET['data_id']
    : (isset($body['data']['id']) ? (string)$body['data']['id'] : '');
if ($dataId === '' && isset($_GET['data.id'])) $dataId = (string)$_GET['data.id'];

// Não confiamos no conteúdo do aviso: o status e a referência do pagamento são
// conferidos direto na API do Mercado Pago, com o token da conta.

if ($dataId === '') out(200, ['ok' => true, 'ignored' => true]);

function parse_ref($ref) {
    // "C~email~curso" = compra avulsa; "T~email" = assinatura de todos os cursos
    $p = ref_parts($ref);
    if ($p[0] === 'C' && count($p) === 3) return ['tipo' => 'curso', 'email' => strtolower($p[1]), 'curso' => $p[2]];
    if ($p[0] === 'T' && count($p) === 2) return ['tipo' => 'todos', 'email' => strtolower($p[1]), 'curso' => null];
    return null;
}

// Aplica uma mudança de acesso a um aluno, guardando o id do aviso para não repetir
function grant($email, $fn, $avisoId) {
    return users_modify(function (&$users) use ($email, $fn, $avisoId) {
        if (!isset($users[$email])) return false;
        $seen = $users[$email]['mp_seen'] ?? [];
        if (in_array($avisoId, $seen, true)) return true;
        $fn($users[$email]);
        $seen[] = $avisoId;
        $users[$email]['mp_seen'] = array_slice($seen, -50);
        return true;
    });
}

$now = time();

if ($type === 'payment') {
    [$code, $p] = mp_request('GET', '/v1/payments/' . rawurlencode($dataId));
    if ($code !== 200 || !is_array($p)) out(200, ['ok' => true, 'skipped' => 'consulta falhou']);
    $ref = parse_ref($p['external_reference'] ?? '');
    if (!$ref || $ref['tipo'] !== 'curso') out(200, ['ok' => true, 'skipped' => 'referência']);
    $status = $p['status'] ?? '';
    $email = $ref['email'];
    $curso = $ref['curso'];

    $bump = payment_has_bump($p);
    if ($status === 'approved') {
        grant($email, function (&$u) use ($curso, $bump) {
            if (($u['plan'] ?? '') !== 'todos') $u['plan'] = 'curso';
            $list = user_courses($u);
            if (!in_array($curso, $list, true)) $list[] = $curso;
            $u['courses'] = $list;
            $u['course'] = $list[0];
            if ($bump) give_todos($u);
        }, 'pay-' . $dataId);
    } elseif (in_array($status, ['refunded', 'charged_back', 'cancelled'], true)) {
        grant($email, function (&$u) use ($curso, $bump) {
            $list = array_values(array_filter(user_courses($u), function ($c) use ($curso) { return $c !== $curso; }));
            $u['courses'] = $list;
            $u['course'] = $list[0] ?? null;
            if (!$list && ($u['plan'] ?? '') === 'curso') $u['plan'] = 'none';
            if ($bump && ($u['plan'] ?? '') === 'todos') { $u['plan'] = 'none'; $u['plan_expires_at'] = 0; }
        }, 'pay-' . $dataId . '-' . $status);
    }
    out(200, ['ok' => true]);
}

if ($type === 'subscription_preapproval') {
    [$code, $s] = mp_request('GET', '/preapproval/' . rawurlencode($dataId));
    if ($code !== 200 || !is_array($s)) out(200, ['ok' => true, 'skipped' => 'consulta falhou']);
    $ref = parse_ref($s['external_reference'] ?? '');
    if (!$ref || $ref['tipo'] !== 'todos') out(200, ['ok' => true, 'skipped' => 'referência']);
    $status = $s['status'] ?? '';
    $email = $ref['email'];

    users_modify(function (&$users) use ($email, $status, $dataId, $now) {
        if (!isset($users[$email])) return;
        $users[$email]['mp_preapproval_id'] = $dataId;
        $users[$email]['mp_preapproval_status'] = $status;
        // Cancelada ou pausada: o acesso segue até o fim do período já pago (plan_expires_at não muda)
        if ($status === 'authorized') {
            $users[$email]['plan'] = 'todos';
            if (($users[$email]['plan_expires_at'] ?? 0) < $now) {
                $users[$email]['plan_expires_at'] = $now + DAYS_TODOS * 86400;
            }
        }
    });
    out(200, ['ok' => true]);
}

if ($type === 'subscription_authorized_payment') {
    [$code, $ap] = mp_request('GET', '/authorized_payments/' . rawurlencode($dataId));
    if ($code !== 200 || !is_array($ap)) out(200, ['ok' => true, 'skipped' => 'consulta falhou']);
    if (($ap['status'] ?? '') !== 'approved') out(200, ['ok' => true]);

    [$c2, $s] = mp_request('GET', '/preapproval/' . rawurlencode((string)($ap['preapproval_id'] ?? '')));
    if ($c2 !== 200 || !is_array($s)) out(200, ['ok' => true, 'skipped' => 'assinatura não encontrada']);
    $ref = parse_ref($s['external_reference'] ?? '');
    if (!$ref || $ref['tipo'] !== 'todos') out(200, ['ok' => true, 'skipped' => 'referência']);
    $email = $ref['email'];

    // Cada cobrança aprovada estende o acesso por um mês a partir do vencimento atual (ou de hoje)
    grant($email, function (&$u) use ($now) {
        $u['plan'] = 'todos';
        $base = max($now, (int)($u['plan_expires_at'] ?? 0));
        $u['plan_expires_at'] = $base + DAYS_TODOS * 86400;
    }, 'ap-' . $dataId);
    out(200, ['ok' => true]);
}

out(200, ['ok' => true, 'ignored' => $type]);
