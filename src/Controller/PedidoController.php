<?php

namespace App\Controller;

use App\DTO\CrearPedidoLineaPedidoDTO;
use App\Entity\LineaPedido;
use App\Entity\Pedido;
use App\Entity\Perfil;
use App\Entity\Producto;
use App\Entity\Usuario;
use App\Enum\Categoria;
use App\Repository\LineaPedidoRepository;
use App\Repository\PedidoRepository;
use App\Repository\PerfilRepository;
use App\Repository\ProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/pedido')]
class PedidoController extends AbstractController
{
    private PedidoRepository $pedidoRepository;
    private EntityManagerInterface $em;
    private PerfilRepository $perfilRepository;


    private ProductoRepository $productoRepository;

    public function __construct(PedidoRepository $pedidoRepository, EntityManagerInterface $em, PerfilRepository $perfilRepository,ProductoRepository $productoRepository)
    {
        date_default_timezone_set('Europe/Madrid'); // Set the timezone

        $this->pedidoRepository = $pedidoRepository;
        $this->em = $em;
        $this->perfilRepository = $perfilRepository;
        $this->productoRepository = $productoRepository;
    }

    #[Route('', name: 'app_pedido')]
    public function index(): Response
    {
       $pedido = $this->pedidoRepository->findAll();

        return $this->json($pedido);
    }

    #[Route('/findall', name: 'todos_pedidos', methods: ['GET'])]
    public function findAll(): JsonResponse
    {
        // Obtener todos los pedidos
        $pedidos = $this->pedidoRepository->findAll();

        // Preparar la respuesta
        $data = [];
        foreach ($pedidos as $pedido) {
            $data[] = [
                'id' => $pedido->getId(),
                'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                'estado' => $pedido->getEstado(),
                'pagoTotal' => $pedido->getPagoTotal(),
                'perfil' => [
                    'id' => $pedido->getPerfil()->getId(),
                    'nombre' => $pedido->getPerfil()->getNombre(),
                    'apellido' => $pedido->getPerfil()->getApellido(),
                    'direccion' => $pedido->getPerfil()->getDireccion(),
                    'dni' => $pedido->getPerfil()->getDni(),
                    'telefono' => $pedido->getPerfil()->getTelefono(),
                    'fecha_nacimiento' => $pedido->getPerfil()->getFechaNacimiento()->format('Y-m-d'),
                ],
            ];
        }

        return $this->json($data);
    }

//    #[Route('/crear', name: 'crear_pedido', methods: ['POST'])]
//    public function crear(Request $request, SerializerInterface $serializer): JsonResponse
//    {
//        // Decodificar el JSON en un DTO
//        try {
//            /** @var CrearPedidoLineaPedidoDTO $crearPedidoLineaPedidoDTO */
//            $crearPedidoLineaPedidoDTO = $serializer->deserialize($request->getContent(), CrearPedidoLineaPedidoDTO::class, 'json');
//        } catch (\Exception $e) {
//            return $this->json(['error' => 'Datos inválidos'], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        // Buscar el perfil del pedido
//        $perfil = $this->perfilRepository->find($crearPedidoLineaPedidoDTO->perfilId);
//        if (!$perfil) {
//            return $this->json(['error' => 'Perfil no encontrado'], JsonResponse::HTTP_NOT_FOUND);
//        }
//
//        // Crear el pedido
//        $pedido = new Pedido();
//        $pedido->setFecha(new \DateTime());
//        $pedido->setEstado($crearPedidoLineaPedidoDTO->estado);
//        $pedido->setPerfil($perfil);
//
//        $pagoTotal = 0; // Variable para calcular el pago total del pedido
//
//        // Procesar las líneas de pedido
//        foreach ($crearPedidoLineaPedidoDTO->lineasPedido as $lineaDTO) {
//            $producto = $this->productoRepository->find($lineaDTO->productoId);
//            if (!$producto) {
//                return $this->json(['error' => 'Producto no encontrado: ' . $lineaDTO->productoId], JsonResponse::HTTP_NOT_FOUND);
//            }
//
//            // Calcular el precio de la línea: precio del producto * cantidad
//            $precioLinea = $producto->getPrecio() * $lineaDTO->cantidad;
//            $pagoTotal += $precioLinea; // Sumar el precio al total del pedido
//
//            $lineaPedido = new LineaPedido();
//            $lineaPedido->setCantidad($lineaDTO->cantidad);
//            $lineaPedido->setPrecio($precioLinea); // Asignar el precio calculado
//            $lineaPedido->setProducto($producto);
//            $lineaPedido->setPedido($pedido);
//
//            $this->em->persist($lineaPedido);
//        }
//
//        // Asignar el pago total al pedido
//        $pedido->setPagoTotal($pagoTotal);
//
//        // Guardar el pedido y las líneas en la base de datos
//        $this->em->persist($pedido);
//        $this->em->flush();
//
//        return $this->json(['message' => 'Pedido creado correctamente', 'id' => $pedido->getId()], JsonResponse::HTTP_CREATED);
//    }

    #[Route('/eliminar/{id}', name: 'eliminar_pedido', methods: ['DELETE'])]
    public function eliminarPedido(int $id): JsonResponse
    {
        $pedido = $this->pedidoRepository->find($id);

        if (!$pedido) {
            return $this->json(['error' => 'Pedido no encontrado'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Eliminar el pedido (Doctrine se encargará de eliminar las líneas de pedido por cascada)
        $this->em->remove($pedido);
        $this->em->flush();

        return $this->json(['message' => 'Pedido eliminado correctamente'], JsonResponse::HTTP_OK);
    }

    // Ruta nueva para obtener pedidos por id_perfil
    #[Route('/perfil/{perfilId}', name: 'app_pedido_by_perfil', methods: ['GET'])]
    public function findByPerfil(int $perfilId, SerializerInterface $serializer): JsonResponse
    {
        // Buscar el perfil por id_perfil
        $perfil = $this->perfilRepository->find($perfilId);

        if (!$perfil) {
            return $this->json(['error' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Buscar los pedidos asociados al perfil
        $pedidos = $this->pedidoRepository->findBy(['perfil' => $perfil]);

        if (empty($pedidos)) {
            return $this->json(['message' => 'No se encontraron pedidos para este perfil'], Response::HTTP_OK);
        }

        // Serializar los pedidos con los grupos
        $data = [];
        foreach ($pedidos as $pedido) {
            $data[] = [
                'id' => $pedido->getId(),
                'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                'estado' => $pedido->getEstado(),
                'pagoTotal' => $pedido->getPagoTotal(),
                'perfil' => [
                    'id' => $pedido->getPerfil()->getId(),
                    'nombre' => $pedido->getPerfil()->getNombre(),
                    'apellido' => $pedido->getPerfil()->getApellido(),
                    'direccion'=> $pedido->getPerfil()->getDireccion(),
                    'dni'=>$pedido->getPerfil()->getDni(),
                    'telefono'=>$pedido->getPerfil()->getTelefono(),
                    'fecha_nacimiento'=>$pedido->getPerfil()->getFechaNacimiento()
                ],
            ];
        }
        // Devolver los pedidos en formato JSON
        return $this->json($data);
    }

    //Otro metodo para encontrar los pedidos por perfil(Este es el que se usa en el perfil del cliente)

    #[Route('/perfilpedido', name: 'app_pedido_by_token', methods: ['GET'])]
    public function findByToken(Request $request, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager, PerfilRepository $perfilRepository, PedidoRepository $pedidoRepository): JsonResponse
    {
        // Obtener el token del encabezado Authorization
        $token = $request->headers->get('Authorization');
        if (!$token) {
            return new JsonResponse(['message' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
        }

        // Limpiar el token (eliminar el "Bearer ")
        $formatToken = str_replace('Bearer ', '', $token);

        // Decodificar el token
        try {
            $finalToken = $jwtManager->parse($formatToken);
            $username = $finalToken['username'] ?? null;
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        if (!$username) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        // Buscar el usuario por su username
        $user = $entityManager->getRepository(Usuario::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Obtener el perfil del usuario
        $perfil = $perfilRepository->findOneBy(['usuario' => $user->getId()]);

        if (!$perfil) {
            return new JsonResponse(['message' => 'Profile not found'], Response::HTTP_NOT_FOUND);
        }

        // Buscar los pedidos asociados al perfil
        $pedidos = $pedidoRepository->findBy(['perfil' => $perfil],['fecha' => 'DESC']);

        if (empty($pedidos)) {
            return new JsonResponse(['message' => 'No orders found for this profile'], Response::HTTP_OK);
        }

        // Serializar los pedidos con los grupos
        $data = [];
        foreach ($pedidos as $pedido) {
            $data[] = [
                'id' => $pedido->getId(),
                'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                'estado' => $pedido->getEstado(),
                'pagoTotal' => $pedido->getPagoTotal(),
                'perfil' => [
                    'id' => $pedido->getPerfil()->getId(),
                    'nombre' => $pedido->getPerfil()->getNombre(),
                    'apellido' => $pedido->getPerfil()->getApellido(),
                    'direccion' => $pedido->getPerfil()->getDireccion(),
                    'dni' => $pedido->getPerfil()->getDni(),
                    'telefono' => $pedido->getPerfil()->getTelefono(),
                    'fecha_nacimiento' => $pedido->getPerfil()->getFechaNacimiento()
                ],
            ];
        }

        // Devolver los pedidos en formato JSON
        return $this->json($data);
    }

    #[Route('/{id}/lineas', name: 'pedido_lineas', methods: ['GET'])]
    public function obtenerLineasDePedido(int $id, PedidoRepository $pedidoRepository): JsonResponse
    {
        $pedido = $pedidoRepository->find($id);

        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Obtener las líneas de pedido asociadas
        $lineasPedido = $pedido->getLineaPedidos();
        $lineasPedidoArray = [];

        foreach ($lineasPedido as $linea) {
            $producto = $linea->getProducto();
            $lineasPedidoArray[] = [
                'id' => $linea->getId(),
                'cantidad' => $linea->getCantidad(),
                'precio' => $linea->getPrecio(),
                'producto' => [
                    'id' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'imagen' => $producto->getImagen(),
                    'precio' => $producto->getPrecio(),
                    'codigo_juego' => $producto->getCodigoJuego(),
                ]
            ];
        }

        return new JsonResponse($lineasPedidoArray, Response::HTTP_OK);
    }

    //POR REALIZAR
//    #[Route('/realizar', name: 'realizar_pedido', methods: ['POST'])]
//    public function realizarPedido(Request $request): JsonResponse
//    {
//        $datos = json_decode($request->getContent(), true);
//        $pedido = new Pedido();
//        $total = 0.0;
//
//        $cliente = $this->perfilRepository->findPerfilByUsuarioId($datos->getIdUsuario());
//        $pedido->setPerfil($cliente);
//        $pedido->setFecha(new \DateTime());
//
//        $this->em->persist($pedido);
//        $this->em->flush();
//
//        foreach ($datos->getProductos() as $productoCarritoDTO) {
//            $lineaPedido = new LineaPedido();
//            $lineaPedido->setPedido($pedido);
//            $lineaPedido->setCantidad($productoCarritoDTO->getCantidad());
////            $lineaPedido->setProducto($this->productoService->getProductoById($productoCarritoDTO->getIdProducto()));
//            $lineaPedido->setPrecio($productoCarritoDTO->getPrecioUnitario());
//            $lineaPedido->setProducto($this->productoRepository->find($productoCarritoDTO->getIdProducto()));
//
//            $this->em->persist($lineaPedido);
//            $total += $productoCarritoDTO->getTotal();
//
//        }
//
//
//        $this->em->flush();
//
//
//        return new JsonResponse(['message' => 'Pedido realizado correctamente'], JsonResponse::HTTP_CREATED);
//    }
//

    #[Route('/pedido/nuevo', name: 'nuevo_pedido', methods: ['POST'])]
    public function nuevoPedido(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['productos']) || count($data['productos']) === 0) {
            return new JsonResponse(['success' => false, 'message' => 'Carrito vacío'], 400);
        }

        $username = $this->getUser()->getUsername();

        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $username]);
        if (!$usuario) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $perfil = $em->getRepository(Perfil::class)->findOneBy(['usuario' => $usuario]);
        if (!$perfil) {
            return new JsonResponse(['success' => false, 'message' => 'Perfil no encontrado'], 404);
        }

        $productosPerifericos = [];
        $otrosProductos = [];

        foreach ($data['productos'] as $productoData) {
            $producto = $em->getRepository(Producto::class)->find($productoData['id']);
            if (!$producto) {
                return new JsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            if ($producto->getCategoria() === Categoria::PERIFERICOS) {
                $productosPerifericos[] = $productoData;
            } else {
                $otrosProductos[] = $productoData;
            }
        }

        if (!empty($productosPerifericos) && !empty($otrosProductos)) {
            $this->crearPedido($em, $perfil, $productosPerifericos, false);
            $this->crearPedido($em, $perfil, $otrosProductos, true);
        } else {
            $estado = !empty($productosPerifericos) ? false : true;
            $this->crearPedido($em, $perfil, $data['productos'], $estado);
        }

        return new JsonResponse(['success' => true, 'message' => 'Pedidos registrados con éxito']);
    }

    private function crearPedido(EntityManagerInterface $em, Perfil $perfil, array $productos, bool $estado): void
    {
        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());
        $pedido->setEstado($estado);
        $pedido->setPagoTotal(0);
        $pedido->setPerfil($perfil);

        $em->persist($pedido);
        $em->flush();

        $total = 0;
        $productosComprados = '';
        $shippingFee = 0;

        foreach ($productos as $productoData) {
            $producto = $em->getRepository(Producto::class)->find($productoData['id']);
            for ($i = 0; $i < $productoData['cantidad']; $i++) {
                $lineaPedido = new LineaPedido();
                $lineaPedido->setPedido($pedido);
                $lineaPedido->setProducto($producto);
                $lineaPedido->setCantidad(1); // Set quantity to 1 for each unique code
                $lineaPedido->setPrecio($producto->getPrecio());

                $total += $producto->getPrecio();
                $codigoJuego = $producto->getCodigoJuego() . '-' . uniqid();
                $productosComprados .= '- ' . $producto->getNombre() . ' ' . 'Precio:  ' . $producto->getPrecio() . '€ ' . ' Código:  ' . $codigoJuego . "\n";

                if ($producto->getCategoria() === Categoria::PERIFERICOS) {
                    $shippingFee += 4.99; // Add shipping fee for each peripheral
                }

                $em->persist($lineaPedido);
            }
        }

        $managementFee = 4.99;
        $total += $managementFee + $shippingFee;

        $pedido->setPagoTotal($total);
        $em->flush();

        $pdfPath = $this->generarPdfPedido($pedido, $productosComprados, $total);

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($perfil->getUsuario()->getCorreo())
            ->subject('Pedido registrado con éxito')
            ->text('Gracias por tu compra. Aquí tienes el detalle de tu pedido:' . "\n" .'- ' .$productosComprados. "\n" .'Total: ' . $total . '€ (incluye 4.99€ de gastos de gestión y ' . $shippingFee . '€ de gastos de envío)'."\n".'Gracias por confiar en nosotros')
            ->attachFromPath($pdfPath, 'GamingEssentials Pedido_' . $pedido->getId() . '.pdf');

        $mailer = new Mailer(Transport::fromDsn($_ENV['MAILER_DSN']));
        $mailer->send($email);

        unlink($pdfPath);

    }private function generarPdfPedido(Pedido $pedido, string $productosComprados, float $total): string
{
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);

    $html = "
   <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        th {
            background-color: #f4f4f4;
            text-align: center;
        }
        th:nth-child(3), td:nth-child(3) {
            width: 50%;
        }
        .total {
            font-weight: bold;
            color: #d9534f;
        }
        .footer {
            margin-top: 20px;
            font-style: italic;
        }
    </style>
    <h1>Detalle del Pedido</h1>
    <table>
        <tr>
            <th><strong>Pedido ID</strong></th>
            <th><strong>Fecha</strong></th>
            <th><strong>Productos</strong></th>
            <th class='total'><strong>Total</strong></th>
        </tr>
        <tr>
            <td>{$pedido->getId()}</td>
            <td>{$pedido->getFecha()->format('d/m/Y')}</td>
            <td><pre style='white-space: pre-wrap;'><strong>{$productosComprados}</strong></pre></td>
            <td class='total'>{$total}€</td>
        </tr>
    </table>
    <p class='footer'>Gracias por confiar en nosotros.</p>
    ";


    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfPath = sys_get_temp_dir() . '/pedido_' . $pedido->getId() . '.pdf';
    file_put_contents($pdfPath, $dompdf->output());

    return $pdfPath;
}


    #[Route('/totalpedidos', name: 'total_pedidos1', methods: ['GET'])]
    public function verTodosPedidos(SerializerInterface $serializer): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pedidos = $this->pedidoRepository->findAll();

        $total = array_reduce($pedidos, function ($carry, $pedido) {
            return $pedido->getEstado() ? $carry + $pedido->getPagoTotal() : $carry;
        }, 0);

        $data = [];
        foreach ($pedidos as $pedido) {
            $data[] = [
                'id' => $pedido->getId(),
                'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                'estado' => $pedido->getEstado(),
                'pagoTotal' => $pedido->getPagoTotal(),
                //forma guay del david para llegar a fk
                'username'=>$pedido->getPerfil()->getUsuario()->getUsername()
            ];
        }

        $response = [
            'pedidos' => $data,
            'total' => $total,
        ];

        return $this->json($response);
    }

    #[Route('/{id}/descargar-pdf', name: 'pedido_pdf')]
    public function descargarPdf(int $id, EntityManagerInterface $em): Response
    {
        $pedido = $em->getRepository(Pedido::class)->find($id);

        if (!$pedido) {
            return new Response('Pedido no encontrado', 404);
        }

        // Simulamos la generación de productos comprados para el ejemplo
        $productosComprados = '';
        $total = $pedido->getPagoTotal();

        foreach ($pedido->getLineaPedidos() as $linea) {
            // Obtenemos el producto relacionado con la línea de pedido
            $producto = $linea->getProducto();  // Obtenemos el producto

            // Repetimos el código del producto tantas veces como la cantidad de esa línea
            $codigoProductoRepetido = str_repeat($producto->getCodigoJuego() . ' ', $linea->getCantidad());

            // Añadimos el detalle del producto y su código repetido
            $productosComprados .= '- ' . $producto->getNombre()
                . ' | Códigos: ' . $codigoProductoRepetido  // Repetimos el código
                . ' | Precio: ' . $linea->getPrecio() . '€'
                . ' x ' . $linea->getCantidad() . "\n";  // Información del producto
        }

        // Generamos el PDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $html = "
   <style>
    body {
        font-family: Arial, sans-serif;
        text-align: center;
    }
    h1 {
        color: #333;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        table-layout: fixed;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    th {
        background-color: #f4f4f4;
        text-align: center;
    }
    th:nth-child(3), td:nth-child(3) {
        width: 50%;
    }
    .total {
        font-weight: bold;
        color: #d9534f;
    }
    .footer {
        margin-top: 20px;
        font-style: italic;
    }
</style>
<h1>Detalle del Pedido</h1>
<table>
    <tr>
        <th><strong>Pedido ID</strong></th>
        <th><strong>Fecha</strong></th>
        <th><strong>Productos</strong></th>
        <th class='total'><strong>Total</strong></th>
    </tr>
    <tr>
        <td>{$pedido->getId()}</td>
        <td>{$pedido->getFecha()->format('d/m/Y')}</td>
        <td><pre style='white-space: pre-wrap;'><strong>{$productosComprados}</strong></pre></td>
        <td class='total'>{$total}€</td>
    </tr>
</table>
<p class='footer'>Gracias por confiar en nosotros.</p>
    ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="pedido_' . $pedido->getId() . '.pdf"',
        ]);
    }



    #[Route('/cambiarEstado/{id}', name: 'cambiar_estado_pedido', methods: ['PUT'])]
    public function cambiarEstado(int $id, EntityManagerInterface $em, LineaPedidoRepository $lineaPedidoRepository): JsonResponse
    {
        // Verificar si el usuario tiene el rol ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Buscar el pedido por su ID
        $pedido = $this->pedidoRepository->find($id);

        if (!$pedido) {
            return $this->json(['message' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Cambiar el estado del pedido a true
        $pedido->setEstado(true);
        $em->flush();


        $productos = $pedido->getLineaPedidos();
        $nombresProductos = [];
        foreach ($productos as $lineaPedido) {
            $nombresProductos[] = $lineaPedido->getProducto()->getNombre();
        }
        $nombresProductosStr = implode(', ', $nombresProductos);

        $perfil= $pedido->getPerfil();
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        $email= (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($perfil->getUsuario()->getCorreo())
            ->subject('Estado del pedido actualizado')
            ->text('Su pedido con ID '. $pedido->getId(). ', '. 'que contiene: '.$nombresProductosStr . ', le llegará en los proximos dias.');


        $mailer->send($email);

        return $this->json(['message' => 'Estado del pedido cambiado y correo enviado'], Response::HTTP_OK);
    }



}
