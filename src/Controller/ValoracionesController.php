<?php

namespace App\Controller;

use App\DTO\CrearValoracionesDTO;
use App\Entity\Producto;
use App\Entity\Usuario;
use App\Entity\Valoraciones;
use App\Repository\ValoracionesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/valoraciones')]
class ValoracionesController extends AbstractController
{
   private ValoracionesRepository $valoracionesRepository;
   private SerializerInterface $serializer;

    public function __construct(ValoracionesRepository $valoracionesRepository, SerializerInterface $serializer)
    {
         $this->valoracionesRepository = $valoracionesRepository;
        $this->serializer = $serializer;
    }

    /**
     * find all de valoraciones
     * @return Response
     */
    #[Route('/find', name: 'app_valoraciones' , methods: ['GET'])]
    public function index(): Response
    {
        $valoraciones = $this->valoracionesRepository->findAll();
        $json = $this->serializer->serialize($valoraciones, 'json', ['groups' => 'usuario']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * Muestra todas las valoraciones activadas de un producto.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/producto/{id}/activadas', name: 'valoraciones_activadas_por_producto', methods: ['GET'])]
    public function obtenerValoracionesActivadasPorProducto(int $id): JsonResponse
    {
        $valoraciones = $this->valoracionesRepository->findByProducto($id);

        if (empty($valoraciones)) {
            return $this->json(['message' => 'Producto no encontrado o sin valoraciones activadas'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($valoraciones, 'json', ['groups' => 'usuario']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * crea una valoracion
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/crear', name: 'valoraciones_crear', methods: ['POST'])]
    public function crearValoracion(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        if ($datos['estrellas'] < 1 || $datos['estrellas'] > 5) {
            return $this->json(['message' => 'La valoración debe estar entre 1 y 5 estrellas'], Response::HTTP_BAD_REQUEST);
        }

        $usuario = $em->getRepository(Usuario::class)->find($datos['id_usuario']);
        $producto = $em->getRepository(Producto::class)->find($datos['id_producto']);

        if (!$usuario || !$producto) {
            return $this->json(['message' => 'Usuario o Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $existingReview = $em->getRepository(Valoraciones::class)->findOneBy([
            'usuario' => $usuario,
            'producto' => $producto
        ]);

        if ($existingReview && $existingReview->getActivado()) {
            return $this->json(['message' => 'Ya has valorado este producto'], Response::HTTP_CONFLICT);
        }



        $valoracion = new Valoraciones();
        $valoracion->setEstrellas($datos['estrellas']);
        $valoracion->setComentario($datos['comentario']);
        $valoracion->setActivado(true);
        $valoracion->setUsuario($usuario);
        $valoracion->setProducto($producto);
        $valoracion->setFecha(new \DateTime());

        $em->persist($valoracion);
        $em->flush();

        return $this->json(['message' => 'Valoración creada correctamente'], Response::HTTP_CREATED);
    }

    /**
     * metodo eliminar valoracion
     *
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
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
     * metodo que crea promedio de las valoraciones en productos
     *
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/promedio/{id}', name: 'valoraciones_promedio_producto', methods: ['GET'])]
    public function calcularPromedioPorProducto(int $id, EntityManagerInterface $em): JsonResponse
    {
        $producto = $em->getRepository(Producto::class)->find($id);

        if (!$producto) {
            return $this->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $valoraciones = $em->getRepository(Valoraciones::class)->findBy(['producto' => $producto, 'activado' => true]);

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

    /**
     * metodo para editar una valoracion
     *
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/editar/{id}', name: 'valoraciones_editar', methods: ['PUT'])]
    public function editarValoracion(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        if (isset($datos['estrellas']) && ($datos['estrellas'] < 1 || $datos['estrellas'] > 5)) {
            return $this->json(['message' => 'La valoración debe estar entre 1 y 5 estrellas'], Response::HTTP_BAD_REQUEST);
        }

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

    /**
     * metodo para sacar top 5 productos mejor valorados
     *
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/topcinco', name: 'top_cinco', methods: ['GET'])]
    public function obtenerTopValorados(EntityManagerInterface $em): JsonResponse
    {
        $productos = $em->getRepository(Producto::class)->findAll();

        if (!$productos) {
            return $this->json(['message' => 'No hay productos disponibles'], Response::HTTP_NOT_FOUND);
        }

        $promedios = [];

        foreach ($productos as $producto) {
            $valoraciones = $em->getRepository(Valoraciones::class)->findBy(['producto' => $producto]);

            if (count($valoraciones) > 0) {
                $totalEstrellas = array_sum(array_map(fn($v) => $v->getEstrellas(), $valoraciones));
                $promedio = $totalEstrellas / count($valoraciones);

                $promedios[] = [
                    'producto' => [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'precio' => $producto->getPrecio(),
                        'descripcion' => $producto->getDescripcion(),
                        'imagen' => $producto->getImagen(),
                        'categoria' => $producto->getCategoria(),
                        'disponibilidad' => $producto->isDisponibilidad(),
                        'descuento' =>  $producto->getDescuento(),
                        'promedioValoraciones' => $promedio
                    ],
                    'promedio' => $promedio
                ];
            }
        }

        usort($promedios, fn($a, $b) => $b['promedio'] <=> $a['promedio']);

        $topProductos = array_slice($promedios, 0, 10);

        $topProductosLimpios = array_map(fn($p) => $p['producto'], $topProductos);

        return $this->json(['top_5_productos' => $topProductosLimpios], Response::HTTP_OK);
    }

    /**
     * metodo para cambiar el estado de una valoracion, solo esta disponible para gestores.
     *
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/desactivar/{id}', name: 'valoraciones_desactivar', methods: ['PUT'])]
    #[isGranted('ROLE_GESTOR')]
    public function cambiarEstadoValoracion(int $id, EntityManagerInterface $em): JsonResponse
    {
        $valoracion = $em->getRepository(Valoraciones::class)->find($id);

        if (!$valoracion) {
            return $this->json(['message' => 'Valoración no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $nuevoEstado = !$valoracion->getActivado();
        $valoracion->setActivado($nuevoEstado);
        $em->flush();

        $mensaje = $nuevoEstado ? 'Valoración activada correctamente' : 'Valoración desactivada correctamente';
        return $this->json(['message' => $mensaje], Response::HTTP_OK);
    }

    /**
     * metodo para ver todas las valoraciones, este metodo solo esta disponible para gestores.
     *
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/gestor/valoraciones', name: 'total_pedidos', methods: ['GET'])]
    #[isGranted('ROLE_GESTOR')]
    public function verTodosPedidos(SerializerInterface $serializer): JsonResponse {

        $valoraciones = $this->valoracionesRepository->findAll();

        $data = [];
        foreach ($valoraciones as $valoracion) {
            $data[] = [
                'id' => $valoracion->getId(),
                'estrellas' => $valoracion->getEstrellas(),
                'comentario' => $valoracion->getComentario(),
                'activado' => $valoracion->getActivado(),
                'producto' => $valoracion->getProducto()->getNombre(),
                'username' => $valoracion->getUsuario()->getUsername(),
                'userActivo' => $valoracion->getUsuario()->getActivo(),
            ];
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/mis-valoraciones', name: 'app_valoraciones_by_token', methods: ['GET'])]
    public function getValoracionesByToken(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager,
        ValoracionesRepository $valoracionesRepository
    ): JsonResponse {
        $token = $request->headers->get('Authorization');
        if (!$token) {
            return new JsonResponse(['message' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
        }

        $formatToken = str_replace('Bearer ', '', $token);

        try {
            $finalToken = $jwtManager->parse($formatToken);
            $username = $finalToken['username'] ?? null;
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        if (!$username) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        $user = $entityManager->getRepository(Usuario::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $valoraciones = $valoracionesRepository->findBy(
            ['usuario' => $user],
            ['id' => 'DESC']
        );

        if (empty($valoraciones)) {
            return new JsonResponse(['message' => 'No ratings found for this user'], Response::HTTP_OK);
        }

        $data = [];
        foreach ($valoraciones as $valoracion) {

            if (!$valoracion->getActivado()) {
                continue;
            }

            $data[] = [
                'id' => $valoracion->getId(),
                'estrellas' => $valoracion->getEstrellas(),
                'comentario' => $valoracion->getComentario(),
                'activado' => $valoracion->getActivado(),
                'producto' => [
                    'id' => $valoracion->getProducto()->getId(),
                    'nombre' => $valoracion->getProducto()->getNombre(),
                    'descripcion' => $valoracion->getProducto()->getDescripcion(),
                    'imagen' => $valoracion->getProducto()->getImagen(),
                    'precio' => $valoracion->getProducto()->getPrecio(),
                ],
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}/desactivar', name: 'app_desactivar_valoracion', methods: ['PATCH'])]
    public function desactivarValoracion(
        int $id,
        EntityManagerInterface $entityManager,
        ValoracionesRepository $valoracionesRepository
    ): JsonResponse {
        $valoracion = $valoracionesRepository->find($id);

        if (!$valoracion) {
            return new JsonResponse(['message' => 'Valoración no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $valoracion->setActivado(false);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Valoración desactivada con éxito'], Response::HTTP_OK);
    }

}
