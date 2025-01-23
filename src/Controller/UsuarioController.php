<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UsuarioRepository;

#[Route('/usuario')]
class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;
    private EntityManagerInterface $entityManager;


    public function __construct(UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_usuario' , methods: ['GET'])]
    public function index(): Response
    {
        $usuarios = $this->usuarioRepository->findAll();

        return $this->json($usuarios);
    }


    #[Route('/{id}', name: 'app_usuario_findbyid', methods: ['GET'])]
    public function findById(int $id): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $usuario = $this->usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($usuario);
    }

    #[Route('/eliminar/{id}', name: 'app_usuario_delete', methods: ['DELETE'])]
    public function deleteById(int $id): JsonResponse
    {
        // Busca el usuario en la base de datos
        $usuario = $this->usuarioRepository->find($id);

        // Si no se encuentra el usuario, devuelve un error
        if (!$usuario) {
            return $this->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Elimina el usuario de la base de datos
        $this->entityManager->remove($usuario);
        $this->entityManager->flush();

        // Devuelve una respuesta exitosa
        return $this->json(['message' => 'Usuario eliminado exitosamente'], Response::HTTP_OK);
    }

}
