<?php
// Rotina diária (cron das 03h): só conta quem venceu o pacote de todos os cursos.
// A remoção do grupo do Telegram é manual: o admin vê a lista na aba Grupo do painel
// e marca "Já removi do grupo". Nada é removido ou alterado aqui.
// Chamada pelo cron do Hostinger: curl "https://cursonalta.com/api/expire.php?token=SEU_TOKEN_DE_CRON"

require_once __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$settings = settings_read();
$token = $settings['cron_token'] ?? '';
$pedido = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($token === '' || !hash_equals($token, $pedido)) out(401, ['ok' => false]);

$now = time();
$vencidos = 0;
foreach (users_read() as $email => $u) {
    if (($u['plan'] ?? '') === 'todos' && (int)($u['plan_expires_at'] ?? 0) > 0 && (int)($u['plan_expires_at'] ?? 0) < $now) {
        $vencidos++;
    }
}

out(200, ['ok' => true, 'vencidos_aguardando_remocao' => $vencidos]);
