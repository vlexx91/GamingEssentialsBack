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

use App\Entity\Usuario;
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


// src/Controller/MailerController.php

// src/Controller/MailerController.php

#[Route('/api/mailer')]
class MailerController extends AbstractController
{
    #[Route("/send-email", name:"send_email")]
    function sendTestEmail(Request $request): Response
    {
        if (!$this->getUser()) {
            return new Response("Acceso no autorizado", Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->getUser();
        $userEmail = $user->getCorreo();
        $userName = $user->getUsername();

        $data = json_decode($request->getContent(), true);
        $to = $data['to'] ?? 'gameessentialsteam@gmail.com';
        $subject = $data['subject'] ?? 'Consulta Chatbox';
        $text = $data['text'] ?? null;

        if (empty($subject) || empty($text)) {
            return new Response("Both subject and text are required.", Response::HTTP_BAD_REQUEST);
        }

        // Incluir el correo del usuario en el mensaje
        $text .= "\n\nCorreo del cliente: " . $userEmail . "\n\nUsername del cliente: " . $userName;

        $transport = Transport::fromDsn('smtp://gameessentialsteam@gmail.com:fupzrvwiatrfmrke@smtp.gmail.com:587');
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($to)
            ->subject($subject)
            ->text($text);

        try {
            $mailer->send($email);

            return new JsonResponse(
                ["message" => "Correo enviado correctamente."],
                Response::HTTP_OK,
                ['Content-Type' => 'application/json']
            );
        } catch (\Exception $e) {
            // Registrar el error para depuración
            $this->get('logger')->error('Error al enviar el correo: ' . $e->getMessage());

            return new JsonResponse(
                ["error" => "Error al enviar el correo: " . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route("/send-product-notification", name: "send_product_notification", methods: ["POST"])]
    public function sendProductNotification(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $userEmail = $data['email'] ?? null;
        $productName = $data['productName'] ?? null;

        if (!$userEmail || !$productName) {
            return new JsonResponse(["error" => "Faltan parámetros."], Response::HTTP_BAD_REQUEST);
        }

        $transport = Transport::fromDsn('smtp://gameessentialsteam@gmail.com:fupzrvwiatrfmrke@smtp.gmail.com:587');
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($userEmail)
            ->subject("¡{$productName} está disponible!")
            ->html("<p>Hola,</p><p>El producto <strong>{$productName}</strong> ya está disponible en nuestra tienda.</p><p>¡Corre a comprarlo!</p>");

        try {
            $mailer->send($email);
            return new JsonResponse(["message" => "Notificación enviada a $userEmail"], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Error al enviar el correo: " . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
