<?php
require('fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Teste PDF NR Detail',0,1,'C');
$pdf->Output();
