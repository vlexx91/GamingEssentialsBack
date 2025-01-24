<?php

namespace App\Controller;
use App\DTO\CrearUsuarioPerfilDTO;
use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Enum\Rol;
use App\Repository\PerfilRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/usuario')]
class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;
    private PerfilRepository $perfilRepository;

    public function __construct(UsuarioRepository $usuarioRepository, PerfilRepository $perfilRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->perfilRepository =$perfilRepository;

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
        $usuario->setRol(Rol::CLIENTE);

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Clase creada'], Response::HTTP_CREATED);
    }

    #[Route('/editar/{id}', name: 'usuario_editar', methods: ['PUT'])]
    public function editar(Request $request, EntityManagerInterface $em, Usuario $usuario): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);


        $usuario->setUsername($datos['username']);
        $usuario->setPassword($datos['password']);
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol(Rol::CLIENTE);

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
     * Mostrar Usuario y perfil con DTO
     *
     */

    #[Route('/mostrarDTO', name: 'usuario_mostrar', methods: ['GET'])]
    public function mostrarDto(PerfilRepository $perfilRepository): JsonResponse
    {
        $perfiles = $perfilRepository->findAll();
//        $usuarios = $this->usuarioRepository->findAll();
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
        $usuario->setRol(Rol::CLIENTE);
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






}
