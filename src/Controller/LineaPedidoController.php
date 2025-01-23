<?php

namespace App\Controller;

use App\Repository\LineaPedidoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/linea_pedido')]
class LineaPedidoController extends AbstractController
{
    private LineaPedidoRepository $lineaPedidoRepository;

    public function __construct(LineaPedidoRepository $lineaPedidoRepository)
    {
        $this->lineaPedidoRepository = $lineaPedidoRepository;
    }

    #[Route('', name: 'app_linea_pedido', methods: ['GET'])]
    public function index(): Response
    {
       $lineaPedido = $this->lineaPedidoRepository->findAll();

        return $this->json($lineaPedido);
    }
}
