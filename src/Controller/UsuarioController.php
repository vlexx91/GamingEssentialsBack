<?php

namespace App\Controller;
use App\DTO\CrearUsuarioPerfilDTO;
use App\Entity\LineaPedido;
use App\Entity\Pedido;
use App\Entity\Perfil;
use App\Entity\Usuario;
use App\Enum\Rol;
use App\Repository\PedidoRepository;
use App\Repository\PerfilRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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


    /**
     * Registro de perfil y usuario con la validacion de la edad, dni, telefono y correo. Se envia un correo de verificacion al resgitrarse
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     * @throws \DateMalformedStringException
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     *
     */
    #[Route('/registro', name: 'app_usuario1', methods: ["POST"])]
    public function registro(Request $request, EntityManagerInterface $em,
                             UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        $datos = json_decode($request->getContent(), true);

        $existingUser = $em->getRepository(Usuario::class)->findOneBy(['username' => $datos['username']]);
        if ($existingUser) {
            return $this->json(['message' => 'El nombre de usuario ya está en uso'], Response::HTTP_CONFLICT);
        }

        $existingEmail = $em->getRepository(Usuario::class)->findOneBy(['correo' => $datos['correo']]);
        if ($existingEmail) {
            return $this->json(['message' => 'El correo ya está en uso'], Response::HTTP_CONFLICT);
        }

        $existingDni = $em->getRepository(Perfil::class)->findOneBy(['dni' => $datos['dni']]);
        if ($existingDni) {
            return $this->json(['message' => 'El dni ya está en uso'], Response::HTTP_CONFLICT);
        }

        $existingTelefono = $em->getRepository(Perfil::class)->findOneBy(['telefono' => $datos['telefono']]);
        if ($existingTelefono) {
            return $this->json(['message' => 'El telefono ya está en uso'], Response::HTTP_CONFLICT);
        }

        $fechaNacimiento = new \DateTime($datos['fechaNacimiento']);
        $hoy = new \DateTime();
        $edad = $hoy->diff($fechaNacimiento)->y;

        if ($edad < 12) {
            return $this->json(['message' => 'Debes tener al menos 12 años para registrarte'], Response::HTTP_BAD_REQUEST);
        }
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
        $perfil->setFechaNacimiento($fechaNacimiento);
        $perfil->setTelefono($datos['telefono']);

        $perfil->setUsuario($usuario);

        $codigoVerificacion = Uuid::uuid4()->toString();
        $usuario->setCodigoVerificacion($codigoVerificacion);
        $usuario->setActivo(true);
        $usuario->setVerificado(false);

        $em->persist($usuario);
        $em->persist($perfil);
        $em->flush();


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

    /**
     * Verificar el codigo de verificacion tras el registro de un usuario
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/verificar', name: 'verificar_usuario', methods: ["POST"])]
    public function verificarCodigo(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);
        $usuario = $em->getRepository(Usuario::class)->findOneBy(['username' => $datos['username']]);

        if (!$usuario || $usuario->getCodigoVerificacion() !== $datos['codigo']) {
            return $this->json(['message' => 'Código incorrecto o usuario no encontrado'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setVerificado(true);
        $usuario->setCodigoVerificacion(null);
        $em->flush();

        return $this->json(['message' => 'Usuario verificado exitosamente'], Response::HTTP_OK);
    }

    /**
     * Find all de los usuarios
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     */

    #[Route('', name: 'app_usuario' , methods: ['GET'])]
    public function index(): Response
    {
        $usuarios = $this->usuarioRepository->findAll();

        return $this->json($usuarios);
    }

    /**
     * Crear un usuario
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */
    #[Route('/crear', name: 'usuario_crear', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
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

    /**
     * Editar un usuario por el id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Usuario $usuario
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */
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

    /**
     * Eliminar un usuario por el id
     * @param Usuario $usuario
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/eliminar/{id}', name: 'usuario_eliminar', methods: ['DELETE'])]
    public function eliminar(Usuario $usuario, EntityManagerInterface $em): JsonResponse
    {


        // Remove the user
        $em->remove($usuario);
        $em->flush();


        return $this->json(['message' => 'Clase eliminada'], Response::HTTP_OK);
    }

    /**
     * Mostrar la DTO de usuario y perfil
     * @param PerfilRepository $perfilRepository
     * @return JsonResponse
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

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
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
     * ADMINISTRADOR
     */

    /**
     * Crear un admin
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */

    #[Route('/crearAdmin', name: 'usuario_crear_admin', methods: ['POST'])]
    public function crearAdmin(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {

        $datos = json_decode($request->getContent(), true);

        $usuario = new Usuario();
        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);
        $usuario->setVerificado(true);
        $usuario->setActivo(true);
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
        $perfilCrearDTO->setTelefono($perfil->getTelefono());
        $perfilCrearDTO->setImagen($perfil->getImagen());


        return $this->json($perfilCrearDTO, Response::HTTP_OK);
    }

    /**
     * Muestra el rol del usuario a partir del token del usuario
     * @param Request $request
     * @param JWTTokenManagerInterface $jwtManager
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
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
     * Crea un gestor siendo administrador
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
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
        $usuario->setActivo(true);
        $usuario->setVerificado(true);
        $usuario->setRol('ROLE_GESTOR');

        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Gestor creado'], Response::HTTP_CREATED);
    }


    /**
     * editar un gestor siendo administrador
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */
    #[Route('/editarGestor/{id}', name: 'usuario_editar_gestor', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editarGestor(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        $usuario = $this->usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($usuario->getRol() !== 'ROLE_GESTOR') {
            return $this->json(['message' => 'El usuario no es un gestor'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setUsername($datos['username']);
        $usuario->setPassword($userPasswordHasher->hashPassword($usuario, $datos['password']));
        $usuario->setCorreo($datos['correo']);

        $em->flush();

        return $this->json(['message' => 'Gestor editado correctamente'], Response::HTTP_OK);
    }

    /**
     * Muestre los gestores que existen sin bucle infinito
     * @return JsonResponse
     */
    #[Route('/gestores', name: 'usuario_listar_gestores', methods: ['GET'])]
    public function listarGestores(): JsonResponse
    {
        $gestores = $this->usuarioRepository->findBy(['rol' => 'ROLE_GESTOR']);

        return $this->json($gestores, Response::HTTP_OK, [], [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);
    }


    /**
     * Eliminar un gestor siendo administrador
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/eliminarGestor/{id}', name: 'usuario_eliminar_gestor', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function eliminarGestor(int $id, EntityManagerInterface $em): JsonResponse
    {
        $usuario = $this->usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($usuario->getRol() !== 'ROLE_GESTOR') {
            return $this->json(['message' => 'El usuario no es un gestor'], Response::HTTP_BAD_REQUEST);
        }

        $em->remove($usuario);
        $em->flush();

        return $this->json(['message' => 'Gestor eliminado correctamente'], Response::HTTP_OK);
    }


    /**
     * Listar todos los perfiles siendo gestor
     * @param PerfilRepository $perfilRepository
     * @return JsonResponse
     */

    #[Route('/gestor/perfiles', name: 'listar_perfiles', methods: ['GET'])]
    public function listarPerfiles(PerfilRepository $perfilRepository): JsonResponse
    {
        $perfiles = $perfilRepository->findAll();
        $result = [];

        foreach ($perfiles as $perfil) {
            $result[] = [
                'id' => $perfil->getId(),
                'nombre' => $perfil->getNombre(),
                'apellido' => $perfil->getApellido(),
                'direccion' => $perfil->getDireccion(),
                'dni' => $perfil->getDni(),
                'fecha_nacimiento' => $perfil->getFechaNacimiento()->format('Y-m-d'),
                'telefono' => $perfil->getTelefono(),
                'username' => $perfil->getUsuario()->getUsername(),
                'correo' => $perfil->getUsuario()->getCorreo(),
                'userId' => $perfil->getUsuario()->getId(),
                'imagen'=> $perfil->getImagen(),
            ];
        }

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Listar un perfil por id siendo gestor
     * @param int $id
     * @param PerfilRepository $perfilRepository
     * @return JsonResponse
     */
    #[Route('/gestor/perfiles/{id}', name: 'listar_perfil_por_id', methods: ['GET'])]
    public function listarPerfilPorId(int $id, PerfilRepository $perfilRepository): JsonResponse
    {
        $perfil = $perfilRepository->find($id);

        if (!$perfil) {
            return $this->json(['message' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $result = [
            'id' => $perfil->getId(),
            'nombre' => $perfil->getNombre(),
            'apellido' => $perfil->getApellido(),
            'direccion' => $perfil->getDireccion(),
            'dni' => $perfil->getDni(),
            'fecha_nacimiento' => $perfil->getFechaNacimiento()->format('Y-m-d'),
            'telefono' => $perfil->getTelefono(),
            'username' => $perfil->getUsuario()->getUsername(),
            'correo' => $perfil->getUsuario()->getCorreo(),
            'imagen'=> $perfil->getImagen(),
        ];

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Listar todos los perfiles con sus pedidos y lineas de pedido siendo gestor
     * @param PerfilRepository $perfilRepository
     * @param PedidoRepository $pedidoRepository
     * @return JsonResponse
     */
    #[Route('/gestor/perfiles/lineaPedido', name: 'listar_perfiles_con_lineas', methods: ['GET'])]
    public function listarPerfilesConLineas(PerfilRepository $perfilRepository, PedidoRepository $pedidoRepository): JsonResponse
    {
        $perfiles = $perfilRepository->findAll();
        $result = [];

        foreach ($perfiles as $perfil) {
            $pedidos = $pedidoRepository->findBy(['perfil' => $perfil]);
            $pedidosConLineas = [];

            foreach ($pedidos as $pedido) {
                $lineasPedido = $pedido->getLineaPedidos();
                $lineas = [];

                foreach ($lineasPedido as $lineaPedido) {
                    $lineas[] = [
                        'id' => $lineaPedido->getId(),
                        'cantidad' => $lineaPedido->getCantidad(),
                        'precio' => $lineaPedido->getPrecio(),
                        'producto' => $lineaPedido->getProducto()->getNombre(),

                    ];
                }

                $pedidosConLineas[] = [
                    'id' => $pedido->getId(),
                    'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                    'pago_total' => $pedido->getPagoTotal(),
                    'estado' => $pedido->getEstado(),
                    'lineas_pedido' => $lineas,
                ];
            }

            $result[] = [
                'perfil' => [
                    'id' => $perfil->getId(),
                    'nombre' => $perfil->getNombre(),
                    'apellido' => $perfil->getApellido(),
                    'direccion' => $perfil->getDireccion(),
                    'dni' => $perfil->getDni(),
                    'fecha_nacimiento' => $perfil->getFechaNacimiento()->format('Y-m-d'),
                    'telefono' => $perfil->getTelefono(),
                    'usuario' => $perfil->getUsuario()->getUsername(),
                    'imagen'=> $perfil->getImagen(),
                ],
                'pedidos' => $pedidosConLineas,
            ];
        }

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Listar todos los perfiles con sus pedidos y lineas de pedido por id de usuario siendo gestor
     * @param int $userId
     * @param PerfilRepository $perfilRepository
     * @param PedidoRepository $pedidoRepository
     * @return JsonResponse
     */
    #[Route('/gestor/perfiles/lineaPedido/{userId}', name: 'listar_perfiles_con_lineas_por_usuario', methods: ['GET'])]
    public function listarPerfilesConLineasPorUsuario(int $userId, PerfilRepository $perfilRepository, PedidoRepository $pedidoRepository): JsonResponse
    {
        $perfiles = $perfilRepository->findBy(['usuario' => $userId]);
        $result = [];

        foreach ($perfiles as $perfil) {
            $pedidos = $pedidoRepository->findBy(['perfil' => $perfil]);
            $pedidosConLineas = [];

            foreach ($pedidos as $pedido) {
                $lineasPedido = $pedido->getLineaPedidos();
                $lineas = [];

                foreach ($lineasPedido as $lineaPedido) {
                    $lineas[] = [
                        'id' => $lineaPedido->getId(),
                        'cantidad' => $lineaPedido->getCantidad(),
                        'precio' => $lineaPedido->getPrecio(),
                        'producto' => $lineaPedido->getProducto()->getNombre(),
                    ];
                }

                $pedidosConLineas[] = [
                    'id' => $pedido->getId(),
                    'fecha' => $pedido->getFecha()->format('Y-m-d H:i:s'),
                    'pago_total' => $pedido->getPagoTotal(),
                    'estado' => $pedido->getEstado(),
                    'lineas_pedido' => $lineas,
                ];
            }

            $result[] = [
                'perfil' => [
                    'id' => $perfil->getId(),
                    'nombre' => $perfil->getNombre(),
                    'apellido' => $perfil->getApellido(),
                    'direccion' => $perfil->getDireccion(),
                    'dni' => $perfil->getDni(),
                    'fecha_nacimiento' => $perfil->getFechaNacimiento()->format('Y-m-d'),
                    'telefono' => $perfil->getTelefono(),
                    'usuario' => $perfil->getUsuario()->getUsername(),
                    'imagen'=> $perfil->getImagen(),
                    'correo'=> $perfil->getUsuario()->getCorreo(),
                ],
                'pedidos' => $pedidosConLineas,
            ];
        }

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Obtener el usuario por el id del perfil
     * @param int $id
     * @param PerfilRepository $perfilRepository
     * @return JsonResponse
     */
    #[Route('/perfil/{id}/usuario', name: 'obtener_usuario_por_perfil', methods: ['GET'])]
    public function obtenerUsuarioPorPerfil(int $id, PerfilRepository $perfilRepository): JsonResponse
    {
        $perfil = $perfilRepository->find($id);

        if (!$perfil) {
            return $this->json(['message' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $usuario = $perfil->getUsuario();

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['usuarioId' => $usuario->getId()], Response::HTTP_OK);
    }

    /**
     * Obtener todos los perfiles de los clientes, este metodo solo lo puede usar un administrador
     * @param UsuarioRepository $usuarioRepository
     * @return JsonResponse
     */
    #[Route('/listaclientes', name:'obtener_perfiles', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function obtenerPerfiles(UsuarioRepository $usuarioRepository): JsonResponse
    {
        $clientes = $usuarioRepository->findBy(['rol'=>'ROLE_CLIENTE']);

        $resultado = [];

        foreach ($clientes as $cliente){
            $resultado[] = [
                'id' => $cliente->getId(),
                'username' => $cliente->getUsername(),
                'correo' => $cliente->getCorreo(),
                'rol' => $cliente->getRol(),
                'activo'=> $cliente->getActivo()
            ];
        }
        return $this->json($resultado, Response::HTTP_OK);
    }


    #[Route('/verificar-password', name: 'verificar_password', methods: ['POST'])]
    public function verificarPassword(Request $request, UserPasswordHasherInterface $passwordHasher, Security $security): JsonResponse {
        $datos = json_decode($request->getContent(), true);
        $usuario = $security->getUser();

        if (!$usuario || !$passwordHasher->isPasswordValid($usuario, $datos['password'])) {
            return $this->json(['valid' => false], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['valid' => true], Response::HTTP_OK);
    }

    #[Route('/cambiar-password', name: 'cambiar_password', methods: ['POST'])]
    public function cambiarPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, Security $security): JsonResponse {
        $datos = json_decode($request->getContent(), true);
        $usuario = $security->getUser();

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if (strlen($datos['nuevaPassword']) < 8) {
            return $this->json(['message' => 'La nueva contraseña debe tener al menos 8 caracteres'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setPassword($passwordHasher->hashPassword($usuario, $datos['nuevaPassword']));
        $em->persist($usuario);
        $em->flush();

        return $this->json(['message' => 'Contraseña cambiada con éxito'], Response::HTTP_OK);
    }

    /**
     * Cambiar el estado de un cliente (activo o inactivo), este metodo solo lo puede usar un administrador.
     *
     * @param int $id
     * @param Request $request
     * @param UsuarioRepository $usuarioRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/cliente/{id}/estado', name: 'desactivar_perfiles', methods:['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function desactivarPerfiles(int $id, Request $request, UsuarioRepository $usuarioRepository, EntityManagerInterface $em): JsonResponse
    {
        $cliente = $usuarioRepository->find($id);

        if (!$cliente || $cliente->getRol() !== 'ROLE_CLIENTE') {
            return $this->json(['error' => 'Cliente no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['activo'])) {
            return $this->json(['error' => 'El campo "activo" es requerido'], Response::HTTP_BAD_REQUEST);
        }

        $cliente->setActivo($data['activo']);
        $em->persist($cliente);
        $em->flush();

        return $this->json([
            'mensaje' => 'Estado actualizado correctamente',
            'id' => $cliente->getId(),
            'activo' => $cliente->getActivo()
        ], Response::HTTP_OK);
    }

    /**
     * Recuperar contraseña desde un correo
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Random\RandomException
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */

    #[Route('/recuperar-password', name: 'recuperar_password', methods: ['POST'])]
    public function recuperarPassword(Request $request,EntityManagerInterface $em): JsonResponse
    {

        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        $datos = json_decode($request->getContent(), true);
        $usuario = $em->getRepository(Usuario::class)->findOneBy(['correo' => $datos['correo']]);

        if (!$usuario) {
            return $this->json(['message' => 'Correo no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $codigo = random_int(100000, 999999); // Generate a 6-digit code
        $usuario->setCodigoVerificacion($codigo);
        $em->flush();

        $email = (new Email())
            ->from('gameessentialsteam@gmail.com')
            ->to($usuario->getCorreo())
            ->subject('Recuperación de contraseña')
            ->text('Para restablecer su contraseña, use el siguiente código: ' . $codigo);

        $mailer->send($email);

        return $this->json(['message' => 'Correo de recuperación enviado'], Response::HTTP_OK);
    }


    /**
     * Resetear la contraseña con el código de verificación
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     */
    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);
        $usuario = $em->getRepository(Usuario::class)->findOneBy(['codigoVerificacion' => $datos['codigo']]);

        if (!$usuario) {
            return $this->json(['message' => 'Código inválido'], Response::HTTP_BAD_REQUEST);
        }

        $newPassword = $datos['newPassword'];

        if (strlen($newPassword) < 8) {
            return $this->json(['message' => 'La nueva contraseña debe tener al menos 8 caracteres'], Response::HTTP_BAD_REQUEST);
        }

        $usuario->setPassword($passwordHasher->hashPassword($usuario, $newPassword));
        $usuario->setCodigoVerificacion(null); // Clear the code after resetting the password
        $em->flush();

        return $this->json(['message' => 'Contraseña restablecida con éxito'], Response::HTTP_OK);
    }


    /**
     * Obtener la imagen de perfil del usuario
     * @param Request $request
     * @param JWTTokenManagerInterface $jwtManager
     * @param UsuarioRepository $usuarioRepository
     * @param PerfilRepository $perfilRepository
     * @return JsonResponse
     */
    #[Route('/perfil/imagen', name: 'obtener_imagen_perfil', methods: ['GET'])]
    public function obtenerImagenPerfil(Request $request, JWTTokenManagerInterface $jwtManager, UsuarioRepository $usuarioRepository, PerfilRepository $perfilRepository): JsonResponse
    {
        $token = $request->headers->get('authorization');
        if (!$token) {
            return $this->json(['message' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
        }

        $formatToken = str_replace('Bearer ', '', $token);
        $finalToken = $jwtManager->parse($formatToken);

        $username = $finalToken['username'] ?? null;
        if (!$username) {
            return $this->json(['message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        $usuario = $usuarioRepository->findOneBy(['username' => $username]);

        if (!$usuario) {
            return $this->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $perfil = $perfilRepository->findOneBy(['usuario' => $usuario]);

        if (!$perfil) {
            return $this->json(['message' => 'Perfil no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['imagen' => $perfil->getImagen()], Response::HTTP_OK);
    }

}
