<?php
// Funções compartilhadas: cadastro, configurações secretas, Mercado Pago e Telegram.
// As chaves ficam em app-data/settings.json (fora do Git e bloqueado para a web).

require_once __DIR__ . '/config.php';

const PRICE_CURSO = 14.99;   // um curso, pagamento único, acesso até concluir
const PRICE_TODOS = 0.50;   // TESTE: valor temporário de R$ 0,50 para compra de teste (voltar para 24.99)   // todos os cursos, assinatura mensal
const PRICE_BUMP  = 10.00;    // order bump: acesso a todos os cursos por 1 mês, junto com um curso avulso
const DAYS_TODOS  = 32;      // um mês + margem de atraso antes de remover do grupo

if (!function_exists('out')) {
    function out($code, $data) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function app_dir() {
    return dirname(__DIR__) . '/app-data';
}

function ensure_app_dir() {
    $d = app_dir();
    if (!is_dir($d)) @mkdir($d, 0750, true);
    $ht = $d . '/.htaccess';
    if (!file_exists($ht)) file_put_contents($ht, "Require all denied\nDeny from all\n");
}

function settings_read() {
    $f = app_dir() . '/settings.json';
    if (!file_exists($f)) return [];
    $s = json_decode(file_get_contents($f), true);
    return is_array($s) ? $s : [];
}

function settings_write(array $changes) {
    ensure_app_dir();
    $fp = fopen(app_dir() . '/settings.json', 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    $s = json_decode(stream_get_contents($fp), true);
    if (!is_array($s)) $s = [];
    foreach ($changes as $k => $v) $s[$k] = $v;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function users_read() {
    $f = app_dir() . '/users.json';
    if (!file_exists($f)) return [];
    $u = json_decode(file_get_contents($f), true);
    return is_array($u) ? $u : [];
}

// Altera os usuários dentro de um bloqueio de arquivo. $fn recebe a lista por referência.
function users_modify(callable $fn) {
    ensure_app_dir();
    $fp = fopen(app_dir() . '/users.json', 'c+');
    if (!$fp) return null;
    flock($fp, LOCK_EX);
    $users = json_decode(stream_get_contents($fp), true);
    if (!is_array($users)) $users = [];
    $result = $fn($users);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

// Cursos avulsos que o aluno tem (lista nova + campo antigo "course")
function user_courses($rec) {
    $list = (isset($rec['courses']) && is_array($rec['courses'])) ? $rec['courses'] : [];
    if (!empty($rec['course']) && !in_array($rec['course'], $list, true)) $list[] = $rec['course'];
    return array_values(array_unique(array_filter($list)));
}

// Nome curto e estável para o convite do Telegram (máximo 32 caracteres)
function user_tag($email) {
    return 'u' . substr(sha1(strtolower(trim($email))), 0, 20);
}

function http_call($method, $url, array $headers = [], $body = null) {
    if (!function_exists('curl_init')) return [0, null];
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ];
    // JSON limpo: sem barras escapadas (\/) nem acentos em \uXXXX, que o firewall do Mercado Pago pode recusar
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    // Guarda a última chamada (sem cabeçalhos, que têm o token) para diagnóstico
    $GLOBALS['last_http'] = ['url' => $url, 'sent' => $opts[CURLOPT_POSTFIELDS] ?? null, 'code' => $code, 'raw' => is_string($raw) ? mb_substr($raw, 0, 2000) : null, 'curl_error' => $err];
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$code, $json];
}

function mp_request($method, $path, $body = null) {
    $token = settings_read()['mp_access_token'] ?? '';
    if ($token === '') return [503, null];
    return http_call($method, 'https://api.mercadopago.com' . $path, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Cursonalta/1.0 (PHP)',
        'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
    ], $body);
}

// Referência do pedido: "C~email~curso" ou "T~email".
// Compras antigas usavam "|", que o firewall do Mercado Pago pode bloquear; as duas formas são aceitas.
function ref_parts($ref) {
    return preg_split('/[|~]/', (string)$ref, 3);
}

// Grava o último erro do Mercado Pago em app-data (bloqueado para a web), para o admin conferir.
// Do token só guarda o formato: tamanho, começo e se tem espaço ou quebra de linha.
function mp_log_error($contexto) {
    $t = (string)(settings_read()['mp_access_token'] ?? '');
    ensure_app_dir();
    @file_put_contents(app_dir() . '/mp-last-error.json', json_encode([
        'at'       => date('c'),
        'contexto' => $contexto,
        'call'     => $GLOBALS['last_http'] ?? null,
        'token'    => ['len' => strlen($t), 'inicio' => substr($t, 0, 8), 'tem_espaco' => (bool)preg_match('/\s/', $t), 'so_ascii' => (bool)preg_match('/^[\x21-\x7E]*$/', $t)],
        'php'      => PHP_VERSION,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

// Texto seguro para enviar ao Mercado Pago (títulos e descrições):
// tira HTML, emojis e símbolos que o firewall deles costuma bloquear, mantém letras com acento.
function mp_text($s, $max = 120) {
    $s = html_entity_decode(strip_tags((string)$s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N} .,:()\-]+/u', ' ', $s);
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    return mb_substr($s, 0, $max);
}

function tg_request($method, array $body) {
    $token = settings_read()['tg_bot_token'] ?? '';
    if ($token === '') return [503, null];
    return http_call('POST', 'https://api.telegram.org/bot' . $token . '/' . $method, [
        'Content-Type: application/json',
    ], $body);
}

// Compras avulsas em andamento: guarda a referência antes de ir ao Mercado Pago.
// Assim a área do aluno confere o pagamento direto na API, sem depender do aviso (webhook).
function remember_pending_purchase($email, $ref) {
    users_modify(function (&$users) use ($email, $ref) {
        if (!isset($users[$email])) return false;
        $list = $users[$email]['pending'] ?? [];
        $now = time();
        // Descarta compras antigas (mais de 1 dia) para não consultar à toa
        $list = array_values(array_filter($list, function ($p) use ($now) { return ($p['at'] ?? 0) > $now - 86400 && ($p['ref'] ?? '') !== ''; }));
        $list[] = ['ref' => $ref, 'at' => $now];
        $users[$email]['pending'] = array_slice($list, -10);
        return true;
    });
}

// Confere no Mercado Pago as compras avulsas pendentes do aluno e libera as aprovadas.
function sync_pending_purchases($email) {
    $users = users_read();
    $list = $users[$email]['pending'] ?? [];
    if (!$list) return;
    $now = time();
    $done = [];
    foreach ($list as $p) {
        $ref = (string)($p['ref'] ?? '');
        if ($ref === '' || ($p['at'] ?? 0) <= $now - 86400) { $done[] = $ref; continue; }
        $parts = ref_parts($ref);
        $ehMensal = count($parts) === 2 && $parts[0] === 'M';
        if (!$ehMensal && (count($parts) !== 3 || $parts[0] !== 'C')) { $done[] = $ref; continue; }
        $curso = $ehMensal ? null : $parts[2];
        [$code, $res] = mp_request('GET', '/v1/payments/search?sort=date_created&criteria=desc&limit=5&external_reference=' . rawurlencode($ref));
        if ($code !== 200 || !is_array($res)) continue;
        foreach (($res['results'] ?? []) as $pay) {
            if (($pay['external_reference'] ?? '') !== $ref) continue;
            $status = $pay['status'] ?? '';
            if ($status === 'approved') {
                $avisoId = 'pay-' . (string)($pay['id'] ?? '');
                $bump = payment_has_bump($pay);
                users_modify(function (&$users) use ($email, $curso, $avisoId, $bump, $ehMensal) {
                    if (!isset($users[$email])) return;
                    $seen = $users[$email]['mp_seen'] ?? [];
                    if (in_array($avisoId, $seen, true)) return;
                    if ($ehMensal) {
                        give_todos($users[$email]);
                    } else {
                        if (($users[$email]['plan'] ?? '') !== 'todos') $users[$email]['plan'] = 'curso';
                        $list2 = user_courses($users[$email]);
                        if (!in_array($curso, $list2, true)) $list2[] = $curso;
                        $users[$email]['courses'] = $list2;
                        $users[$email]['course'] = $list2[0];
                        if ($bump) give_todos($users[$email]);
                    }
                    $seen[] = $avisoId;
                    $users[$email]['mp_seen'] = array_slice($seen, -50);
                });
                $done[] = $ref;
                break;
            }
            if (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) { $done[] = $ref; break; }
        }
    }
    if ($done) {
        users_modify(function (&$users) use ($email, $done) {
            if (!isset($users[$email])) return;
            $users[$email]['pending'] = array_values(array_filter($users[$email]['pending'] ?? [], function ($p) use ($done) {
                return !in_array($p['ref'] ?? '', $done, true);
            }));
        });
    }
}

// Order bump: o aluno marcou "todos os cursos por 1 mês" junto com o curso avulso (mesmo pagamento)
function payment_has_bump($pay) {
    if (($pay['metadata']['bump'] ?? '') === 'todos') return true;
    foreach (($pay['additional_info']['items'] ?? []) as $it) {
        if (($it['id'] ?? '') === 'bump-todos') return true;
    }
    return false;
}

// Libera o plano de todos os cursos por DAYS_TODOS a partir de hoje (ou soma ao que já existe)
function give_todos(&$u) {
    $now = time();
    $base = max($now, (int)($u['plan_expires_at'] ?? 0));
    $u['plan'] = 'todos';
    $u['plan_expires_at'] = $base + DAYS_TODOS * 86400;
}
