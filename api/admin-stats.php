<?php
// Dados do painel do admin: alunos, cursos, vendas e receita.
// Só o admin acessa. Vendas e receita vêm direto da API do Mercado Pago.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib.php';

function out_json($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (!is_admin(isset($_SESSION['user']) ? $_SESSION['user'] : null)) out_json(403, ['ok' => false, 'error' => 'Sem acesso.']);

date_default_timezone_set('America/Sao_Paulo');
$now = time();

// Alunos (contas criadas pelo cadastro, sem o admin)
$users = users_read();
$students = [];
$kpi = ['students' => 0, 'todos_active' => 0, 'curso_buyers' => 0, 'no_plan' => 0];
foreach ($users as $email => $u) {
    if (!is_array($u) || $email === ADMIN_EMAIL || !isset($u['password'])) continue;
    $kpi['students']++;
    $plan = $u['plan'] ?? 'none';
    $expires = (int)($u['plan_expires_at'] ?? 0);
    $owned = user_courses($u);
    if ($plan === 'todos' && $expires > $now) $kpi['todos_active']++;
    elseif (count($owned) > 0) $kpi['curso_buyers']++;
    else $kpi['no_plan']++;
    $students[] = [
        'name' => $u['name'] ?? '',
        'email' => $email,
        'plan' => ($plan === 'todos' && $expires > $now) ? 'todos' : (count($owned) ? 'curso' : 'none'),
        'expires' => $plan === 'todos' ? date('d/m/Y', $expires) : null,
        'courses' => count($owned),
        'created' => $u['created'] ?? null,
    ];
}
usort($students, function ($a, $b) { return strcmp((string)$b['created'], (string)$a['created']); });
$students = array_slice($students, 0, 300);

// Cursos
$courses = [];
$file = app_dir() . '/courses.json';
if (file_exists($file)) $courses = json_decode(file_get_contents($file), true) ?: [];
$titles = [];
$courseRows = [];
foreach ($courses as $c) {
    if (!isset($c['id'])) continue;
    $titles[$c['id']] = $c['title'] ?? $c['id'];
    $owners = 0;
    foreach ($users as $u) {
        if (is_array($u) && in_array($c['id'], user_courses($u), true)) $owners++;
    }
    $courseRows[] = [
        'id' => $c['id'],
        'title' => $c['title'] ?? $c['id'],
        'category' => $c['category'] ?? '',
        'price' => (float)($c['price'] ?? PRICE_CURSO),
        'modules' => is_array($c['modules'] ?? null) ? count($c['modules']) : 0,
        'lessons' => (int)($c['lessons'] ?? 0),
        'students' => $owners,
        'sales' => 0,
    ];
}

// Vendas no Mercado Pago (até 1000 pagamentos mais recentes)
$sales = ['ok' => false, 'error' => null];
$payments = [];
$begin = '2024-01-01T00:00:00.000-03:00';
$end = date('Y-m-d\TH:i:s.000P', $now + 86400);
$token = settings_read()['mp_access_token'] ?? '';
if ($token === '') {
    $sales['error'] = 'Mercado Pago ainda não configurado em Integrações.';
} else {
    for ($page = 0; $page < 10; $page++) {
        $path = '/v1/payments/search?sort=date_created&criteria=desc&limit=100&offset=' . ($page * 100)
            . '&range=date_created&begin_date=' . rawurlencode($begin) . '&end_date=' . rawurlencode($end);
        [$code, $res] = mp_request('GET', $path);
        if ($code !== 200 || !is_array($res)) { $sales['error'] = 'Não foi possível consultar as vendas agora.'; break; }
        $results = $res['results'] ?? [];
        $payments = array_merge($payments, $results);
        $sales['ok'] = true;
        if (count($results) < 100) break;
    }
}

$approved = 0; $approvedSum = 0.0; $refunds = 0;
$rev30 = 0.0; $count30 = 0; $months = [];
$recent = [];
for ($i = 5; $i >= 0; $i--) { $months[date('Y-m', strtotime("first day of -$i month"))] = ['m' => date('Y-m', strtotime("first day of -$i month")), 'amount' => 0.0, 'count' => 0]; }
$coursesById = [];
foreach ($courseRows as $r) $coursesById[$r['id']] = $r;

foreach ($payments as $p) {
    $status = $p['status'] ?? '';
    $amount = (float)($p['transaction_amount'] ?? 0);
    $ref = (string)($p['external_reference'] ?? '');
    $parts = explode('|', $ref, 3);
    $email = count($parts) >= 2 ? $parts[1] : (($p['payer']['email'] ?? '') ?: '');
    $cursoId = (count($parts) === 3 && $parts[0] === 'C') ? $parts[2] : null;
    $type = $cursoId ? 'Curso avulso' : ((count($parts) >= 1 && $parts[0] === 'T') ? 'Assinatura' : 'Outro');
    $when = isset($p['date_created']) ? strtotime($p['date_created']) : 0;
    // Só conta pagamentos feitos pela plataforma (referência C| ou T|); outros pagamentos da conta Mercado Pago são ignorados
    if (!in_array($parts[0] ?? '', ['C', 'T'], true)) continue;

    if (in_array($status, ['refunded', 'charged_back'], true)) $refunds++;
    if ($status === 'approved') {
        $approved++;
        $approvedSum += $amount;
        if ($when >= $now - 30 * 86400) { $rev30 += $amount; $count30++; }
        $mk = date('Y-m', $when);
        if (isset($months[$mk])) { $months[$mk]['amount'] += $amount; $months[$mk]['count']++; }
        if ($cursoId && isset($coursesById[$cursoId])) $coursesById[$cursoId]['sales']++;
    }
    if (count($recent) < 25) {
        $recent[] = [
            'date' => $when ? date('d/m/Y H:i', $when) : '',
            'email' => $email,
            'type' => $type,
            'item' => $cursoId ? ($titles[$cursoId] ?? $cursoId) : ($type === 'Assinatura' ? 'Todos os cursos' : ''),
            'amount' => $amount,
            'status' => $status,
        ];
    }
}

out_json(200, [
    'ok' => true,
    'generatedAt' => date('d/m/Y H:i'),
    'kpis' => [
        'students' => $kpi['students'],
        'todos_active' => $kpi['todos_active'],
        'curso_buyers' => $kpi['curso_buyers'],
        'no_plan' => $kpi['no_plan'],
        'courses' => count($courseRows),
        'sales_count' => $approved,
        'revenue_total' => round($approvedSum, 2),
        'revenue_30d' => round($rev30, 2),
        'sales_30d' => $count30,
        'refunds' => $refunds,
    ],
    'months' => array_values($months),
    'courses' => array_values($coursesById),
    'students' => $students,
    'recent' => $recent,
    'sales' => $sales,
]);
