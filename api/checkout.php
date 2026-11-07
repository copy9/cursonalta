<?php
// Inicia a compra no Mercado Pago e manda o aluno para a tela de pagamento.
// GET ?plano=curso&curso=ID  -> pagamento único (Checkout Pro, Pix ou cartão)
// GET ?plano=todos           -> assinatura mensal de todos os cursos

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') out(405, ['ok' => false, 'error' => 'Método não permitido.']);

ini_set('session.gc_maxlifetime', '2592000');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

$plano = isset($_GET['plano']) ? (string)$_GET['plano'] : '';
$curso = isset($_GET['curso']) ? (string)$_GET['curso'] : '';

if ($plano !== 'curso' && $plano !== 'todos') out(422, ['ok' => false, 'error' => 'Escolha o plano.']);

if (empty($_SESSION['user'])) {
    // Depois de criar a conta ou entrar, o aluno volta direto para esta compra
    $volta = 'api/checkout.php?plano=' . $plano . ($curso !== '' ? '&curso=' . urlencode($curso) : '');
    header('Location: /cadastro.html?next=' . urlencode($volta));
    exit;
}

$email = strtolower($_SESSION['user']['email']);
$users = users_read();
if (!isset($users[$email])) out(403, ['ok' => false, 'error' => 'Conta não encontrada.']);
$rec = $users[$email];

// Não vende de novo para quem já tem o acesso ativo
$now = time();
$hasAll = ($rec['plan'] ?? '') === 'todos' && ($rec['plan_expires_at'] ?? 0) > $now;
$hasOne = $plano === 'curso' && in_array($curso, user_courses($rec), true);
if ($hasAll || $hasOne) {
    header('Location: /minha-area.html');
    exit;
}

if ($plano === 'todos') {
    [$code, $res] = mp_request('POST', '/preapproval', [
        'reason'            => 'Cursonalta - todos os cursos',
        'external_reference' => 'T~' . $email,
        'payer_email'       => $email,
        'auto_recurring'    => [
            'frequency'          => 1,
            'frequency_type'     => 'months',
            // Começa agora (o Mercado Pago exige data de início; 5 minutos à frente evita rejeição por horário)
            'start_date'         => (new DateTime('@' . (time() + 300)))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d\TH:i:s.000P'),
            'transaction_amount' => PRICE_TODOS,
            'currency_id'        => 'BRL',
        ],
        'back_url'          => 'https://cursonalta.com/minha-area.html',
    ]);
} else {
    $cursos = [];
    $file = app_dir() . '/courses.json';
    if (file_exists($file)) $cursos = json_decode(file_get_contents($file), true) ?: [];
    $titulo = null;
    $preco = PRICE_CURSO;
    foreach ($cursos as $c) {
        if (isset($c['id']) && $c['id'] === $curso) {
            $titulo = $c['title'] ?? $curso;
            if (isset($c['price']) && (float)$c['price'] > 0) $preco = round((float)$c['price'], 2);
        }
    }
    if ($titulo === null) out(422, ['ok' => false, 'error' => 'Este curso não está disponível.']);

    // Order bump: mesmo pagamento libera também todos os cursos por 1 mês
    $items = [[
        'id'          => 'curso-' . preg_replace('/[^A-Za-z0-9_-]/', '', $curso),
        'title'       => mp_text('Curso: ' . $titulo),
        'quantity'    => 1,
        'unit_price'  => $preco,
        'currency_id' => 'BRL',
    ]];
    $bump = isset($_GET['bump']) && $_GET['bump'] === '1';
    if ($bump) {
        $items[] = [
            'id'          => 'bump-todos',
            'title'       => 'Acesso a todos os cursos por 1 mês',
            'quantity'    => 1,
            'unit_price'  => (float)PRICE_BUMP,
            'currency_id' => 'BRL',
        ];
    }
    [$code, $res] = mp_request('POST', '/checkout/preferences', [
        'items'    => $items,
        'metadata' => ['bump' => $bump ? 'todos' : ''],
        'payer'              => ['email' => $email],
        'external_reference' => 'C~' . $email . '~' . $curso,
        'back_urls'          => [
            'success' => 'https://cursonalta.com/minha-area.html',
            'pending' => 'https://cursonalta.com/minha-area.html',
            'failure' => 'https://cursonalta.com/curso.html?id=' . urlencode($curso),
        ],
        'auto_return'        => 'approved',
    ]);
}

$url = is_array($res) ? ($res['init_point'] ?? null) : null;
if ($code < 200 || $code >= 300 || !$url) {
    error_log('Mercado Pago checkout (' . $plano . ') falhou: HTTP ' . $code . ' ' . json_encode($res));
    // Mostra ao aluno o motivo que o Mercado Pago informou (texto curto, sem dados sensíveis)
    $motivo = '';
    if (is_array($res)) {
        $motivo = (string)($res['message'] ?? '');
        $causas = [];
        foreach (($res['cause'] ?? []) as $c) { if (!empty($c['description'])) $causas[] = (string)$c['description']; elseif (!empty($c['code'])) $causas[] = 'código ' . $c['code']; }
        if ($causas) $motivo .= ($motivo !== '' ? ' - ' : '') . implode('; ', $causas);
    }
    $motivo = mb_substr(trim($motivo), 0, 160);
    header('Location: /minha-area.html?erro=pagamento&motivo=' . urlencode($motivo));
    exit;
}

if ($plano === 'curso') remember_pending_purchase($email, 'C~' . $email . '~' . $curso);

header('Location: ' . $url);
exit;
