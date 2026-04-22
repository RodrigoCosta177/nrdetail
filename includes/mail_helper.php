<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function configurarMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'papnrdetail@gmail.com';
    $mail->Password = 'jvkwobxogswvkivf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('papnrdetail@gmail.com', 'NR DETAIL');

    return $mail;
}

function enviarEmailEncomenda($emailCliente, $nomeCliente, $pdfPath, $encomendaId, $total)
{
    try {
        $mail = configurarMailer();

        $mail->addAddress($emailCliente, $nomeCliente);

        if (!empty($pdfPath) && file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        $mail->isHTML(true);
        $mail->Subject = 'Encomenda #' . $encomendaId . ' - NR DETAIL';
        $mail->Body = '
            <h2>Obrigado pela tua compra, ' . htmlspecialchars($nomeCliente) . '!</h2>
            <p>A tua encomenda foi registada com sucesso.</p>
            <p><strong>Número da encomenda:</strong> #' . (int)$encomendaId . '</p>
            <p><strong>Total:</strong> ' . number_format((float)$total, 2, ',', '.') . '€</p>
            <p>Segue em anexo o PDF com os detalhes da encomenda.</p>
            <br>
            <p>Cumprimentos,<br><strong>NR DETAIL</strong></p>
        ';

        $mail->AltBody =
            'Obrigado pela tua compra, ' . $nomeCliente .
            '! A tua encomenda #' . (int)$encomendaId .
            ' foi registada com sucesso. Total: ' .
            number_format((float)$total, 2, ',', '.') . ' EUR.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL: ' . $mail->ErrorInfo;
    }
}

function enviarEmailRecuperacaoPassword($emailCliente, $nomeCliente, $linkReset)
{
    try {
        $mail = configurarMailer();

        $mail->addAddress($emailCliente, $nomeCliente);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de Password - NR DETAIL';
        $mail->Body = '
            <h2>Recuperação de Password</h2>
            <p>Olá, ' . htmlspecialchars($nomeCliente) . '.</p>
            <p>Recebemos um pedido para redefinir a tua password.</p>
            <p>Clica no botão abaixo para continuar:</p>
            <p>
                <a href="' . htmlspecialchars($linkReset) . '" style="
                    display:inline-block;
                    background:#ffcc00;
                    color:#000;
                    text-decoration:none;
                    padding:12px 20px;
                    border-radius:8px;
                    font-weight:bold;
                ">
                    Redefinir Password
                </a>
            </p>
            <p>Este link é válido por 1 hora.</p>
            <p>Se não foste tu, podes ignorar este email.</p>
            <br>
            <p>Cumprimentos,<br><strong>NR DETAIL</strong></p>
        ';

        $mail->AltBody =
            'Olá, ' . $nomeCliente .
            '. Usa este link para redefinir a tua password: ' . $linkReset .
            '. Este link é válido por 1 hora.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL: ' . $mail->ErrorInfo;
    }
}

function getLabelEstadoEncomenda($estado)
{
    $labels = [
        'pendente' => 'Pendente',
        'processada' => 'Em processamento',
        'pronta_levantamento' => 'Pronta para levantamento em loja',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada'
    ];

    return $labels[$estado] ?? ucfirst($estado);
}

function getMensagemEstadoEncomenda($estado)
{
    switch ($estado) {
        case 'pendente':
            return 'A tua encomenda foi registada e está pendente de processamento.';
        case 'processada':
            return 'A tua encomenda está a ser preparada.';
        case 'pronta_levantamento':
            return 'A tua encomenda está pronta para levantamento em loja.';
        case 'concluida':
            return 'A tua encomenda foi concluída com sucesso.';
        case 'cancelada':
            return 'A tua encomenda foi cancelada.';
        default:
            return 'O estado da tua encomenda foi atualizado.';
    }
}

function enviarEmailAtualizacaoEstadoEncomenda($emailCliente, $nomeCliente, $encomendaId, $novoEstado)
{
    try {
        $mail = configurarMailer();

        $labelEstado = getLabelEstadoEncomenda($novoEstado);
        $mensagemEstado = getMensagemEstadoEncomenda($novoEstado);

        $mail->addAddress($emailCliente, $nomeCliente);
        $mail->isHTML(true);
        $mail->Subject = 'Atualização da encomenda #' . (int)$encomendaId . ' - NR DETAIL';

        $mail->Body = '
            <h2>Olá, ' . htmlspecialchars($nomeCliente, ENT_QUOTES, 'UTF-8') . '!</h2>
            <p>O estado da tua encomenda foi atualizado.</p>
            <p><strong>Encomenda:</strong> #' . (int)$encomendaId . '</p>
            <p><strong>Novo estado:</strong> ' . htmlspecialchars($labelEstado, ENT_QUOTES, 'UTF-8') . '</p>
            <p>' . htmlspecialchars($mensagemEstado, ENT_QUOTES, 'UTF-8') . '</p>
            <br>
            <p>Cumprimentos,<br><strong>NR DETAIL</strong></p>
        ';

        $mail->AltBody =
            'Olá, ' . $nomeCliente .
            '. O estado da tua encomenda #' . (int)$encomendaId .
            ' foi atualizado para: ' . $labelEstado . '. ' . $mensagemEstado;

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL ESTADO: ' . $mail->ErrorInfo;
    }
}