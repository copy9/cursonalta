<?php
// Envio de capa de curso pelo painel do admin.
// Só o admin envia. Aceita JPG, PNG ou WebP até 4 MB e guarda em uploads/covers/ com nome aleatório.

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

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out(403, ['ok' => false, 'error' => 'Acesso restrito.']);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    out(422, ['ok' => false, 'error' => 'Não recebi o arquivo. Tente outra imagem.']);
}

$file = $_FILES['file'];
if ($file['size'] > 4 * 1024 * 1024) out(422, ['ok' => false, 'error' => 'A imagem passa de 4 MB. Reduza e tente de novo.']);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$ext = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
if (!isset($ext[$mime])) out(422, ['ok' => false, 'error' => 'Formato não aceito. Use JPG, PNG ou WebP.']);

$info = @getimagesize($file['tmp_name']);
if (!$info) out(422, ['ok' => false, 'error' => 'O arquivo não parece uma imagem válida.']);

$dir = dirname(__DIR__) . '/uploads/covers';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
// Impede que a pasta execute scripts, mesmo que algo indesejado seja enviado
$ht = dirname(__DIR__) . '/uploads/.htaccess';
if (!file_exists($ht)) file_put_contents($ht, "php_flag engine off\nRemoveHandler .php .phtml .php3 .php5\n");

$name = bin2hex(random_bytes(10)) . '.' . $ext[$mime];
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) out(500, ['ok' => false, 'error' => 'Não foi possível salvar a imagem. Tente de novo.']);

out(200, ['ok' => true, 'path' => 'uploads/covers/' . $name]);
