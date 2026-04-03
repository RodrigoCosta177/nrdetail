<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailEncomenda($emailCliente, $nomeCliente, $pdfPath, $encomendaId, $total)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'papnrdetail@gmail.com';
        $mail->Password = 'jvkwobxogswvkivf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('papnrdetail@gmail.com', 'NR DETAIL');
        $mail->addAddress($emailCliente, $nomeCliente);

        if (!empty($pdfPath) && file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        $mail->isHTML(true);
        $mail->Subject = 'Encomenda #' . $encomendaId . ' - NR DETAIL';
        $mail->Body = '
            <h2>Obrigado pela tua compra, ' . htmlspecialchars($nomeCliente) . '!</h2>
            <p>A tua encomenda foi registada com sucesso.</p>
            <p><strong>Número da encomenda:</strong> #' . $encomendaId . '</p>
            <p><strong>Total:</strong> ' . number_format($total, 2, ',', '.') . '€</p>
            <p>Segue em anexo o PDF com os detalhes da encomenda.</p>
            <br>
            <p>Cumprimentos,<br><strong>NR Detail</strong></p>
        ';

        $mail->AltBody = 'Obrigado pela tua compra, ' . $nomeCliente . '! A tua encomenda #' . $encomendaId . ' foi registada com sucesso. Total: ' . number_format($total, 2, ',', '.') . ' EUR.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL: ' . $mail->ErrorInfo;
    }
}