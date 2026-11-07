<?php
// Chaves de integração (Mercado Pago e Telegram). Só o admin usa.
// GET: diz quais chaves já estão salvas (nunca devolve os valores).
// POST JSON: salva as chaves enviadas.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib.php';

const ALLOWED_KEYS = ['mp_access_token', 'mp_webhook_secret', 'tg_bot_token', 'tg_group_todos', 'cron_token', 'ai_api_key'];

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out(403, ['ok' => false, 'error' => 'Acesso restrito ao admin.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = settings_read();
    $status = [];
    foreach (ALLOWED_KEYS as $k) $status[$k] = !empty($s[$k]);
    $status['tg_webhook_secret'] = !empty($s['tg_webhook_secret']);
    out(200, ['ok' => true, 'saved' => $status]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

$ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($ctype, 'application/json') === false) out(415, ['ok' => false, 'error' => 'Requisição inválida.']);
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) out(400, ['ok' => false, 'error' => 'Dados inválidos.']);

$changes = [];
foreach (ALLOWED_KEYS as $k) {
    if (!array_key_exists($k, $body)) continue;
    $v = trim((string)$body[$k]);
    if ($v === '') continue; // campo vazio mantém o valor atual
    if (strlen($v) > 300) out(422, ['ok' => false, 'error' => 'Um dos campos está grande demais.']);
    $changes[$k] = $v;
}
if (!$changes) out(422, ['ok' => false, 'error' => 'Nenhum campo preenchido.']);

settings_write($changes);
out(200, ['ok' => true, 'saved' => array_keys($changes)]);
