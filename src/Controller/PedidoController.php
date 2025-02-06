<?php

namespace App\Controller;

use App\DTO\CrearPedidoLineaPedidoDTO;
use App\Entity\LineaPedido;
use App\Entity\Pedido;
use App\Repository\PedidoRepository;
use App\Repository\PerfilRepository;
use App\Repository\ProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\SecurityBundle\Security;


#[Route('/api/pedido')]
class PedidoController extends AbstractController
{
    private PedidoRepository $pedidoRepository;
    private EntityManagerInterface $em;
    private PerfilRepository $perfilRepository;
    private ProductoRepository $productoRepository;
    private Security $security;


    public function __construct(PedidoRepository $pedidoRepository,
                                EntityManagerInterface $em,
                                PerfilRepository $perfilRepository,
                                ProductoRepository $productoRepository,
                                Security $security

    )
    {
        $this->pedidoRepository = $pedidoRepository;
        $this->em = $em;
        $this->perfilRepository = $perfilRepository;
        $this->productoRepository = $productoRepository;
        $this->security = $security;
    }

    #[Route('/findall', name: 'app_pedido')]
    public function index(): Response
    {
       $pedido = $this->pedidoRepository->findAll();

        return $this->json($pedido);
    }

    #[Route('/perfilpedido', name: 'pedidos_por_usuario', methods: ['GET'])]
    public function pedidosPorUsuario(): JsonResponse
    {
        // Obtener el usuario autenticado desde el token
        $usuario = $this->security->getUser();


        if (!$usuario) {
            return new JsonResponse(['message' => 'Usuario no autenticado'], 401);
        }

        // Buscar el perfil asociado al usuario
        $perfil = $this->perfilRepository->findOneBy(['usuario' => $usuario]);

        if (!$perfil || !$perfil->getId()) {
            return new JsonResponse(['message' => 'No se encontró un perfil válido para este usuario'], 404);
        }

        // Buscar los pedidos asociados a ese perfil usando findByPerfil
        $pedidos = $this->pedidoRepository->findByPerfil($perfil);

        if (empty($pedidos)) {
            return new JsonResponse(['message' => 'No se encontraron pedidos para este perfil'], 404);
        }

        return $this->json($pedidos);
    }

    #[Route('/{id}', name: 'app_pedido_findById', methods: ['GET'])]
    public function findById(string $id): JsonResponse
    {
        $id = (int) $id;

        $pedido = $this->pedidoRepository->find($id);

        if (!$pedido) {
            return $this->json(['error' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($pedido);
    }



    #[Route('/crearPorDTO', name: 'crear_pedido', methods: ['POST'])]
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
        $data = $serializer->normalize($pedidos, null, ['groups' => ['pedido:read']]);

        // Devolver los pedidos en formato JSON
        return $this->json($data);
    }

    #[Route('/crear', name: 'crear_pedido', methods: ['POST'])]
    public function crearDesdeCarrito(SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            return $this->json(['error' => 'El carrito está vacío'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $usuario = $this->getUser();
        if (!$usuario) {
            return $this->json(['error' => 'Usuario no autenticado'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $perfil = $this->perfilRepository->findOneBy(['usuario' => $usuario]);
        if (!$perfil) {
            return $this->json(['error' => 'Perfil no encontrado'], JsonResponse::HTTP_NOT_FOUND);
        }

        $pagoTotal = 0;

        // ✅ 1. Calcular el total del pedido antes de crearlo
        foreach ($cart as $item) {
            $producto = $this->productoRepository->find($item['id']);
            if (!$producto) {
                return $this->json(['error' => 'Producto no encontrado: ' . $item['id']], JsonResponse::HTTP_NOT_FOUND);
            }
            $pagoTotal += $producto->getPrecio() * $item['quantity'];
        }

        // ✅ 2. Crear y guardar el pedido en la base de datos
        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());
        $pedido->setEstado(true);
        $pedido->setPerfil($perfil);
        $pedido->setPagoTotal($pagoTotal);

        $this->em->persist($pedido);
        $this->em->flush(); // 🔹 Asegura que el pedido tiene un ID antes de asociarlo con las líneas

        dump($pedido->getId()); // 🔍 Verifica si el ID del pedido se ha generado correctamente


        // ❗ Verificamos que realmente el Pedido tiene un ID antes de continuar
        if (!$pedido->getId()) {
            return $this->json(['error' => 'No se pudo crear el pedido correctamente'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // ✅ 3. Asociamos las líneas de pedido al pedido creado
        foreach ($cart as $item) {
            $producto = $this->productoRepository->find($item['id']);
            if (!$producto) {
                return $this->json(['error' => 'Producto no encontrado: ' . $item['id']], JsonResponse::HTTP_NOT_FOUND);
            }

            $precioLinea = $producto->getPrecio() * $item['quantity'];

            $lineaPedido = new LineaPedido();
            $lineaPedido->setCantidad($item['quantity']);
            $lineaPedido->setPrecio($precioLinea);
            $lineaPedido->setProducto($producto);
            $lineaPedido->setPedido($pedido); // ✅ Ahora el pedido ya tiene un ID

            dump($lineaPedido); // 🔍 Verifica que el pedido se ha asignado correctamente


            $this->em->persist($lineaPedido);
        }

        $this->em->flush(); // 🔹 Guardamos las líneas de pedido correctamente

        // Limpiar el carrito después de hacer el pedido
        $session->remove('cart');

        return $this->json([
            'message' => 'Pedido creado correctamente',
            'id' => $pedido->getId(),
            'total' => $pagoTotal
        ], JsonResponse::HTTP_CREATED);
    }


}
