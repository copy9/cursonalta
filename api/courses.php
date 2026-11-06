<?php
// Lista de cursos da Cursonalta.
// GET: qualquer visitante lê a lista. Se ainda não existir lista salva, devolve courses = null
//      e o site usa a lista padrão de js/courses.js.
// POST: só o admin grava a lista inteira (app-data/courses.json).

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/config.php';

function out($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$dataDir  = dirname(__DIR__) . '/app-data';
$dataFile = $dataDir . '/courses.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $list = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : null;
    out(200, ['ok' => true, 'courses' => is_array($list) ? $list : null]);
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

$ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($ctype, 'application/json') === false) out(415, ['ok' => false, 'error' => 'Requisição inválida.']);

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || !isset($body['courses']) || !is_array($body['courses'])) out(400, ['ok' => false, 'error' => 'Dados inválidos.']);

function text($v, $max, $field, $min = 0) {
    $s = trim((string)$v);
    if (mb_strlen($s) < $min || mb_strlen($s) > $max) out(422, ['ok' => false, 'error' => "Campo inválido: $field."]);
    return $s;
}

function int_in($v, $min, $max, $field) {
    if (!is_numeric($v) || (int)$v < $min || (int)$v > $max) out(422, ['ok' => false, 'error' => "Número inválido: $field."]);
    return (int)$v;
}

// Capa: caminho de imagem da pasta assets/covers (do repositório) ou enviada pelo painel (uploads/covers)
function cover_path($v) {
    $s = trim((string)$v);
    if ($s === '') return '';
    if (!preg_match('#^(assets/covers|uploads/covers)/[A-Za-z0-9._-]+\.(png|jpe?g|webp)$#i', $s)) {
        out(422, ['ok' => false, 'error' => 'Capa inválida. Envie a imagem pelo painel.']);
    }
    return $s;
}

// Link do grupo do Telegram: só endereços t.me ou telegram.me
function telegram_link($v) {
    $s = trim((string)$v);
    if ($s === '') return '';
    if (!preg_match('#^https://(t\.me|telegram\.me)/[A-Za-z0-9_+/-]{3,}$#', $s)) {
        out(422, ['ok' => false, 'error' => 'Link do Telegram inválido. Use um endereço como https://t.me/seugrupo.']);
    }
    return $s;
}

$clean = [];
$ids = [];
foreach ($body['courses'] as $c) {
    if (!is_array($c)) out(422, ['ok' => false, 'error' => 'Curso inválido.']);

    $id = text(isset($c['id']) ? $c['id'] : '', 60, 'identificador (id)', 3);
    if (!preg_match('/^[a-z0-9-]+$/', $id)) out(422, ['ok' => false, 'error' => "Identificador \"$id\" aceita só letras minúsculas, números e hífen."]);
    if (isset($ids[$id])) out(422, ['ok' => false, 'error' => "Identificador repetido: $id."]);
    $ids[$id] = true;

    $price = isset($c['price']) ? (float)$c['price'] : -1;
    if ($price < 0 || $price > 999) out(422, ['ok' => false, 'error' => "Preço inválido em $id."]);

    $learn = [];
    if (isset($c['learn']) && is_array($c['learn'])) {
        foreach (array_slice($c['learn'], 0, 20) as $l) {
            $t = trim((string)$l);
            if ($t !== '') $learn[] = mb_substr($t, 0, 200);
        }
    }

    $modules = [];
    if (isset($c['modules']) && is_array($c['modules'])) {
        foreach (array_slice($c['modules'], 0, 60) as $m) {
            if (!is_array($m)) continue;
            $modules[] = [
                'title'   => text(isset($m['title']) ? $m['title'] : '', 160, 'título do módulo', 1),
                'lessons' => int_in(isset($m['lessons']) ? $m['lessons'] : 0, 0, 999, 'aulas do módulo'),
                'time'    => text(isset($m['time']) ? $m['time'] : '', 40, 'duração do módulo'),
            ];
        }
    }

    $ins = isset($c['instructor']) && is_array($c['instructor']) ? $c['instructor'] : [];
    $clean[] = [
        'id'               => $id,
        'title'            => text(isset($c['title']) ? $c['title'] : '', 120, 'título', 3),
        'shortDescription' => text(isset($c['shortDescription']) ? $c['shortDescription'] : '', 240, 'descrição curta', 3),
        'description'      => text(isset($c['description']) ? $c['description'] : '', 2000, 'descrição', 3),
        'category'         => text(isset($c['category']) ? $c['category'] : '', 40, 'categoria', 2),
        'level'            => text(isset($c['level']) ? $c['level'] : '', 40, 'nível', 2),
        'instructor'       => [
            'name'     => text(isset($ins['name']) ? $ins['name'] : '', 80, 'nome do professor'),
            'role'     => text(isset($ins['role']) ? $ins['role'] : '', 80, 'cargo do professor'),
            'initials' => text(isset($ins['initials']) ? $ins['initials'] : '', 3, 'iniciais'),
        ],
        'price'            => round($price, 2),
        'duration'         => text(isset($c['duration']) ? $c['duration'] : '', 40, 'duração'),
        'lessons'          => int_in(isset($c['lessons']) ? $c['lessons'] : 0, 0, 999, 'aulas'),
        'cover'            => cover_path(isset($c['cover']) ? $c['cover'] : ''),
        'telegram'         => telegram_link(isset($c['telegram']) ? $c['telegram'] : ''),
        'gradient'         => 'linear-gradient(135deg, #141414, #2a2a2a)',
        'learn'            => $learn,
        'modules'          => $modules,
    ];
}

if (count($clean) === 0) out(422, ['ok' => false, 'error' => 'Salve pelo menos um curso.']);

if (!is_dir($dataDir)) @mkdir($dataDir, 0750, true);
$ht = $dataDir . '/.htaccess';
if (!file_exists($ht)) file_put_contents($ht, "Require all denied\nDeny from all\n");

if (file_put_contents($dataFile, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
    out(500, ['ok' => false, 'error' => 'Não foi possível salvar. Tente novamente.']);
}

out(200, ['ok' => true, 'courses' => $clean]);
