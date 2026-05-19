<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Termos e Condições - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #111; color: #fff; margin: 0; font-family: 'Barlow', 'Segoe UI', sans-serif; }

        .legal-hero {
            background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
            border-bottom: 1px solid #222;
            padding: 60px 20px 50px;
            text-align: center;
        }
        .legal-hero .badge {
            display: inline-block;
            background: rgba(255,204,0,0.12);
            border: 1px solid rgba(255,204,0,0.25);
            color: #ffcc00;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
        }
        .legal-hero h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(34px, 6vw, 54px);
            font-weight: 800;
            color: #fff;
            margin: 0 0 10px;
        }
        .legal-hero p { color: rgba(255,255,255,0.4); font-size: 14px; margin: 0; }

        .legal-body {
            max-width: 820px;
            margin: 0 auto;
            padding: 60px 20px 80px;
        }

        .legal-section {
            margin-bottom: 20px;
            background: #161616;
            border: 1px solid #222;
            border-radius: 16px;
            padding: 26px 30px;
            transition: border-color 0.25s;
        }
        .legal-section:hover { border-color: rgba(255,204,0,0.2); }

        .legal-section h2 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 19px;
            font-weight: 800;
            color: #ffcc00;
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .legal-section h2 .num {
            background: rgba(255,204,0,0.1);
            color: #ffcc00;
            font-size: 12px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .legal-section p {
            color: rgba(255,255,255,0.55);
            font-size: 15px;
            line-height: 1.75;
            margin: 0 0 10px;
        }
        .legal-section p:last-child { margin-bottom: 0; }

        .legal-footer {
            text-align: center;
            color: rgba(255,255,255,0.2);
            font-size: 13px;
            margin-top: 40px;
        }

        @media (max-width: 600px) {
            .legal-section { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/header.php'); ?>

<div class="legal-hero">
    <div class="badge">📄 Legal</div>
    <h1>Termos e Condições</h1>
    <p>Última atualização: <?= date('d/m/Y') ?></p>
</div>

<div class="legal-body">

    <div class="legal-section">
        <h2><span class="num">1</span> Objeto</h2>
        <p>Os presentes Termos e Condições regulam a utilização do website da NR Detail Car & Care e a compra de produtos disponibilizados online. Ao acederes ao site, concordas com os termos aqui descritos.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">2</span> Encomendas</h2>
        <p>As encomendas realizadas através do website são processadas para levantamento presencial nas instalações da NR Detail. O pagamento é efetuado no momento da entrega ou levantamento.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">3</span> Preços</h2>
        <p>Todos os preços apresentados no site incluem IVA à taxa legal em vigor. A NR Detail reserva-se o direito de alterar os preços a qualquer momento, sem aviso prévio. O preço aplicado à tua encomenda será o que estava em vigor no momento da finalização da compra.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">4</span> Marcações</h2>
        <p>As marcações de serviços são realizadas online e sujeitas a disponibilidade. A NR Detail reserva-se o direito de cancelar ou reagendar marcações em situações excecionais, notificando o cliente com a maior brevidade possível.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">5</span> Responsabilidade</h2>
        <p>A NR Detail Car & Care não se responsabiliza por interrupções temporárias do serviço, erros técnicos alheios à sua vontade, ou danos causados por uso indevido do website por parte do utilizador.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">6</span> Privacidade</h2>
        <p>Os dados pessoais recolhidos são tratados de acordo com a nossa <a href="/nrdetail/privacidade.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">Política de Privacidade</a> e em conformidade com o RGPD. Não partilhamos dados com terceiros sem o teu consentimento.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">7</span> Alterações</h2>
        <p>Reservamo-nos o direito de alterar os presentes Termos e Condições sempre que necessário. As alterações entram em vigor imediatamente após publicação no website. Recomendamos a consulta periódica desta página.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">8</span> Contacto</h2>
        <p>Para esclarecimentos sobre estes Termos e Condições, podes contactar-nos através da página de <a href="/nrdetail/contactos.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">contactos</a>.</p>
    </div>

    <p class="legal-footer">© <?= date("Y") ?> NR Detail Car & Care — Todos os direitos reservados</p>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/footer.php'); ?>

</body>
</html>