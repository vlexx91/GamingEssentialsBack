<?php

//// Obtener la descripción del sistema (OID 1.3.6.1.2.1.1.1.0)
//$oid = "1.3.6.1.2.1.1.1.0";  // OID para la descripción del sistema
//$community = "public";         // Comunidad SNMP
//$hostname = "127.0.0.1";       // Usualmente "localhost" para pruebas locales
//
//// Obtener el valor del OID
//$result = snmpget($hostname, $community, $oid);
//
//echo "Resultado SNMP: " . $result;
//

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
require __DIR__ . '/../../vendor/autoload.php';


#[Route('/api/mailer')]
class MailerController extends AbstractController
{

    #[Route("/send-email", name:"send_email")]
    function sendTestEmail(Request $request): Response
    {

//        $user = $this->getUser();
//        $to = $user ? $user->getEmail() : null;

        $data = json_decode($request->getContent(), true);
        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? null;
        $text = $data['text'] ?? null;
//        $subject = $request->request->get('subject');
//        $text = $request->request->get('text');


        if (empty($subject) || empty($text)) {
            return new Response("Both subject and text are required.", Response::HTTP_BAD_REQUEST);
        }

        $transport = Transport::fromDsn('smtp://gameessentialsteam@gmail.com:fupzrvwiatrfmrke@smtp.gmail.com:587');
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($to)
            ->subject($subject)
            ->text($text);


        try {
            $mailer->send($email);
            return new Response("Correo enviado correctamente.");
        } catch (\Exception $e) {
            return new Response("Error al enviar el correo: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
