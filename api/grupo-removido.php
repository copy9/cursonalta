<?php
// Admin confirma que tirou o aluno do grupo do Telegram: encerra o plano dele.
// Só o admin usa.

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out(403, ['ok' => false, 'error' => 'Sua sessão expirou. Recarregue a página e entre de novo.']);

$ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($ctype, 'application/json') === false) out(415, ['ok' => false, 'error' => 'Requisição inválida.']);

$body = json_decode(file_get_contents('php://input'), true);
$email = is_array($body) && isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'E-mail inválido.']);

$achou = false;
users_modify(function (&$users) use ($email, &$achou) {
    if (!isset($users[$email]) || ($users[$email]['plan'] ?? '') !== 'todos') return;
    $achou = true;
    $users[$email]['plan'] = 'none';
    $users[$email]['plan_expires_at'] = 0;
    $users[$email]['grupo_removido_em'] = time();
});

if (!$achou) out(404, ['ok' => false, 'error' => 'Aluno com pacote de todos os cursos não encontrado.']);
out(200, ['ok' => true]);
