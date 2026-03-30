<?php
require_once('config/db.php');
require_once(__DIR__ . '/fpdf/fpdf.php');

function gerarNotaEncomenda($conn, $encomenda_id) {
    $encomenda_id = (int)$encomenda_id;

    if ($encomenda_id <= 0) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT e.id, e.total, e.data_hora, e.estado, u.nome, u.email
        FROM encomendas e
        JOIN users u ON e.user_id = u.id
        WHERE e.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $encomenda_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $encomenda = $result->fetch_assoc();
    $stmt->close();

    $stmtProdutos = $conn->prepare("
        SELECT p.nome, c.quantidade, p.preco
        FROM carrinho c
        JOIN produtos p ON c.produto_id = p.id
        WHERE c.encomenda_id = ?
    ");
    $stmtProdutos->bind_param("i", $encomenda_id);
    $stmtProdutos->execute();
    $resultProdutos = $stmtProdutos->get_result();

    $produtos = [];
    while ($row = $resultProdutos->fetch_assoc()) {
        $produtos[] = $row;
    }

    $stmtProdutos->close();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, utf8_decode('Nota de Encomenda'), 0, 1, 'C');

    $pdf->Ln(4);

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'NR Detail', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, utf8_decode('Loja e serviços automóveis'), 0, 1);

    $pdf->Ln(6);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('Dados da encomenda'), 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, 'N. encomenda: ' . $encomenda['id'], 0, 1);
    $pdf->Cell(0, 7, 'Cliente: ' . utf8_decode($encomenda['nome']), 0, 1);
    $pdf->Cell(0, 7, 'Email: ' . $encomenda['email'], 0, 1);
    $pdf->Cell(0, 7, 'Data: ' . $encomenda['data_hora'], 0, 1);
    $pdf->Cell(0, 7, 'Estado: ' . utf8_decode($encomenda['estado']), 0, 1);

    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(255, 204, 0);

    $pdf->Cell(85, 10, 'Produto', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Qtd.', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Preco unit.', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Subtotal', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);

    foreach ($produtos as $produto) {
        $nome = utf8_decode($produto['nome']);
        $quantidade = (int)$produto['quantidade'];
        $preco = (float)$produto['preco'];
        $subtotal = $quantidade * $preco;

        $pdf->Cell(85, 10, $nome, 1, 0);
        $pdf->Cell(25, 10, $quantidade, 1, 0, 'C');
        $pdf->Cell(40, 10, number_format($preco, 2, ',', '.') . ' EUR', 1, 0, 'C');
        $pdf->Cell(40, 10, number_format($subtotal, 2, ',', '.') . ' EUR', 1, 1, 'C');
    }

    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Total: ' . number_format((float)$encomenda['total'], 2, ',', '.') . ' EUR', 0, 1, 'R');

    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, utf8_decode('Documento gerado automaticamente pelo sistema da NR Detail.'));

    $pastaPDF = __DIR__ . '/notas_encomenda';

    if (!is_dir($pastaPDF)) {
        mkdir($pastaPDF, 0777, true);
    }

    $nomeFicheiro = 'nota_encomenda_' . $encomenda_id . '.pdf';
    $caminhoCompleto = $pastaPDF . '/' . $nomeFicheiro;

    $pdf->Output('F', $caminhoCompleto);

    return $caminhoCompleto;
}