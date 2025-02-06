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
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

require __DIR__ . '/../../vendor/autoload.php';


#[Route('/api/usuario')]
class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }


    #[Route('/registro', name: 'app_usuario1', methods: ["POST"])]
    public function registro(Request $request, EntityManagerInterface $em,
                             UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
//        $transport = Transport::fromDsn('smtp://gameessentialsteam@gmail.com:fupzrvwiatrfmrke@smtp.gmail.com:587');
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        $datos = json_decode($request->getContent(), true);

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setRol('ROLE_CLIENTE');


        $perfil = new Perfil();
        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellido']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));
        $perfil->setTelefono($datos['telefono']);

        $perfil->setUsuario($usuario);

        $codigoVerificacion = Uuid::uuid4()->toString(); // Genera un código único
        $usuario->setCodigoVerificacion($codigoVerificacion); // Guardar en la BD
        $usuario->setActivo(true); // Marcar usuario como inactivo hasta que verifique
        $usuario->setVerificado(false); // Marcar usuario como no verificado

        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();


        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();

        // Enviar correo electrónico
        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($usuario->getCorreo())
            ->subject('Registro exitoso, '.$perfil->getUsuario()->getUsername())
            ->text('¡Bienvenido!, '. $perfil->getNombre().' '. $perfil->getApellido().
                ' a nuestra plataforma de videojuegos. ¡Esperamos que disfrutes de una experiencia increíble y te diviertas mucho!, se te ha mandado un codigo de verificacion: '.$usuario->getCodigoVerificacion());

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Usuario creado, pero no se pudo enviar el correo: ' . $e->getMessage()], Response::HTTP_CREATED);
        }

        return $this->json(['message' => 'Usuario creado y correo enviado'], Response::HTTP_CREATED);
    }

    #[Route('/verificar', name: 'verificar_usuario', methods: ["POST"])]
    public function verificarCodigo(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);
        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $datos['username    ']]);

        if (!$usuario || $usuario->getCodigoVerificacion() !== $datos['codigo']) {
            return $this->json(['message' => 'Código incorrecto o usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setVerificado(true);
        $usuario->setCodigoVerificacion(null); // Limpia el código después de la verificación
        $em->flush();

        return $this->json(['message' => 'Usuario verificado exitosamente'], Response::HTTP_OK);
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
     * Mostrar Usuario y perfil con DTO
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
            $perfilCrearDTO->setTelefono($perfil->getTelefono());
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
    public function crearDto(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $crearUsuarioPerfil = new CrearUsuarioPerfilDTO();

        // Create new Usuario
        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['email']);
        $usuario->setRol('ROLE_CLIENTE');
        // Create new Perfil and associate with Usuario
        $perfil = new Perfil();
        $perfil->setNombre($datos['nombre']);
        $perfil->setApellido($datos['apellidos']);
        $perfil->setDireccion($datos['direccion']);
        $perfil->setDni($datos['dni']);
        $perfil->setFechaNacimiento(new \DateTime($datos['fechaNacimiento']));
        $perfil->setTelefono($datos['telefono']);

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


    #[Route('/editarGestor/{id}', name: 'usuario_editar_gestor', methods: ['PUT'])]
    public function editarGestor(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $usuario = $this->usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($usuario->getRol() !== Rol::GESTOR->value) {
            return $this->json(['message' => 'El usuario no es un gestor'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);

        $em->flush();

        return $this->json(['message' => 'Gestor editado correctamente'], Response::HTTP_OK);
    }

    #[Route('/gestores', name: 'usuario_listar_gestores', methods: ['GET'])]
    public function listarGestores(): JsonResponse
    {
        $gestores = $this->usuarioRepository->findBy(['rol' => Rol::GESTOR->value]);

        return $this->json($gestores, Response::HTTP_OK);
    }

    #[Route('/eliminarGestor/{id}', name: 'usuario_eliminar_gestor', methods: ['DELETE'])]
    public function eliminarGestor(int $id, EntityManagerInterface $em): JsonResponse
    {
        $usuario = $this->usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($usuario->getRol() !== Rol::GESTOR->value) {
            return $this->json(['message' => 'El usuario no es un gestor'], Response::HTTP_BAD_REQUEST);
        }

        $em->remove($usuario);
        $em->flush();

        return $this->json(['message' => 'Gestor eliminado correctamente'], Response::HTTP_OK);
    }


}
