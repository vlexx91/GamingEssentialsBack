<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Enum\Plataforma;
use App\Enum\Categoria;
use App\Repository\LineaPedidoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductoRepository;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/producto')]
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

    #[Route('', name: 'app_producto', methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->productoRepository->findAll();
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/cliente', name: 'app_producto_cliente' , methods: ['GET'])]
    public function indexCliente(): Response
    {
        $productos = $this->productoRepository->findAvailableProducts();
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

    #[Route('/buscar/nombre/{nombre}', name: 'app_producto_buscar', methods: ['GET'])]
    public function buscarNombre(string $nombre): Response
    {
        $productos = $this->productoRepository->findByName($nombre);
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/buscar/plataforma/{platform}', name: 'app_producto_buscar_plataforma', methods: ['GET'])]
    public function buscarPlataforma(string $platform): Response
    {
        $productos = $this->productoRepository->findByPlatform($platform);
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/buscar/categoria/{category}', name: 'app_producto_buscar_categoria', methods: ['GET'])]
    public function buscarPorCategoria(string $category): Response
    {
        $productos = $this->productoRepository->findByCategory($category);
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/buscar/precio/{minPrice}/{maxPrice}', name: 'app_producto_buscar_precio', methods: ['GET'])]
    public function buscarPorRangoDePrecio(float $minPrice, float $maxPrice): Response
    {
        $productos = $this->productoRepository->findByPriceRange($minPrice, $maxPrice);
        $jsonContent = $this->serializer->serialize($productos, 'json', ['groups' => 'producto']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/crear', name: 'app_producto_crear', methods: ['POST'])]
    public function crearProducto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        if (!isset($datos['nombre'], $datos['descripcion'], $datos['precio'], $datos['categoria'], $datos['plataforma'], $datos['imagen'])) {
            return $this->json(['message' => 'Faltan datos obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        $producto = new Producto();
        $producto->setNombre($datos['nombre']);
        $producto->setDescripcion($datos['descripcion']);
        $producto->setDisponibilidad($datos['disponibilidad'] ?? true);
        $producto->setPlataforma(Plataforma::from($datos['plataforma']));
        $producto->setPrecio(floatval($datos['precio']));
        $producto->setCategoria(Categoria::from($datos['categoria']));
        $producto->setImagen($datos['imagen']); // Guarda la URL de la imagen

        $em->persist($producto);
        $em->flush();

        return $this->json(['message' => 'Producto creado correctamente'], Response::HTTP_CREATED);
    }


    #[Route('/editar/{id}', name: 'app_producto_editar', methods: ['POST'])]
    public function editarProducto(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $datos = $request->request->all();
        $archivo = $request->files->get('imagen');

        // Procesar la nueva imagen si existe
        if ($archivo) {
            if (!$archivo->isValid() || !in_array($archivo->getMimeType(), ['image/jpeg', 'image/png'])) {
                return $this->json(['message' => 'El archivo debe ser una imagen válida (JPEG o PNG)'], Response::HTTP_BAD_REQUEST);
            }

            // Borra la imagen anterior
            if ($producto->getImagen()) {
                $directorio = $this->getParameter('directorio_imagenes');
                $rutaImagenAnterior = $directorio . '/' . $producto->getImagen();
                if (file_exists($rutaImagenAnterior)) {
                    unlink($rutaImagenAnterior);
                }
            }

            // Guarda la nueva imagen
            $nombreImagen = uniqid('producto_', true) . '.' . $archivo->guessExtension();
            $directorio = $this->getParameter('directorio_imagenes');
            $archivo->move($directorio, $nombreImagen);
            $producto->setImagen($nombreImagen);
        }

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

        // Persistir y guardar los cambios
        $em->persist($producto);
        $em->flush();

        return $this->json(['message' => 'Producto editado correctamente'], Response::HTTP_OK);
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
        if ($totalProductos <= 10) {
            $productosAleatorios = $productos;
        } else {
            $productosAleatorios = [];
            $indicesSeleccionados = [];

            // Seleccionamos 10 índices aleatorios sin repetición
            while (count($productosAleatorios) < 10) {
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


}
