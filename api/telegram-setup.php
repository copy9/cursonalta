<?php
// Liga o bot ao site: diz ao Telegram para avisar o endereço api/telegram.php.
// Só o admin usa, uma vez (e de novo se trocar o token do bot).

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib.php';

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out(403, ['ok' => false, 'error' => 'Acesso restrito ao admin.']);

$settings = settings_read();
if (empty($settings['tg_bot_token'])) out(422, ['ok' => false, 'error' => 'Salve o token do bot primeiro.']);

$secret = $settings['tg_webhook_secret'] ?? '';
if ($secret === '') {
    $secret = bin2hex(random_bytes(24));
    settings_write(['tg_webhook_secret' => $secret]);
}

[$code, $res] = tg_request('setWebhook', [
    'url'             => 'https://cursonalta.com/api/telegram.php',
    'secret_token'    => $secret,
    'allowed_updates' => ['chat_member'],
]);

out($code === 200 ? 200 : 502, [
    'ok' => $code === 200,
    'telegram' => is_array($res) ? ($res['description'] ?? ($res['ok'] ?? null)) : null,
]);
