<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   CONFIGURAR MAIL
========================= */
function configurarMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'papnrdetail@gmail.com';
    $mail->Password = 'jvkwobxogswvkivf'; // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('papnrdetail@gmail.com', 'NR DETAIL');

    return $mail;
}

/* =========================
   EMAIL BOAS VINDAS
========================= */
function enviarEmailBoasVindas($emailCliente, $nomeCliente)
{
    try {
        $mail = configurarMailer();

        $mail->addAddress($emailCliente, $nomeCliente);

        $mail->isHTML(true);
        $mail->Subject = 'Bem-vindo à NR DETAIL';

        $mail->Body = '
            <div style="font-family: Arial, sans-serif; background:#111; padding:30px; color:#fff;">
                <div style="max-width:600px; margin:auto; background:#1c1c1c; padding:28px; border-radius:14px; border:1px solid #333;">
                    
                    <h2 style="color:#ffcc00; margin-top:0;">
                        Bem-vindo à NR DETAIL, ' . htmlspecialchars($nomeCliente, ENT_QUOTES, 'UTF-8') . '!
                    </h2>

                    <p>A tua conta foi criada com sucesso.</p>

                    <p>
                        Já podes:
                        <br>✔ Fazer marcações online
                        <br>✔ Acompanhar encomendas
                        <br>✔ Comprar produtos na loja
                    </p>

                    <p style="margin-top:25px;">
                        Obrigado por confiares na <strong>NR DETAIL</strong>.
                    </p>

                    <br>

                    <p style="color:#aaa;">
                        Cumprimentos,<br>
                        <strong style="color:#ffcc00;">NR DETAIL Car & Care</strong>
                    </p>

                </div>
            </div>
        ';

        $mail->AltBody =
            'Bem-vindo à NR DETAIL, ' . $nomeCliente .
            '! A tua conta foi criada com sucesso. Já podes fazer marcações, encomendas e usar o site.';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL BOAS VINDAS: ' . $mail->ErrorInfo;
    }
}

/* =========================
   EMAIL ENCOMENDA
========================= */
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
            'Encomenda #' . (int)$encomendaId .
            ' registada. Total: ' .
            number_format((float)$total, 2, ',', '.') . '€';

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL: ' . $mail->ErrorInfo;
    }
}

/* =========================
   RECUPERAR PASSWORD
========================= */
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
            <p>Clica no botão abaixo para redefinir a tua password:</p>

            <a href="' . htmlspecialchars($linkReset) . '" style="
                display:inline-block;
                background:#ffcc00;
                color:#000;
                padding:12px 20px;
                border-radius:8px;
                text-decoration:none;
                font-weight:bold;
            ">
                Redefinir Password
            </a>

            <p>Este link é válido por 1 hora.</p>

            <br>
            <p>NR DETAIL</p>
        ';

        $mail->AltBody =
            'Redefine a tua password aqui: ' . $linkReset;

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL RESET: ' . $mail->ErrorInfo;
    }
}

/* =========================
   ESTADOS ENCOMENDA
========================= */
function getLabelEstadoEncomenda($estado)
{
    $labels = [
        'pendente' => 'Pendente',
        'processada' => 'Em processamento',
        'pronta_levantamento' => 'Pronta para levantamento',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada'
    ];

    return $labels[$estado] ?? ucfirst($estado);
}

function getMensagemEstadoEncomenda($estado)
{
    switch ($estado) {
        case 'pendente':
            return 'A tua encomenda está pendente.';
        case 'processada':
            return 'A tua encomenda está a ser preparada.';
        case 'pronta_levantamento':
            return 'Já podes levantar a tua encomenda.';
        case 'concluida':
            return 'Encomenda concluída com sucesso.';
        case 'cancelada':
            return 'A encomenda foi cancelada.';
        default:
            return 'Estado atualizado.';
    }
}

/* =========================
   EMAIL ATUALIZAÇÃO ESTADO
========================= */
function enviarEmailAtualizacaoEstadoEncomenda($emailCliente, $nomeCliente, $encomendaId, $novoEstado)
{
    try {
        $mail = configurarMailer();

        $labelEstado = getLabelEstadoEncomenda($novoEstado);
        $mensagemEstado = getMensagemEstadoEncomenda($novoEstado);

        $mail->addAddress($emailCliente, $nomeCliente);

        $mail->isHTML(true);
        $mail->Subject = 'Atualização da encomenda #' . (int)$encomendaId;

        $mail->Body = '
            <h2>Olá, ' . htmlspecialchars($nomeCliente) . '!</h2>
            <p>Estado atualizado:</p>
            <p><strong>' . htmlspecialchars($labelEstado) . '</strong></p>
            <p>' . htmlspecialchars($mensagemEstado) . '</p>
            <br>
            <p>NR DETAIL</p>
        ';

        $mail->AltBody =
            'Estado da encomenda #' . (int)$encomendaId .
            ': ' . $labelEstado;

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'ERRO MAIL ESTADO: ' . $mail->ErrorInfo;
    }
}