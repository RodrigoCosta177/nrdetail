<?php
require_once('includes/mail_helper.php');

$pdfTeste = __DIR__ . '/notas_encomenda/teste.pdf';

if (!file_exists($pdfTeste)) {
    file_put_contents($pdfTeste, 'teste');
}

$resultado = enviarEmailEncomenda(
    'papnrdetail@gmail.com',
    'Rodrigo',
    $pdfTeste,
    999,
    49.99
);

echo '<pre>';
var_dump($resultado);
echo '</pre>';