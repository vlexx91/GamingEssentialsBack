<?php

namespace App\Controller;

use App\Repository\ValoracionesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/valoraciones')]
class ValoracionesController extends AbstractController
{
   private ValoracionesRepository $valoracionesRepository;

    public function __construct(ValoracionesRepository $valoracionesRepository)
    {
         $this->valoracionesRepository = $valoracionesRepository;
    }
    #[Route('', name: 'app_valoraciones' , methods: ['GET'])]
    public function index(): Response
    {
        $valoraciones = $this->valoracionesRepository->findAll();

        return $this->json($valoraciones);
    }

}
