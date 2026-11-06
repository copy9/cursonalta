<?php
// Liberação manual de acesso pelo admin: define o plano e o curso de uma conta de aluno.
// Só o admin usa. A conta precisa já existir (o aluno se cadastra primeiro).

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
if (!is_array($body)) out(400, ['ok' => false, 'error' => 'Dados inválidos.']);

$email = isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';
$plan  = isset($body['plan']) ? (string)$body['plan'] : '';
$course = isset($body['course']) ? (string)$body['course'] : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'Informe um e-mail válido.']);
if ($plan !== 'curso' && $plan !== 'todos') out(422, ['ok' => false, 'error' => 'Escolha o plano.']);

$validCourses = [
    'policia-civil-pi-agente', 'marketing-digital-do-zero', 'excel-para-produtividade',
    'design-com-ia', 'ingles-para-negocios', 'financas-pessoais', 'programacao-web-iniciante',
];
if ($plan === 'curso') {
    if (!in_array($course, $validCourses, true)) out(422, ['ok' => false, 'error' => 'Escolha o curso liberado.']);
} else {
    $course = null; // plano de todos os cursos não tem um curso específico
}

$dataDir  = dirname(__DIR__) . '/app-data';
$dataFile = $dataDir . '/users.json';
if (!is_dir($dataDir)) @mkdir($dataDir, 0750, true);
$ht = $dataDir . '/.htaccess';
if (!file_exists($ht)) file_put_contents($ht, "Require all denied\nDeny from all\n");

$fp = fopen($dataFile, 'c+');
if (!$fp) out(500, ['ok' => false, 'error' => 'Não foi possível acessar o cadastro.']);
flock($fp, LOCK_EX);
$users = json_decode(stream_get_contents($fp), true);
if (!is_array($users)) $users = [];

if (!isset($users[$email])) {
    flock($fp, LOCK_UN);
    fclose($fp);
    out(404, ['ok' => false, 'error' => 'Esta conta não existe. A pessoa precisa se cadastrar primeiro, com este e-mail.']);
}

$users[$email]['plan'] = $plan;
$users[$email]['course'] = $course;
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

out(200, ['ok' => true, 'email' => $email, 'plan' => $plan, 'course' => $course]);
