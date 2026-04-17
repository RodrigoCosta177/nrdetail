<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

header('Content-Type: application/json');

$data = $_GET['data'] ?? '';

if (empty($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Data inválida.'
    ]);
    exit;
}

/* Bloquear datas passadas */
if ($data < date('Y-m-d')) {
    echo json_encode([
        'status' => 'ok',
        'horarios' => []
    ]);
    exit;
}

/* Bloquear fim de semana */
$diaSemana = date('N', strtotime($data));
if ($diaSemana >= 6) {
    echo json_encode([
        'status' => 'ok',
        'horarios' => []
    ]);
    exit;
}

/* Bloquear feriados */
$stmtFeriado = $conn->prepare("SELECT id FROM feriados WHERE data = ? LIMIT 1");
$stmtFeriado->bind_param("s", $data);
$stmtFeriado->execute();
$feriado = $stmtFeriado->get_result()->fetch_assoc();
$stmtFeriado->close();

if ($feriado) {
    echo json_encode([
        'status' => 'ok',
        'horarios' => []
    ]);
    exit;
}

/* Buscar disponibilidade */
$stmt = $conn->prepare("
    SELECT d.hora, d.vagas,
           (
               SELECT COUNT(*)
               FROM marcacoes m
               WHERE m.data_marcacao = d.data AND m.hora = d.hora
           ) AS ocupadas
    FROM disponibilidade d
    WHERE d.data = ? AND d.ativo = 1
    ORDER BY d.hora ASC
");
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();

$horariosDisponiveis = [];

while ($row = $res->fetch_assoc()) {
    $vagas = (int)$row['vagas'];
    $ocupadas = (int)$row['ocupadas'];

    if ($ocupadas < $vagas) {
        $horariosDisponiveis[] = substr($row['hora'], 0, 5);
    }
}

$stmt->close();

echo json_encode([
    'status' => 'ok',
    'horarios' => $horariosDisponiveis
]);