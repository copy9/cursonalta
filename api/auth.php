<?php
// Cadastro, login, logout e sessão da Cursonalta.
// Usuários ficam em app-data/users.json, numa pasta bloqueada para acesso pela web.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function out($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/config.php';

$dataDir  = dirname(__DIR__) . '/app-data';
$dataFile = $dataDir . '/users.json';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'me') {
    if (!empty($_SESSION['user'])) {
        $u = $_SESSION['user'];
        $u['role'] = is_admin($u) ? 'admin' : 'aluno';
        out(200, ['ok' => true, 'user' => $u]);
    }
    out(401, ['ok' => false, 'error' => 'Não logado.']);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    out(200, ['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

// Só aceita JSON: formulários de outros sites não conseguem enviar este tipo.
$ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($ctype, 'application/json') === false) out(415, ['ok' => false, 'error' => 'Requisição inválida.']);

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) out(400, ['ok' => false, 'error' => 'Dados inválidos.']);

function ensure_data_dir($dir) {
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $ht = $dir . '/.htaccess';
    if (!file_exists($ht)) file_put_contents($ht, "Require all denied\nDeny from all\n");
}

// Lê, altera e grava os usuários dentro de um bloqueio de arquivo, para evitar dois cadastros ao mesmo tempo.
function with_users($dir, $file, callable $fn) {
    ensure_data_dir($dir);
    $fp = fopen($file, 'c+');
    if (!$fp) out(500, ['ok' => false, 'error' => 'Não foi possível acessar o cadastro.']);
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $users = json_decode($raw, true);
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

if ($action === 'register') {
    $name     = isset($body['name']) ? trim((string)$body['name']) : '';
    $email    = isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';
    $password = isset($body['password']) ? (string)$body['password'] : '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) out(422, ['ok' => false, 'error' => 'Informe seu nome completo.']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) out(422, ['ok' => false, 'error' => 'Informe um e-mail válido.']);
    if (strlen($password) < 8 || strlen($password) > 200) out(422, ['ok' => false, 'error' => 'A senha precisa ter no mínimo 8 caracteres.']);

    $user = ['name' => $name, 'email' => $email, 'course' => null, 'plan' => 'curso'];
    $exists = with_users($dataDir, $dataFile, function (&$users) use ($email, $name, $password, &$user) {
        if (isset($users[$email])) return true;
        $users[$email] = [
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created'  => date('c'),
        ];
        return false;
    });
    if ($exists) out(409, ['ok' => false, 'error' => 'Já existe uma conta com este e-mail. Entre na sua conta.']);

    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    out(201, ['ok' => true, 'user' => $user]);
}

if ($action === 'login') {
    $email    = isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';
    $password = isset($body['password']) ? (string)$body['password'] : '';

    $found = null;
    with_users($dataDir, $dataFile, function ($users) use ($email, &$found) {
        if (isset($users[$email])) $found = $users[$email];
        return null;
    });

    // Mesmo tempo de resposta com e-mail inexistente, para não revelar quais contas existem.
    $hash = $found ? $found['password'] : password_hash('dummy-password-for-timing', PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash);
    if (!$found || !$ok) out(401, ['ok' => false, 'error' => 'E-mail ou senha incorretos.']);

    $user = [
        'name'   => $found['name'],
        'email'  => $found['email'],
        'course' => isset($found['course']) ? $found['course'] : null,
        'plan'   => isset($found['plan']) ? $found['plan'] : 'curso',
    ];
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    out(200, ['ok' => true, 'user' => $user]);
}

// Escolha do curso do plano (um curso). Só aceita ids da lista abaixo.
if ($action === 'course') {
    if (empty($_SESSION['user'])) out(401, ['ok' => false, 'error' => 'Entre na sua conta.']);
    $course = isset($body['course']) ? (string)$body['course'] : '';
    $valid = [
        'policia-civil-pi-agente', 'marketing-digital-do-zero', 'excel-para-produtividade',
        'design-com-ia', 'ingles-para-negocios', 'financas-pessoais', 'programacao-web-iniciante',
    ];
    if (!in_array($course, $valid, true)) out(422, ['ok' => false, 'error' => 'Curso inválido.']);
    // A escolha é única: trocar de curso acontece com a mudança de plano, não aqui.
    if (!empty($_SESSION['user']['course'])) out(409, ['ok' => false, 'error' => 'Seu curso já foi escolhido.']);
    if (isset($_SESSION['user']['plan']) && $_SESSION['user']['plan'] === 'todos') out(409, ['ok' => false, 'error' => 'Seu plano já dá acesso a todos os cursos.']);

    $email = $_SESSION['user']['email'];
    with_users($dataDir, $dataFile, function (&$users) use ($email, $course) {
        if (isset($users[$email])) $users[$email]['course'] = $course;
        return null;
    });
    $_SESSION['user']['course'] = $course;
    out(200, ['ok' => true, 'user' => $_SESSION['user']]);
}

out(400, ['ok' => false, 'error' => 'Ação inválida.']);
