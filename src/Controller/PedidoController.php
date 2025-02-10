<?php

namespace App\Controller;

use App\DTO\CrearPedidoLineaPedidoDTO;
use App\Entity\LineaPedido;
use App\Entity\Pedido;
use App\Entity\Perfil;
use App\Entity\Producto;
use App\Entity\Usuario;
use App\Repository\PedidoRepository;
use App\Repository\PerfilRepository;
use App\Repository\ProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/crear', name: 'crear_pedido', methods: ['POST'])]
    public function crear(Request $request, SerializerInterface $serializer): JsonResponse
    {
        // Decodificar el JSON en un DTO
        try {
            /** @var CrearPedidoLineaPedidoDTO $crearPedidoLineaPedidoDTO */
            $crearPedidoLineaPedidoDTO = $serializer->deserialize($request->getContent(), CrearPedidoLineaPedidoDTO::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['error' => 'Datos inválidos'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Buscar el perfil del pedido
        $perfil = $this->perfilRepository->find($crearPedidoLineaPedidoDTO->perfilId);
        if (!$perfil) {
            return $this->json(['error' => 'Perfil no encontrado'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Crear el pedido
        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());
        $pedido->setEstado($crearPedidoLineaPedidoDTO->estado);
        $pedido->setPerfil($perfil);

        $pagoTotal = 0; // Variable para calcular el pago total del pedido

        // Procesar las líneas de pedido
        foreach ($crearPedidoLineaPedidoDTO->lineasPedido as $lineaDTO) {
            $producto = $this->productoRepository->find($lineaDTO->productoId);
            if (!$producto) {
                return $this->json(['error' => 'Producto no encontrado: ' . $lineaDTO->productoId], JsonResponse::HTTP_NOT_FOUND);
            }

            // Calcular el precio de la línea: precio del producto * cantidad
            $precioLinea = $producto->getPrecio() * $lineaDTO->cantidad;
            $pagoTotal += $precioLinea; // Sumar el precio al total del pedido

            $lineaPedido = new LineaPedido();
            $lineaPedido->setCantidad($lineaDTO->cantidad);
            $lineaPedido->setPrecio($precioLinea); // Asignar el precio calculado
            $lineaPedido->setProducto($producto);
            $lineaPedido->setPedido($pedido);

            $this->em->persist($lineaPedido);
        }

        // Asignar el pago total al pedido
        $pedido->setPagoTotal($pagoTotal);

        // Guardar el pedido y las líneas en la base de datos
        $this->em->persist($pedido);
        $this->em->flush();

        return $this->json(['message' => 'Pedido creado correctamente', 'id' => $pedido->getId()], JsonResponse::HTTP_CREATED);
    }

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
        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());
        $pedido->setEstado(true);
        $pedido->setPagoTotal(0);
        $pedido->setPerfil($perfil);

        $em->persist($pedido);
        $em->flush();

        $total = 0;

        $productosComprados = '';

        // Crear las líneas del pedido
        foreach ($data['productos'] as $productoData) {
            $producto = $em->getRepository(Producto::class)->find($productoData['id']);
            if (!$producto) {
                return new JsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }

            $lineaPedido = new LineaPedido();
            $lineaPedido->setPedido($pedido);
            $lineaPedido->setProducto($producto);
            $lineaPedido->setCantidad($productoData['cantidad']);
            $lineaPedido->setPrecio($producto->getPrecio() * $productoData['cantidad']);

            $total += $productoData['cantidad'] * $producto->getPrecio();

            $productosComprados .= $producto->getNombre() .' '. 'Precio:  ' .$producto->getPrecio().'€ '. ' x '
                . $productoData['cantidad'] . ' '.' Código:  '. $producto->getCodigoJuego() . "\n";


            $em->persist($lineaPedido);
        }

        $pedido->setPagoTotal($total);

        $em->flush();

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($usuario->getCorreo())
            ->subject('Pedido registrado con éxito')
            ->text('Gracias por tu compra. Aquí tienes el detalle de tu pedido:' . "\n" . $productosComprados. "\n" .'Total: ' . $total . '€'."\n".'Gracias por confiar en nosotros');

        $mailer->send($email);

        return new JsonResponse(['success' => true, 'message' => 'Pedido registrado con éxito', 'pedidoId' => $pedido->getId()]);
    }


    #[Route('/totalpedidos', name: 'total_pedidos', methods: ['GET'])]
    public function verTodosPedidos(SerializerInterface $serializer): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pedidos = $this->pedidoRepository->findAll();

        $total = array_reduce($pedidos, function ($carry, $pedido) {
            return $carry + $pedido->getPagoTotal();
        }, 0);

        $data = ['pedidos' => $pedidos, 'total' => $total];

        // Serializa los pedidos usando el grupo "pedido:read"
        $jsonPedidos = $serializer->serialize($data, 'json', ['groups' => 'pedido:read']);

        return new JsonResponse($jsonPedidos, 200, [], true);
    }


    //ESTE METODO EL ULTIMO SIEMPRE SI NO JODE TO-DO
    //REPITO
    //EL ULTIMO SI O SI U OS REVIENTA EL METODO QUE VAYA DELANTE
    //EL
    //ULTIMO
    //S I E M P R E
    #[Route('/{id}', name: 'app_pedido_findById', methods: ['GET'])]
    public function findById(int $id): JsonResponse
    {
        $pedido = $this->pedidoRepository->find($id);

        if (!$pedido) {
            return $this->json(['error' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($pedido);
    }
}
