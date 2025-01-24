<?php

namespace App\Controller;

use App\Repository\PedidoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pedido')]
class PedidoController extends AbstractController
{
    private PedidoRepository $pedidoRepository;

    public function __construct(PedidoRepository $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    #[Route('', name: 'app_pedido')]
    public function index(): Response
    {
       $pedido = $this->pedidoRepository->findAll();

        return $this->json($pedido);
    }
}
