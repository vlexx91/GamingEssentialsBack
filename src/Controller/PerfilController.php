<?php

namespace App\Controller;

use App\DTO\CrearUsuarioPerfilDTO;
use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Enum\Rol;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PerfilRepository;
#[Route('/perfil')]
class PerfilController extends AbstractController
{
    private PerfilRepository $perfilRepository;

    public function __construct(PerfilRepository $perfilRepository)
    {
        $this->perfilRepository = $perfilRepository;
    }

    #[Route('', name: 'app_perfil' , methods: ['GET'])]
    public function index(): Response
    {
        $perfil = $this->perfilRepository->findAll();

        return $this->json($perfil);
    }

    #[Route('/crear', name: 'perfil_crear', methods: ['POST'])]
    public function crearDto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $crearUsuarioPerfil = new CrearUsuarioPerfilDTO();

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($datos['password']);
        $usuario->setCorreo($datos['email']);
        $usuario->setRol(Rol::CLIENTE);

        $perfil = new Perfil();
        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellidos']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));

        $perfil->setUsuario($usuario);


        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();

        return $this->json(['message' => 'Usuario y Perfil creados correctamente'], Response::HTTP_CREATED);
    }


}
