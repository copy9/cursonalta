<?php
// Exclusão de conta de aluno pelo admin. Só o admin usa. Não apaga contas de admin.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';

function out($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params([
    'lifetime' => 2592000,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out(403, ['ok' => false, 'error' => 'Sua sessão expirou. Recarregue a página e entre de novo.']);

$ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($ctype, 'application/json') === false) out(415, ['ok' => false, 'error' => 'Requisição inválida.']);

$body = json_decode(file_get_contents('php://input'), true);
$email = is_array($body) && isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'E-mail inválido.']);

$achou = false;
$admin = false;
users_modify(function (&$users) use ($email, &$achou, &$admin) {
    if (!isset($users[$email])) return;
    $achou = true;
    if (is_admin($users[$email])) { $admin = true; return; }
    unset($users[$email]);
});

if (!$achou) out(404, ['ok' => false, 'error' => 'Aluno não encontrado.']);
if ($admin) out(403, ['ok' => false, 'error' => 'Contas de administrador não podem ser excluídas por aqui.']);
out(200, ['ok' => true]);
