<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductoRepository;
#[Route('/producto')]

class ProductoController extends AbstractController
{
    private ProductoRepository $productoRepository;

    public function __construct(ProductoRepository $productoRepository)
    {
        $this->productoRepository = $productoRepository;
    }

    #[Route('', name: 'app_producto' , methods: ['GET'])]
    public function index(): Response
    {
        $productos = $this->productoRepository->findAll();

        return $this->json($productos);

    }
}
