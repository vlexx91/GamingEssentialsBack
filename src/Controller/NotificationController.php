<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ListaDeseosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/notificaciones')]

class NotificationController extends AbstractController
{
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Notificaciones de productos disponibles que esten en la lista de deseos del usuario
     * @param ListaDeseosRepository $listaDeseosRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('', name: 'notificaciones_1', methods: ['GET'])]
    public function obtenerNotificaciones(ListaDeseosRepository $listaDeseosRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $usuario = $this->getUser();
        if (!$usuario) {
            return $this->json(['message' => 'Usuario no autenticado']);
        }

        $idUsuario = $usuario->getId();
        $listaDeseos = $listaDeseosRepository->findBy(['usuario' => $idUsuario]);

        $notificaciones = [];
        foreach ($listaDeseos as $deseo) {
            if ($deseo->isNotificacion()) {
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
        }

        return $this->json($notificaciones);
    }

    /**
     * Eliminar notificación de un producto (cambiar estado de notificación a false)
     * @param int $idProducto
     * @param ListaDeseosRepository $listaDeseosRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/{idProducto}', name: 'eliminar_notificaciones',methods: ['PUT'])]
    public function eliminarNotificacion(int $idProducto, ListaDeseosRepository $listaDeseosRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $usuario = $this->getUser();
        $idUsuario = $usuario->getId();

        $deseo = $listaDeseosRepository->findOneBy(['usuario' => $idUsuario, 'producto' => $idProducto]);

        if ($deseo) {
            $deseo->setNotificacion(false);
            $entityManager->flush();

            return $this->json(['mensaje' => 'Notificación actualizada correctamente.'], JsonResponse::HTTP_OK);
        }

        return $this->json(['mensaje' => 'Notificación no encontrada.'], JsonResponse::HTTP_NOT_FOUND);
    }
}
