<?php

require_once('fpdf/fpdf.php');

function pdfText($text)
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$text);
}

function gerarNotaEncomenda($conn, $encomenda_id)
{
    /* =========================
       BUSCAR ENCOMENDA
    ========================= */
    $stmt = $conn->prepare("
        SELECT e.*, u.nome, u.email
        FROM encomendas e
        INNER JOIN users u ON e.user_id = u.id
        WHERE e.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $encomenda_id);
    $stmt->execute();
    $encomenda = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$encomenda) {
        return false;
    }

    /* =========================
       BUSCAR PRODUTOS
    ========================= */
    $stmtProdutos = $conn->prepare("
        SELECT p.nome, c.quantidade, p.preco
        FROM carrinho c
        INNER JOIN produtos p ON c.produto_id = p.id
        WHERE c.encomenda_id = ?
    ");
    $stmtProdutos->bind_param("i", $encomenda_id);
    $stmtProdutos->execute();
    $produtos = $stmtProdutos->get_result();

    /* =========================
       CONFIG PDF
    ========================= */
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);

    $corPreto = [20, 20, 20];
    $corCinza = [110, 110, 110];
    $corClaro = [245, 245, 245];
    $corDestaque = [255, 204, 0];

    $logoPath = __DIR__ . '/imagens/logo.png';

    /* =========================
       TOPO
    ========================= */
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 12, 10, 38);
    }

    $pdf->SetFont('Arial', 'B', 22);
    $pdf->SetTextColor($corPreto[0], $corPreto[1], $corPreto[2]);
    $pdf->SetXY(55, 12);
    $pdf->Cell(100, 10, pdfText('NR Detail'), 0, 1);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($corCinza[0], $corCinza[1], $corCinza[2]);
    $pdf->SetX(55);
    $pdf->Cell(100, 6, pdfText('Car & Care'), 0, 1);
    $pdf->SetX(55);
    $pdf->Cell(100, 6, pdfText('Email: papnrdetail@gmail.com'), 0, 1);
    $pdf->SetX(55);
    $pdf->Cell(100, 6, pdfText('Documento de Encomenda'), 0, 1);

    $pdf->SetDrawColor($corDestaque[0], $corDestaque[1], $corDestaque[2]);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(10, 42, 200, 42);

    /* =========================
       CAIXA INFO DOCUMENTO
    ========================= */
    $pdf->Ln(10);

    $pdf->SetFillColor($corClaro[0], $corClaro[1], $corClaro[2]);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect(10, 48, 90, 28, 'DF');
    $pdf->Rect(110, 48, 90, 28, 'DF');

    $pdf->SetXY(14, 52);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($corPreto[0], $corPreto[1], $corPreto[2]);
    $pdf->Cell(40, 6, pdfText('Cliente'), 0, 1);

    $pdf->SetX(14);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(80, 6, pdfText($encomenda['nome']), 0, 1);

    $pdf->SetX(14);
    $pdf->Cell(80, 6, pdfText($encomenda['email']), 0, 1);

    $pdf->SetXY(114, 52);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(80, 6, pdfText('Detalhes da Encomenda'), 0, 1);

    $pdf->SetX(114);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(80, 6, pdfText('N.º: #' . $encomenda['id']), 0, 1);

    $pdf->SetX(114);
    $pdf->Cell(80, 6, pdfText('Data: ' . $encomenda['data_hora']), 0, 1);

    $pdf->SetX(114);
    $pdf->Cell(80, 6, pdfText('Estado: ' . $encomenda['estado']), 0, 1);

    /* =========================
       TÍTULO TABELA
    ========================= */
    $pdf->Ln(18);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor($corPreto[0], $corPreto[1], $corPreto[2]);
    $pdf->Cell(0, 8, pdfText('Resumo dos Produtos'), 0, 1);

    /* =========================
       CABEÇALHO TABELA
    ========================= */
    $pdf->SetFillColor($corDestaque[0], $corDestaque[1], $corDestaque[2]);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);

    $pdf->Cell(85, 10, pdfText('Produto'), 1, 0, 'C', true);
    $pdf->Cell(25, 10, pdfText('Qtd'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, pdfText('Preço Unit.'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, pdfText('Subtotal'), 1, 1, 'C', true);

    /* =========================
       LINHAS PRODUTOS
    ========================= */
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($corPreto[0], $corPreto[1], $corPreto[2]);

    $total = 0;
    $fill = false;

    while ($p = $produtos->fetch_assoc()) {
        $subtotal = (float)$p['preco'] * (int)$p['quantidade'];
        $total += $subtotal;

        if ($fill) {
            $pdf->SetFillColor(250, 250, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $nomeProduto = pdfText($p['nome']);
        if (strlen($nomeProduto) > 42) {
            $nomeProduto = substr($nomeProduto, 0, 42) . '...';
        }

        $pdf->Cell(85, 10, $nomeProduto, 1, 0, 'L', true);
        $pdf->Cell(25, 10, (int)$p['quantidade'], 1, 0, 'C', true);
        $pdf->Cell(40, 10, number_format((float)$p['preco'], 2, ',', '.') . ' EUR', 1, 0, 'C', true);
        $pdf->Cell(40, 10, number_format($subtotal, 2, ',', '.') . ' EUR', 1, 1, 'C', true);

        $fill = !$fill;
    }

    $stmtProdutos->close();

    /* =========================
       TOTAL
    ========================= */
    $pdf->Ln(6);

    $pdf->SetX(110);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(35, 35, 35);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(50, 12, pdfText('Total Final'), 1, 0, 'C', true);

    $pdf->SetFillColor($corDestaque[0], $corDestaque[1], $corDestaque[2]);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(40, 12, number_format($total, 2, ',', '.') . ' EUR', 1, 1, 'C', true);

    /* =========================
       NOTA FINAL
    ========================= */
    $pdf->Ln(10);
    $pdf->SetTextColor($corCinza[0], $corCinza[1], $corCinza[2]);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->MultiCell(
        0,
        6,
        pdfText('Obrigado pela sua preferência. Este documento foi gerado automaticamente pelo sistema da NR Detail.')
    );

    /* =========================
       RODAPÉ VISUAL
    ========================= */
    $pdf->Ln(6);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(4);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor($corCinza[0], $corCinza[1], $corCinza[2]);
    $pdf->Cell(0, 5, pdfText('NR Detail - Documento interno de encomenda'), 0, 1, 'C');
    $pdf->Cell(0, 5, pdfText('Contacto: papnrdetail@gmail.com'), 0, 1, 'C');

    /* =========================
       GUARDAR PDF
    ========================= */
    $pasta = __DIR__ . '/notas_encomenda';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $ficheiro = $pasta . '/nota_encomenda_' . $encomenda_id . '.pdf';
    $pdf->Output('F', $ficheiro);

    return true;
}