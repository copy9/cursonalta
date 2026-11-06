<?php
// Funções compartilhadas: cadastro, configurações secretas, Mercado Pago e Telegram.
// As chaves ficam em app-data/settings.json (fora do Git e bloqueado para a web).

require_once __DIR__ . '/config.php';

const PRICE_CURSO = 14.99;   // um curso, pagamento único, acesso até concluir
const PRICE_TODOS = 24.99;   // todos os cursos, assinatura mensal
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
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$code, $json];
}

function mp_request($method, $path, $body = null) {
    $token = settings_read()['mp_access_token'] ?? '';
    if ($token === '') return [503, null];
    return http_call($method, 'https://api.mercadopago.com' . $path, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ], $body);
}

function tg_request($method, array $body) {
    $token = settings_read()['tg_bot_token'] ?? '';
    if ($token === '') return [503, null];
    return http_call('POST', 'https://api.telegram.org/bot' . $token . '/' . $method, [
        'Content-Type: application/json',
    ], $body);
}
