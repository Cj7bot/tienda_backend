<?php

namespace App\Controller;

use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private JWTTokenManagerInterface $jwtManager;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $jwtManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Registro de clientes - Formato específico para frontend
     */
    #[Route('/register', name: 'api_register_frontend', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar datos requeridos
        if (!isset($data['nombre']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Datos incompletos'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Datos incompletos'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el email ya existe
        $existingClient = $this->entityManager->getRepository(Cliente::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingClient) {
            return $this->json([
                'success' => false,
                'error' => 'El email ya está registrado'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Crear nuevo cliente
        $cliente = new Cliente();
        $cliente->setNombre($data['nombre']);
        $cliente->setApellido($data['apellido'] ?? ''); // Apellido opcional
        $cliente->setEmail($data['email']);
        
        // Hash de la contraseña usando Symfony's password hasher
        $hashedPassword = $this->passwordHasher->hashPassword($cliente, $data['password']);
        $cliente->setPassword($hashedPassword);
        
        $cliente->setFechaRegistro(new \DateTime());
        $cliente->setRoles(['ROLE_USER']);

        // Validar entidad
        $errors = $this->validator->validate($cliente);
        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'error' => 'Datos inválidos'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($cliente);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Cliente registrado exitosamente',
                'cliente_id' => $cliente->getIdCliente()
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error al registrar cliente'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login check - Formato específico para frontend
     */
    #[Route('/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Credenciales incompletas'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar cliente por email (username)
        $cliente = $this->entityManager->getRepository(Cliente::class)
            ->findOneBy(['email' => $data['username']]);

        if (!$cliente) {
            return $this->json([
                'success' => false,
                'error' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar contraseña
        if (!$this->passwordHasher->isPasswordValid($cliente, $data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generar token JWT
        $token = $this->jwtManager->create($cliente);

        return $this->json([
            'token' => $token,
            'username' => $cliente->getNombre()
        ]);
    }

    /**
     * Obtener perfil del cliente autenticado
     */
    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var Cliente $cliente */
        $cliente = $this->getUser();

        if (!$cliente) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $cliente->getIdCliente(),
            'username' => $cliente->getNombre(),
            'email' => $cliente->getEmail()
        ]);
    }

    /**
     * Actualizar perfil del cliente
     */
    #[Route('/profile/update', name: 'api_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Cliente $cliente */
        $cliente = $this->getUser();

        if (!$cliente) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Actualizar campos si están presentes
        if (isset($data['username']) && !empty($data['username'])) {
            $cliente->setNombre($data['username']);
        }

        if (isset($data['apellido'])) {
            $cliente->setApellido($data['apellido']);
        }

        if (isset($data['telefono'])) {
            $cliente->setTelefono($data['telefono']);
        }

        if (isset($data['direccion'])) {
            $cliente->setDireccion($data['direccion']);
        }

        if (isset($data['dni'])) {
            $cliente->setDni($data['dni']);
        }

        try {
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'cliente' => [
                    'id' => $cliente->getIdCliente(),
                    'username' => $cliente->getNombre(),
                    'email' => $cliente->getEmail(),
                    'apellido' => $cliente->getApellido(),
                    'telefono' => $cliente->getTelefono(),
                    'direccion' => $cliente->getDireccion(),
                    'dni' => $cliente->getDni()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error al actualizar perfil'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout del cliente
     */
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Con JWT, el logout se maneja en el frontend eliminando el token
        // Aquí podríamos implementar una blacklist de tokens si fuera necesario
        
        return $this->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }
}
