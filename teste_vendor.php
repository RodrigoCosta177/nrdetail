<?php
require_once __DIR__ . '/vendor/autoload.php';

echo '<pre>';

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "PHPMailer OK";
} else {
    echo "PHPMailer NAO CARREGOU";
}

echo '</pre>';