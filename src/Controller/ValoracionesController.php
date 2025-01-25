<?php

namespace App\Controller;

use App\DTO\CrearValoracionesDTO;
use App\Entity\Producto;
use App\Entity\Usuario;
use App\Entity\Valoraciones;
use App\Repository\ValoracionesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * crea una valoracion
     */
    #[Route('/crear', name: 'valoraciones_crear', methods: ['POST'])]
    public function crearValoracion(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $usuario = $em->getRepository(Usuario::class)->find($datos['id_usuario']);
        $producto = $em->getRepository(Producto::class)->find($datos['id_producto']);

        if (!$usuario || !$producto) {
            return $this->json(['message' => 'Usuario o Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $valoracion = new Valoraciones();
        $valoracion->setEstrellas($datos['estrellas']);
        $valoracion->setComentario($datos['comentario']);
        $valoracion->setUsuario($usuario);
        $valoracion->setProducto($producto);

        $em->persist($valoracion);
        $em->flush();

        return $this->json(['message' => 'Valoración creada correctamente'], Response::HTTP_CREATED);
    }

    /**
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * metodo eliminar valoracion por id de producto
     */

    #[Route('/eliminar/{id}', name: 'valoraciones_eliminar', methods: ['DELETE'])]
    public function eliminarValoracion(int $id, EntityManagerInterface $em): JsonResponse
    {
        $valoracion = $em->getRepository(Valoraciones::class)->find($id);

        if (!$valoracion) {
            return $this->json(['message' => 'Valoración no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($valoracion);
        $em->flush();

        return $this->json(['message' => 'Valoración eliminada correctamente'], Response::HTTP_OK);
    }

    /**
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * metodo que crea promedio de las valoraciones
     */
    #[Route('/promedio/{id}', name: 'valoraciones_promedio_producto', methods: ['GET'])]
    public function calcularPromedioPorProducto(int $id, EntityManagerInterface $em): JsonResponse
    {
        $producto = $em->getRepository(Producto::class)->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $valoraciones = $em->getRepository(Valoraciones::class)->findBy(['producto' => $producto]);

        if (count($valoraciones) === 0) {
            return $this->json(['message' => 'No hay valoraciones disponibles para este producto'], Response::HTTP_NOT_FOUND);
        }

        $totalEstrellas = 0;
        foreach ($valoraciones as $valoracion) {
            $totalEstrellas += $valoracion->getEstrellas();
        }

        $promedio = $totalEstrellas / count($valoraciones);

        return $this->json(['promedio' => $promedio], Response::HTTP_OK);
    }

    #[Route('/editar/{id}', name: 'valoraciones_editar', methods: ['PUT'])]
    public function editarValoracion(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $valoracion = $em->getRepository(Valoraciones::class)->find($id);

        if (!$valoracion) {
            return $this->json(['message' => 'Valoración no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if (isset($datos['estrellas'])) {
            $valoracion->setEstrellas($datos['estrellas']);
        }

        if (isset($datos['comentario'])) {
            $valoracion->setComentario($datos['comentario']);
        }

        $em->flush();

        return $this->json(['message' => 'Valoración editada correctamente'], Response::HTTP_OK);
    }

}
