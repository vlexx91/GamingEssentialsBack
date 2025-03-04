<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ListaDeseos;
use App\Entity\Producto;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/lista-deseos')]
class ListaDeseosController extends AbstractController
{
    /**
     * Metodo que agrega un producto a la lista de deseados
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTTokenManagerInterface $jwtManager
     * @return JsonResponse
     */

    #[Route('/agregar', name: 'agregar_lista_deseos', methods: ['POST'])]
    public function agregar(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
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

        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $username]);
        if (!$usuario) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $producto = $em->getRepository(Producto::class)->find($data['id_producto']);

        if (!$producto) {
            return new JsonResponse(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $existingWishlist = $em->getRepository(ListaDeseos::class)->findOneBy([
            'usuario' => $usuario,
            'producto' => $producto,
        ]);

        if ($existingWishlist) {
            return new JsonResponse(['message' => 'Este producto ya está en tu lista de deseos'], Response::HTTP_CONFLICT);
        }

        $listaDeseos = new ListaDeseos();
        $listaDeseos->setUsuario($usuario);
        $listaDeseos->setProducto($producto);
        $em->persist($listaDeseos);
        $em->flush();

        return new JsonResponse(['message' => 'Producto agregado a la lista de deseos'], Response::HTTP_CREATED);
    }

    /**
     * Metodo que elimina un producto de la lista de deseados
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTTokenManagerInterface $jwtManager
     * @return JsonResponse
     */
    #[Route('/eliminar', name: 'eliminar_lista_deseos', methods: ['DELETE'])]
    public function eliminar(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
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

        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $username]);
        if (!$usuario) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $producto = $em->getRepository(Producto::class)->find($data['id_producto']);

        if (!$producto) {
            return new JsonResponse(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $listaDeseos = $em->getRepository(ListaDeseos::class)->findOneBy([
            'usuario' => $usuario,
            'producto' => $producto
        ]);

        if (!$listaDeseos) {
            return new JsonResponse(['message' => 'Producto no está en la lista de deseos'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($listaDeseos);
        $em->flush();

        return new JsonResponse(['message' => 'Producto eliminado de la lista de deseos'], Response::HTTP_OK);
    }


    /**
     * Metodo que muestra toda la lista de deseos asociada a un usuario a traves del token
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTTokenManagerInterface $jwtManager
     * @return JsonResponse
     */

    #[Route('/listar', name: 'listar_lista_deseos', methods: ['GET'])]
    public function listar(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager, Security $security): JsonResponse
    {
        $usuario = $security->getUser();

        if (!$usuario->getActivo()) {
            return $this->json(['message' => 'Usuario inactivo'], Response::HTTP_FORBIDDEN);
        }

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

        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $username]);
        if (!$usuario) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $listaDeseos = $em->getRepository(ListaDeseos::class)->findBy(['usuario' => $usuario]);

        $productos = array_map(fn($deseo) => [
            'id' => $deseo->getProducto()->getId(),
            'nombre' => $deseo->getProducto()->getNombre(),
            'precio' => $deseo->getProducto()->getPrecio(),
            'imagen' => $deseo->getProducto()->getImagen()
        ], $listaDeseos);

        return new JsonResponse($productos, Response::HTTP_OK);
    }
}
