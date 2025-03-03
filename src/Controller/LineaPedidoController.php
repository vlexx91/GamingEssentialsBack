<?php

namespace App\Controller;

use App\Repository\LineaPedidoRepository;
use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/linea_pedido')]
class LineaPedidoController extends AbstractController
{
    private LineaPedidoRepository $lineaPedidoRepository;
    private ProductoRepository $productoRepository;


    public function __construct(LineaPedidoRepository $lineaPedidoRepository, ProductoRepository $productoRepository)
    {
        $this->lineaPedidoRepository = $lineaPedidoRepository;
        $this->productoRepository = $productoRepository;
    }

    /**
     * Find all LineaPedidos
     * @return Response
     */
    #[Route('', name: 'app_linea_pedido', methods: ['GET'])]
    public function index(): Response
    {
       $lineaPedido = $this->lineaPedidoRepository->findAll();

        return $this->json($lineaPedido);
    }

    #[Route('/producto/{id}', name: 'linea_pedido_by_producto', methods: ['GET'])]
    public function findByProducto(int $id, SerializerInterface $serializer): JsonResponse
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            return $this->json(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $lineasPedido = $this->lineaPedidoRepository->findByProducto($producto);

        if (empty($lineasPedido)) {
            return $this->json(['message' => 'No hay líneas de pedido asociadas a este producto'], Response::HTTP_OK);
        }

        return $this->json($lineasPedido, Response::HTTP_OK);
    }

}
