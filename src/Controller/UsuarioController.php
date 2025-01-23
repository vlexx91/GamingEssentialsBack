<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UsuarioRepository;

#[Route('/usuario')]
class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    #[Route('', name: 'app_usuario' , methods: ['GET'])]
    public function index(): Response
    {
        $usuarios = $this->usuarioRepository->findAll();

        return $this->json($usuarios);
    }
}
