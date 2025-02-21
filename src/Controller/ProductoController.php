<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Entity\Usuario;
use App\Enum\Plataforma;
use App\Enum\Categoria;
use App\Repository\LineaPedidoRepository;
use App\Repository\ListaDeseosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductoRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/producto')]
class ProductoController extends AbstractController
{
    private ProductoRepository $productoRepository;
    private LineaPedidoRepository $lineaPedidoRepository;
    private SerializerInterface $serializer;

    public function __construct(ProductoRepository $productoRepository, LineaPedidoRepository $lineaPedidoRepository, SerializerInterface $serializer)
    {
        $this->productoRepository = $productoRepository;
        $this->lineaPedidoRepository = $lineaPedidoRepository;
        $this->serializer = $serializer;
    }

    #[Route('/gestor/mostrar', name: 'app_producto', methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->productoRepository->findAll();
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/cliente', name: 'app_producto_cliente' , methods: ['GET'])]
    public function indexCliente(): Response
    {
        $productos = $this->productoRepository->findAll();
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);

    }

    #[Route('/detalle/{id}', name: 'app_producto_detalle', methods: ['GET'])]
    public function productoInfo(int $id): Response
    {
        $producto = $this->productoRepository->findById($id);
        $jsonContent = $this->serializer->serialize($producto, 'json', ['groups' => 'producto']);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/buscar', name: 'app_producto_buscar', methods: ['GET'])]
    public function buscar(Request $request): Response
    {
        $nombre = $request->query->get('nombre');
        $plataforma = $request->query->get('plataforma');
        $categoria = $request->query->get('categoria');
        $minPrecio = $request->query->get('minPrecio');
        $maxPrecio = $request->query->get('maxPrecio');

        $criterios = array_filter([
            'nombre' => $nombre,
            'plataforma' => $plataforma,
            'categoria' => $categoria,
            'minPrecio' => $minPrecio,
            'maxPrecio' => $maxPrecio,
        ]);

        $productos = $this->productoRepository->findByCriteria($criterios);
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

//    #[Route('/crear', name: 'app_producto_crear', methods: ['POST'])]
//    public function crearProducto(Request $request, EntityManagerInterface $em): JsonResponse
//    {
//        $datos = json_decode($request->getContent(), true);
//
//        if (!isset($datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'], $datos['plataforma'], $datos['imagen'])) {
//            return $this->json(['message' => 'Faltan datos obligatorios'], Response::HTTP_BAD_REQUEST);
//        }
//
//        $codigoJuego = Uuid::uuid4()->toString();
//
//        $producto = new Producto();
//        $producto->setNombre($datos['nombre']);
//        $producto->setDescripcion($datos['descripcion']);
//        $producto->setDisponibilidad($datos['disponibilidad'] ?? true);
//        $producto->setPlataforma(Plataforma::from($datos['plataforma']));
//        $producto->setPrecio(floatval($datos['precio']));
//        $producto->setCategoria(Categoria::from($datos['categoria']));
//        $producto->setCodigoJuego($codigoJuego);
//        $producto->setImagen($datos['imagen']);
//
//        $em->persist($producto);
//        $em->flush();
//
//        return $this->json(['message' => 'Producto creado correctamente'], Response::HTTP_CREATED);
//    }


    #[Route('/gestor/editar/{id}', name: 'app_producto_editar', methods: ['PUT'])]
    #[IsGranted('ROLE_GESTOR')]
    public function editarProducto( Request $request, EntityManagerInterface $em, Producto $producto, ListaDeseosRepository $listaDeseosRepository): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $disponibilidadAnterior = $producto->isDisponibilidad();

        // Actualizar los campos del producto
        if (isset($datos['nombre'])) {
            $producto->setNombre($datos['nombre']);
        }
        if (isset($datos['descripcion'])) {
            $producto->setDescripcion($datos['descripcion']);
        }
        if (isset($datos['disponibilidad'])) {
            $producto->setDisponibilidad(filter_var($datos['disponibilidad'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($datos['plataforma'])) {
            $producto->setPlataforma(Plataforma::from($datos['plataforma']));
        }
        if (isset($datos['precio'])) {
            $producto->setPrecio(floatval($datos['precio']));
        }
        if (isset($datos['categoria'])) {
            $producto->setCategoria(Categoria::from($datos['categoria']));
        }
        if (isset($datos['imagen'])) {
            $producto->setImagen($datos['imagen']);
        }

        // Persistir y guardar los cambios
//        $em->persist($producto);
        $em->flush();

        if (!$disponibilidadAnterior && $producto->isDisponibilidad()) {
            $usuariosInteresados = $listaDeseosRepository->findUsuariosPorProducto($producto->getId());

            foreach ($usuariosInteresados as $usuario) {
                $this->enviarNotificacion($usuario, $producto);
            }
        }

        return $this->json(['message' => 'Producto editado correctamente'], Response::HTTP_OK);

    }

    private function enviarNotificacion($usuario, $producto)
    {
        $httpClient = HttpClient::create();

        try {
            $response = $httpClient->request('POST', 'http://127.0.0.1:8000/send-product-notification', [
                'json' => [
                    'email' => $usuario->getCorreo(), // Debería ser un array con 'email'
                    'productName' => $producto->getNombre()
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception('Error en la notificación');
            }

        } catch (\Exception $e) {
            error_log("Error enviando notificación a {$usuario->getCorreo()}: " . $e->getMessage());
        }
    }

    #[Route('/eliminar/{id}', name: 'app_producto_eliminar', methods: ['DELETE'])]
    public function eliminarProducto(int $id, EntityManagerInterface $em): JsonResponse
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $lineasPedidos = $this->lineaPedidoRepository->findBy(['producto' => $producto]);

        if (count($lineasPedidos) > 0) {
            return $this->json(['message' => 'No se puede eliminar el producto porque está asociado a uno o más pedidos'], Response::HTTP_CONFLICT);
        }

        $em->remove($producto);
        $em->flush();

        return $this->json(['message' => 'Producto eliminado correctamente'], Response::HTTP_OK);
    }


    #[Route('/aleatorios', name: 'app_producto_cliente_random' , methods: ['GET'])]
    public function indexClienteRandom(): Response
    {
        $productos = $this->productoRepository->findAvailableProducts();

        // Si hay menos de 10 productos, se devuelven todos
        $totalProductos = count($productos);
        if ($totalProductos <= 15) {
            $productosAleatorios = $productos;
        } else {
            $productosAleatorios = [];
            $indicesSeleccionados = [];

            // Seleccionamos 10 índices aleatorios sin repetición
            while (count($productosAleatorios) < 15) {
                $indice = random_int(0, $totalProductos - 1);
                if (!in_array($indice, $indicesSeleccionados)) {
                    $indicesSeleccionados[] = $indice;
                    $productosAleatorios[] = $productos[$indice];
                }
            }
        }

        $jsonContent = $this->serializer->serialize($productosAleatorios, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/masvendidos', name: 'productos_mas_vendidos', methods: ['GET'])]
    public function productosMasVendidos(ProductoRepository $productoRepository): JsonResponse
    {
        $productos = $productoRepository->findTop5MasVendidos();

        return $this->json($productos);
    }

    #[Route('/gestor/crear', name: 'app_producto_crear_gestor', methods: ['POST'])]
    #[IsGranted('ROLE_GESTOR')]
    public function crearProductoGestor(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $headers = $request->headers->all();


        if (!isset($datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'], $datos['plataforma'], $datos['imagen'])) {
            return $this->json(['message' => 'Faltan datos obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        $codigoJuego = Uuid::uuid4()->toString();


        $producto = new Producto();
        $producto->setNombre($datos['nombre']);
        $producto->setDescripcion($datos['descripcion']);
        $producto->setDisponibilidad($datos['disponibilidad'] ?? true);
        $producto->setPlataforma(Plataforma::from($datos['plataforma']));
        $producto->setPrecio(floatval($datos['precio']));
        $producto->setCategoria(Categoria::from($datos['categoria']));
        $producto->setCodigoJuego($codigoJuego);
        $producto->setImagen($datos['imagen']); // Guarda la URL de la imagen

        $em->persist($producto);
        $em->flush();

        return $this->json(['header' => $headers]);

    }

    // src/Controller/ProductoController.php

    #[Route('/gestor/descuento/{id}', name: 'app_producto_descuento', methods: ['PUT'])]
    #[IsGranted('ROLE_GESTOR')]
    public function crearDescuento(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $datos = json_decode($request->getContent(), true);

        if (!isset($datos['descuento']) || !is_numeric($datos['descuento'])) {
            return $this->json(['message' => 'El descuento debe ser un número válido'], Response::HTTP_BAD_REQUEST);
        }

        $descuento = floatval($datos['descuento']);

        if ($descuento < 0 || $descuento > 100) {
            return $this->json(['message' => 'El descuento debe estar entre 0 y 100'], Response::HTTP_BAD_REQUEST);
        }

        if ($producto->getDescuento() === $descuento) {
            return $this->json(['message' => 'El producto ya tiene este descuento aplicado'], Response::HTTP_OK);
        }

        $producto->setDescuento($descuento);
        $em->flush();

        $precioConDescuento = $producto->getPrecio() * (1 - $descuento / 100);

        return $this->json([
            'message' => 'Descuento aplicado correctamente',
            'precio_final' => round($precioConDescuento, 2)
        ], Response::HTTP_OK);
    }



    #[Route('/test', name: 'app_producto_test', methods: ['GET'])]
    public function test(): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();


        return $this->json(['user' => $usuario]);
    }





}
