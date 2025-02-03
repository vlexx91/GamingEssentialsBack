<?php

namespace App\Controller;
use App\DTO\CrearUsuarioPerfilDTO;
use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Enum\Rol;
use App\Repository\PerfilRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/usuario')]
class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;
    private PerfilRepository $perfilRepository;

    public function __construct(UsuarioRepository $usuarioRepository, PerfilRepository $perfilRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->perfilRepository =$perfilRepository;

    }


    #[Route('/registro', name: 'app_usuario1', methods: ["POST"])]
    public function registro(Request $request, EntityManagerInterface $em,
                             UserPasswordHasherInterface $userPasswordHasher,): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol(1);

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Usuario creado'], Response::HTTP_CREATED);
    }



    #[Route('', name: 'app_usuario' , methods: ['GET'])]
    public function index(): Response
    {
        $usuarios = $this->usuarioRepository->findAll();

        return $this->json($usuarios);
    }

    #[Route('/crear', name: 'usuario_crear', methods: ['GET','POST'])]
    public function crear(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($datos['password']);
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol('ROLE_CLIENTE');

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Clase creada'], Response::HTTP_CREATED);
    }

    #[Route('/editar/{id}', name: 'usuario_editar', methods: ['PUT'])]
    public function editar(Request $request, EntityManagerInterface $em,
                           Usuario $usuario, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {

        $datos = json_decode($request->getContent(), true);


        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario,$datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol('ROLE_CLIENTE');

        $em->flush();

        return $this->json(['message' => 'Clase editada'], Response::HTTP_OK);
    }

    //No se puede liminar si no se elimina el perfil

    #[Route('/eliminar/{id}', name: 'usuario_eliminar', methods: ['DELETE'])]
    public function eliminar(Usuario $usuario, EntityManagerInterface $em): JsonResponse
    {


        // Remove the user
        $em->remove($usuario);
        $em->flush();


        return $this->json(['message' => 'Clase eliminada'], Response::HTTP_OK);
    }


    /**
     * Mostrar Usuarios y perfiles con DTOs
     *
     */

    #[Route('/mostrarDTO', name: 'usuario_mostrar', methods: ['GET'])]
    public function mostrarDto(PerfilRepository $perfilRepository): JsonResponse
    {
        $perfiles = $perfilRepository->findAll();
        $crearUsuarioPerfiles = [];

        foreach ($perfiles as $perfil){
            $perfilCrearDTO = new CrearUsuarioPerfilDTO();
            $perfilCrearDTO->setNombre($perfil->getNombre());
            $perfilCrearDTO->setApellidos($perfil->getApellido());
            $perfilCrearDTO->setDireccion($perfil->getDireccion());
            $perfilCrearDTO->setDni($perfil->getDni());
            $perfilCrearDTO->setFechaNacimiento($perfil->getFechaNacimiento());
            $perfilCrearDTO->setEmail($perfil->getUsuario()->getCorreo());
            $perfilCrearDTO->setUsername($perfil->getUsuario()->getUsername());
            $perfilCrearDTO->setPassword($perfil->getUsuario()->getPassword());
            $perfilCrearDTO->setRol($perfil->getUsuario()->getRol());

            $crearUsuarioPerfiles[] = $perfilCrearDTO;

        }
        return $this->json($crearUsuarioPerfiles, Response::HTTP_OK);

    }


    /**
     * Crear Usuario y Perfil con DTO
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \DateMalformedStringException
     */



    #[Route('/crearDTO', name: 'usuario_crear', methods: ['POST'])]
    public function crearDto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $crearUsuarioPerfil = new CrearUsuarioPerfilDTO();

        // Create new Usuario
        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($datos['password']);
        $usuario->setCorreo($datos['email']);
        $usuario->setRol('ROLE_CLIENTE');
        // Create new Perfil and associate with Usuario
        $perfil = new Perfil();
        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellidos']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));

        $perfil->setUsuario($usuario);


        // Persist both entities
        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();

        return $this->json(['message' => 'Usuario y Perfil creados correctamente'], Response::HTTP_CREATED);
    }



    /**
     * ADMINISTRADOR
     */


    #[Route('/crearAdmin', name: 'usuario_crear_admin', methods: ['POST'])]
    public function crearAdmin(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {

        $datos = json_decode($request->getContent(), true);

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol('ROLE_ADMIN');

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Admin creado'], Response::HTTP_CREATED);
    }


    /**
     * Crear Gestor teniendo el rol de administrador
     *
     */

    #[Route('/gestor/crear', name: 'usuario_crear_gestor', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function crearGestor(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {

        $datos = json_decode($request->getContent(), true);




        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol('ROLE_GESTOR');

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Gestor creado'], Response::HTTP_CREATED);
    }

    #[Route('/idToken', name: 'id_token', methods: ['GET'])]
    public function obtenerIdDesdeToken(Request $request, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager): JsonResponse {
        $token = $request->headers->get('authorization');
        if (!$token) {
            return new JsonResponse(['message' => 'No token provided'], 401);
        }

        $formatToken = str_replace('Bearer ', '', $token);
        $finalToken = $jwtManager->parse($formatToken);

        $username = $finalToken['username'] ?? null;
        if (!$username) {
            return new JsonResponse(['message' => 'Invalid token'], 403);
        }

        $user = $entityManager->getRepository(Usuario::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        return new JsonResponse(['user_id' => $user->getId()]);
    }

    /**
     * Mostrar Usuario y perfil con DTO
     *
     */

    #[Route('/mostrarPerfil', name: 'usuario_mostrar_uno', methods: ['GET'])]
    public function mostrarPerfil(Request $request, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager, PerfilRepository $perfilRepository): JsonResponse{
        $token = $request->headers->get('authorization');
        if (!$token) {
            return new JsonResponse(['message' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
        }

        $formatToken = str_replace('Bearer ', '', $token);
        $finalToken = $jwtManager->parse($formatToken);

        $username = $finalToken['username'] ?? null;
        if (!$username) {
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        $user = $entityManager->getRepository(Usuario::class)->findOneBy(['username' => $username]);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $perfil = $perfilRepository->findOneBy(['usuario' => $user->getId()]);
        if (!$perfil) {
            return new JsonResponse(['message' => 'Profile not found'], Response::HTTP_NOT_FOUND);
        }

        $perfilCrearDTO = new CrearUsuarioPerfilDTO();
        $perfilCrearDTO->setNombre($perfil->getNombre());
        $perfilCrearDTO->setApellidos($perfil->getApellido());
        $perfilCrearDTO->setDireccion($perfil->getDireccion());
        $perfilCrearDTO->setDni($perfil->getDni());
        $perfilCrearDTO->setFechaNacimiento($perfil->getFechaNacimiento());
        $perfilCrearDTO->setEmail($perfil->getUsuario()->getCorreo());
        $perfilCrearDTO->setUsername($perfil->getUsuario()->getUsername());
        $perfilCrearDTO->setPassword($perfil->getUsuario()->getPassword());
        $perfilCrearDTO->setRol($perfil->getUsuario()->getRol());

        return $this->json($perfilCrearDTO, Response::HTTP_OK);
    }


    #[Route('/RolToken', name: 'rol_token', methods: ['GET'])]
    public function obtenerRolDesdeToken(Request $request, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager): JsonResponse {
        $token = $request->headers->get('authorization');
        if (!$token) {
            return new JsonResponse(['message' => 'No token provided'], 401);
        }

        $formatToken = str_replace('Bearer ', '', $token);
        $finalToken = $jwtManager->parse($formatToken);

        $username = $finalToken['username'] ?? null;
        if (!$username) {
            return new JsonResponse(['message' => 'Invalid token'], 403);
        }

        $user = $entityManager->getRepository(Usuario::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        return new JsonResponse(['user_rol' => $user->getRol()]);
    }

//    private function convertRoleToString(int $role): string {
//        $roles = [
//            0 => 'ADMIN',
//            1 => 'CLIENTE',
//            2 => 'GESTOR',
//        ];
//
//        return $roles[$role] ?? 'Unknown';
//    }

}
