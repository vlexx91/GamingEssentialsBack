<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PerfilRepository;
#[Route('/perfil')]
class PerfilController extends AbstractController
{
    private PerfilRepository $perfilRepository;

    public function __construct(PerfilRepository $perfilRepository)
    {
        $this->perfilRepository = $perfilRepository;
    }

    #[Route('', name: 'app_perfil' , methods: ['GET'])]
    public function index(): Response
    {
        $perfil = $this->perfilRepository->findAll();

        return $this->json($perfil);
    }
}
