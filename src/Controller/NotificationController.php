<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ListaDeseosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/notificaciones')]

class NotificationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function obtenerNotificaciones(ListaDeseosRepository $listaDeseosRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Obtener el usuario autenticado
        $usuario = $this->getUser();
        $idUsuario = $usuario->getId();

        // Obtener los juegos en la lista de deseos del usuario
        $listaDeseos = $listaDeseosRepository->findBy(['usuario' => $idUsuario]);

        $notificaciones = [];
        foreach ($listaDeseos as $deseo) {
            $producto = $entityManager->getRepository(Producto::class)->find($deseo->getProducto()->getId());
            if ($producto && $producto->isDisponibilidad()) {
                $notificaciones[] = [
                    'id' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'imagen' => $producto->getImagen(),
                    'mensaje' => "El juego '{$producto->getNombre()}' ya está disponible 🎮",
                ];
            }
        }

        return $this->json($notificaciones);
    }
}
