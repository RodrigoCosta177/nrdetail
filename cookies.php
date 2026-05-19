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
    <title>Política de Cookies - NR Detail</title>
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

        .cookie-types { display: grid; gap: 10px; margin-top: 6px; }
        .cookie-type-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid #1e1e1e;
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .cookie-type-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
        .cookie-type-info strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
        }
        .cookie-type-info span {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            line-height: 1.5;
        }

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
    <div class="badge">🍪 Privacidade</div>
    <h1>Política de Cookies</h1>
    <p>Última atualização: <?= date('d/m/Y') ?></p>
</div>

<div class="legal-body">

    <div class="legal-section">
        <h2><span class="num">1</span> O que são cookies?</h2>
        <p>Cookies são pequenos ficheiros de texto armazenados no teu dispositivo quando visitas um website. Permitem que o site reconheça o teu browser em visitas futuras e guarde preferências como o estado de sessão ou o conteúdo do carrinho.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">2</span> Que cookies utilizamos?</h2>
        <div class="cookie-types">
            <div class="cookie-type-item">
                <span class="cookie-type-icon">🔒</span>
                <div class="cookie-type-info">
                    <strong>Cookies essenciais</strong>
                    <span>Necessários para o funcionamento do site. Incluem a gestão de sessão de utilizador e o carrinho de compras. Não podem ser desativados.</span>
                </div>
            </div>
            <div class="cookie-type-item">
                <span class="cookie-type-icon">⚙️</span>
                <div class="cookie-type-info">
                    <strong>Cookies de sessão</strong>
                    <span>Mantêm a tua sessão ativa enquanto navegas. São eliminados automaticamente quando fechas o browser.</span>
                </div>
            </div>
            <div class="cookie-type-item">
                <span class="cookie-type-icon">💾</span>
                <div class="cookie-type-info">
                    <strong>Cookies de preferências</strong>
                    <span>Guardam as tuas escolhas, como o consentimento de cookies, para não teres de as repetir em cada visita.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="legal-section">
        <h2><span class="num">3</span> Gestão de cookies</h2>
        <p>Podes aceitar ou rejeitar cookies através do banner que aparece na primeira visita. Podes também gerir ou eliminar cookies a qualquer momento através das configurações do teu browser.</p>
        <p>Nota: desativar cookies essenciais pode impedir o correto funcionamento de algumas funcionalidades, como o login ou o carrinho.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">4</span> Contacto</h2>
        <p>Para qualquer questão relacionada com cookies, contacta-nos através da página de <a href="/nrdetail/contactos.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">contactos</a>.</p>
    </div>

    <p class="legal-footer">© <?= date("Y") ?> NR Detail Car & Care — Todos os direitos reservados</p>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/footer.php'); ?>

</body>
</html>