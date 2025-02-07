<?php

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

require 'vendor/autoload.php';

$transport = Transport::fromDsn('smtp://gameessentialsteam@gmail.com:fupzrvwiatrfmrke@smtp.gmail.com:587');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('gameessentialsteam@gmail.com')
    ->to('movis72681@andinews.com')
    ->subject('Prueba de Symfony Mailer')
    ->text('Este es un correo de prueba enviado desde Symfony.');

try {
    $mailer->send($email);
    echo "Correo enviado correctamente.";
} catch (\Exception $e) {
    echo "Error al enviar el correo: " . $e->getMessage();
}
