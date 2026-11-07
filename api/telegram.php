<?php
// Recebe os avisos do Telegram sobre o grupo de todos os cursos.
// Quando um aluno entra com o convite gerado para ele, guardamos o ID do Telegram dele,
// para que a remoção, quando a assinatura vencer, saiba quem tirar do grupo.

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false]);

$settings = settings_read();
$secret = $settings['tg_webhook_secret'] ?? '';
$recebido = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
if ($secret === '' || !hash_equals($secret, $recebido)) out(401, ['ok' => false]);

$update = json_decode(file_get_contents('php://input'), true);
if (!is_array($update) || !isset($update['chat_member'])) out(200, ['ok' => true]);

$cm = $update['chat_member'];
$chatId = (string)($cm['chat']['id'] ?? '');
$grupo = (string)($settings['tg_group_todos'] ?? '');
if ($grupo === '' || $chatId !== $grupo) out(200, ['ok' => true]);

$status = $cm['new_chat_member']['status'] ?? '';
$tgId = $cm['new_chat_member']['user']['id'] ?? null;
$nome = $cm['invite_link']['name'] ?? '';
if (!in_array($status, ['member', 'restricted', 'administrator', 'creator'], true) || !$tgId || $nome === '') {
    out(200, ['ok' => true]);
}

// Acha o aluno pelo nome do convite (que é derivado do e-mail)
users_modify(function (&$users) use ($nome, $tgId) {
    foreach ($users as $email => &$u) {
        if (user_tag($email) === $nome) {
            $u['telegram_id'] = (int)$tgId;
            if (empty($u['tg_entrou_em'])) $u['tg_entrou_em'] = time();
            break;
        }
    }
});
out(200, ['ok' => true]);
