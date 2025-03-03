<?php

namespace App\Controller;

use App\DTO\CrearUsuarioPerfilDTO;
use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Enum\Rol;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PerfilRepository;
#[Route('/api/perfil')]
class PerfilController extends AbstractController
{
    private PerfilRepository $perfilRepository;

    public function __construct(PerfilRepository $perfilRepository)
    {
        $this->perfilRepository = $perfilRepository;
    }

    /**
     * Find all Perfiles
     * @return Response
     */
    #[Route('', name: 'app_perfil' , methods: ['GET'])]
    public function index(): Response
    {
        $perfil = $this->perfilRepository->findAll();

        return $this->json($perfil);
    }

    /**
     * Crear un perfil y un usuario
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \DateMalformedStringException
     */
    #[Route('/crear', name: 'perfil_crear', methods: ['POST'])]
    public function crearDto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $crearUsuarioPerfil = new CrearUsuarioPerfilDTO();

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($datos['password']);
        $usuario->setCorreo($datos['email']);
        $usuario->setRol('ROLE_CLIENTE');

        $perfil = new Perfil();
        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellidos']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));
        $perfil->setTelefono($datos['telefono']);

        $perfil->setUsuario($usuario);


        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();

        return $this->json(['message' => 'Usuario y Perfil creados correctamente'], Response::HTTP_CREATED);
    }

    /**
     * editar un perfil y un usuario por el id
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param PerfilRepository $perfilRepository
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     * @throws \DateMalformedStringException
     */

    #[Route('/editar/{id}', name: 'perfil_editar', methods: ['PUT'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, PerfilRepository $perfilRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $perfil = $perfilRepository->find($id);

        if (!$perfil) {
            return $this->json(['message' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $datos = json_decode($request->getContent(), true);

        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellidos']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));
        $perfil->setTelefono($datos['telefono']);

        $usuario = $perfil->getUsuario();

        if (!$usuario) {
            return $this->json(['message' => 'El perfil no tiene un usuario asociado'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setUsername($datos['username']);
        $usuario->setCorreo($datos['email']);
        $usuario->setRol('ROLE_CLIENTE');

        if (!empty($datos['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($usuario, $datos['password']);
            $usuario->setPassword($hashedPassword);
        }

        $em->flush();

        return $this->json(['message' => 'Perfil y Usuario actualizados correctamente'], Response::HTTP_OK);
    }

    //Editar a través del token

    #[Route('/editarportoken', name: 'perfil_editar_token', methods: ['PUT'])]
    public function editByToken(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $em,
        PerfilRepository $perfilRepository
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

        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $username]);

        if (!$usuario) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $perfil = $perfilRepository->findOneBy(['usuario' => $usuario]);

        if (!$perfil) {
            return new JsonResponse(['message' => 'Profile not found'], Response::HTTP_NOT_FOUND);
        }

        $datos = json_decode($request->getContent(), true);

        // Validar que los datos requeridos existen antes de asignarlos
        if (!empty($datos['nombre'])) {
            $perfil->setNombre($datos['nombre']);
        }
        if (!empty($datos['apellidos'])) {
            $perfil->setApellido($datos['apellidos']);
        }
        if (!empty($datos['direccion'])) {
            $perfil->setDireccion($datos['direccion']);
        }
        if (!empty($datos['telefono'])) {
            $perfil->setTelefono($datos['telefono']);
        }
        if (!empty($datos['dni'])) {
            $perfil->setDni($datos['dni']);
        }
        if (!empty($datos['imagenUrl'])) {
            $perfil->setImagen($datos['imagenUrl']); // Nuevo campo para la imagen
        }

        // Guardamos los cambios en la base de datos
        $em->flush();

        return $this->json(['message' => 'Perfil actualizado correctamente'], Response::HTTP_OK);
    }


    /**
     * Buscar un perfil por el id
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'perfil_buscar', methods: ['GET'])]
    public function buscarPorId(int $id): JsonResponse
    {
        $perfil = $this->perfilRepository->find($id);

        if (!$perfil) {
            return $this->json(['message' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($perfil, Response::HTTP_OK);
    }
}
