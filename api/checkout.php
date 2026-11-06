<?php
// Inicia a compra no Mercado Pago e manda o aluno para a tela de pagamento.
// GET ?plano=curso&curso=ID  -> pagamento único (Checkout Pro, Pix ou cartão)
// GET ?plano=todos           -> assinatura mensal de todos os cursos

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

$plano = isset($_GET['plano']) ? (string)$_GET['plano'] : '';
$curso = isset($_GET['curso']) ? (string)$_GET['curso'] : '';

if ($plano !== 'curso' && $plano !== 'todos') out(422, ['ok' => false, 'error' => 'Escolha o plano.']);

if (empty($_SESSION['user'])) {
    header('Location: /cadastro.html?plano=' . $plano . ($curso !== '' ? '&curso=' . urlencode($curso) : ''));
    exit;
}

$email = strtolower($_SESSION['user']['email']);
$users = users_read();
if (!isset($users[$email])) out(403, ['ok' => false, 'error' => 'Conta não encontrada.']);
$rec = $users[$email];

// Não vende de novo para quem já tem o acesso ativo
$now = time();
$hasAll = ($rec['plan'] ?? '') === 'todos' && ($rec['plan_expires_at'] ?? 0) > $now;
$hasOne = ($rec['plan'] ?? '') === 'curso' && !empty($rec['course']);
if ($hasAll || ($plano === 'curso' && $hasOne)) {
    header('Location: /minha-area.html');
    exit;
}

if ($plano === 'todos') {
    [$code, $res] = mp_request('POST', '/preapproval', [
        'reason'            => 'Cursonalta: todos os cursos',
        'external_reference' => 'T|' . $email,
        'payer_email'       => $email,
        'auto_recurring'    => [
            'frequency'          => 1,
            'frequency_type'     => 'months',
            'transaction_amount' => PRICE_TODOS,
            'currency_id'        => 'BRL',
        ],
        'back_url'          => 'https://cursonalta.com/minha-area.html',
        'status'            => 'pending',
    ]);
} else {
    $cursos = [];
    $file = app_dir() . '/courses.json';
    if (file_exists($file)) $cursos = json_decode(file_get_contents($file), true) ?: [];
    $titulo = null;
    foreach ($cursos as $c) {
        if (isset($c['id']) && $c['id'] === $curso) $titulo = $c['title'] ?? $curso;
    }
    if ($titulo === null) out(422, ['ok' => false, 'error' => 'Este curso não está disponível.']);

    [$code, $res] = mp_request('POST', '/checkout/preferences', [
        'items' => [[
            'title'      => 'Curso: ' . $titulo,
            'quantity'   => 1,
            'unit_price' => PRICE_CURSO,
            'currency_id' => 'BRL',
        ]],
        'payer'              => ['email' => $email],
        'external_reference' => 'C|' . $email . '|' . $curso,
        'back_urls'          => [
            'success' => 'https://cursonalta.com/minha-area.html',
            'pending' => 'https://cursonalta.com/minha-area.html',
            'failure' => 'https://cursonalta.com/curso.html?id=' . urlencode($curso),
        ],
        'auto_return'        => 'approved',
    ]);
}

$url = is_array($res) ? ($res['init_point'] ?? null) : null;
if ($code < 200 || $code >= 300 || !$url) {
    error_log('Mercado Pago checkout falhou: HTTP ' . $code);
    header('Location: /minha-area.html?erro=pagamento');
    exit;
}

header('Location: ' . $url);
exit;
