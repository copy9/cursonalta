<?php
// Rotina diária: tira do grupo de todos os cursos quem teve o acesso vencido.
// Chamada pelo cron do Hostinger: curl "https://cursonalta.com/api/expire.php?token=SEU_TOKEN_DE_CRON"

require_once __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$settings = settings_read();
$token = $settings['cron_token'] ?? '';
$pedido = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($token === '' || !hash_equals($token, $pedido)) out(401, ['ok' => false]);

$grupo = (string)($settings['tg_group_todos'] ?? '');
$now = time();
$removidos = 0;
$semId = 0;
$falhas = 0;

$vencidos = [];
foreach (users_read() as $email => $u) {
    if (($u['plan'] ?? '') === 'todos' && (int)($u['plan_expires_at'] ?? 0) < $now) {
        $vencidos[$email] = $u;
    }
}

foreach ($vencidos as $email => $u) {
    $tgId = $u['telegram_id'] ?? null;
    if ($tgId && $grupo !== '') {
        // Ban remove do grupo; unban permite voltar depois, se comprar de novo
        [$c1] = tg_request('banChatMember', ['chat_id' => $grupo, 'user_id' => (int)$tgId, 'revoke_messages' => false]);
        tg_request('unbanChatMember', ['chat_id' => $grupo, 'user_id' => (int)$tgId, 'only_if_banned' => true]);
        if ($c1 === 200) $removidos++; else $falhas++;
    } else {
        $semId++;
    }

    users_modify(function (&$users) use ($email) {
        if (!isset($users[$email])) return;
        $users[$email]['plan'] = 'none';
        $users[$email]['plan_expires_at'] = 0;
    });
}

out(200, [
    'ok' => true,
    'vencidos' => count($vencidos),
    'removidos' => $removidos,
    'sem_id_telegram' => $semId,
    'falhas' => $falhas,
]);
