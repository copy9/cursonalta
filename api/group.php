<?php
// Entrega o link do grupo do Telegram de um curso só para o aluno logado que tem acesso a ele.
// GET ?course=ID

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib.php';

function out($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params([
    'lifetime' => 2592000,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['user'])) out(401, ['ok' => false, 'error' => 'Entre na sua conta.']);

$user = $_SESSION['user'];
$courseId = isset($_GET['course']) ? (string)$_GET['course'] : '';

// Confere o acesso no cadastro atual (não só na sessão)
$dataFile = dirname(__DIR__) . '/app-data/users.json';
$record = null;
if (file_exists($dataFile)) {
    $users = json_decode(file_get_contents($dataFile), true);
    if (is_array($users) && isset($users[$user['email']])) $record = $users[$user['email']];
}
if (!$record) out(403, ['ok' => false, 'error' => 'Acesso não encontrado.']);

$plan = isset($record['plan']) ? $record['plan'] : 'curso';
$allowed = ($plan === 'todos') || in_array($courseId, user_courses($record), true);
if (!$allowed) out(403, ['ok' => false, 'error' => 'Seu plano não inclui este curso.']);

// Plano de todos os cursos: convite de uso único para o grupo geral, válido por 1 dia
if ($plan === 'todos' && (int)($record['plan_expires_at'] ?? 0) > time()) {
    $grupo = (string)(settings_read()['tg_group_todos'] ?? '');
    if ($grupo === '') out(503, ['ok' => false, 'error' => 'O grupo ainda não está configurado. Tente mais tarde.']);
    [$code, $res] = tg_request('createChatInviteLink', [
        'chat_id'      => $grupo,
        'member_limit' => 1,
        'expire_date'  => time() + 86400,
        'name'         => user_tag($user['email']),
    ]);
    $url = is_array($res) && isset($res['result']['invite_link']) ? $res['result']['invite_link'] : '';
    if ($code !== 200 || $url === '') out(502, ['ok' => false, 'error' => 'Não foi possível gerar seu convite. Tente de novo.']);
    out(200, ['ok' => true, 'url' => $url]);
}

$coursesFile = dirname(__DIR__) . '/app-data/courses.json';
$list = file_exists($coursesFile) ? json_decode(file_get_contents($coursesFile), true) : null;
$link = '';
if (is_array($list)) {
    foreach ($list as $c) {
        if (isset($c['id']) && $c['id'] === $courseId && !empty($c['telegram'])) $link = $c['telegram'];
    }
}
if ($link === '') out(404, ['ok' => false, 'error' => 'Este curso ainda não tem grupo cadastrado.']);

out(200, ['ok' => true, 'url' => $link]);
