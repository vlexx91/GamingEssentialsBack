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

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($producto);
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

        $producto = new Producto();
        $producto->setNombre($datos['nombre']);
        $producto->setDescripcion($datos['descripcion']);
        $producto->setDisponibilidad($datos['disponibilidad']);
        $producto->setPlataforma(Plataforma::from($datos['plataforma'])); // Convert to enum
        $producto->setPrecio($datos['precio']);
        $producto->setCategoria(Categoria::from($datos['categoria']));

        $em->persist($producto);
        $em->flush();

        return $this->json(['message' => 'Producto creado correctamente'], Response::HTTP_CREATED);
    }

    #[Route('/editar/{id}', name: 'app_producto_editar', methods: ['PUT'])]
    public function editarProducto(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $datos = json_decode($request->getContent(), true);

        if (isset($datos['nombre'])) {
            $producto->setNombre($datos['nombre']);
        }
        if (isset($datos['descripcion'])) {
            $producto->setDescripcion($datos['descripcion']);
        }
        if (isset($datos['disponibilidad'])) {
            $producto->setDisponibilidad($datos['disponibilidad']);
        }
        if (isset($datos['plataforma'])) {
            $producto->setPlataforma(Plataforma::from($datos['plataforma']));
        }
        if (isset($datos['precio'])) {
            $producto->setPrecio($datos['precio']);
        }
        if (isset($datos['categoria'])) {
            $producto->setCategoria(Categoria::from($datos['categoria']));
        }

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
}
