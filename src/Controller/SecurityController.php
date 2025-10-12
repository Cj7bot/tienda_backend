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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    /**
     * Endpoint de login que devuelve JWT token
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        /** @var Cliente $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'token' => $JWTManager->create($user),
            'user' => [
                'id' => $user->getIdCliente(),
                'email' => $user->getEmail(),
                'nombre' => $user->getNombre(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    /**
     * Endpoint para obtener información del usuario autenticado
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Cliente $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getIdCliente(),
            'email' => $user->getEmail(),
            'nombre' => $user->getNombre(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Endpoint de registro de nuevos usuarios
     */
    #[Route('/register-jwt', name: 'register_jwt', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Datos inválidos'], Response::HTTP_BAD_REQUEST);
        }

        // Validación básica
        if (empty($data['email']) || empty($data['password']) || empty($data['nombre'])) {
            return $this->json(['error' => 'Email, contraseña y nombre son obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el usuario ya existe
        $existingUser = $this->entityManager->getRepository(Cliente::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json(['error' => 'El email ya está registrado'], Response::HTTP_CONFLICT);
        }

        // Crear nuevo usuario
        $user = new Cliente();
        $user->setEmail($data['email']);
        $user->setNombre($data['nombre']);
        $user->setApellido($data['apellido'] ?? ''); // Apellido opcional
        
        // Hash de la contraseña
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Validar entidad
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Datos inválidos', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Guardar en base de datos
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $user->getIdCliente(),
                    'email' => $user->getEmail(),
                    'nombre' => $user->getNombre(),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al registrar usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint para logout (invalidar token del lado del cliente)
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // En JWT stateless, el logout se maneja del lado del cliente
        // eliminando el token del almacenamiento local
        return $this->json(['message' => 'Logout exitoso']);
    }

    /**
     * Endpoint para refrescar token (opcional)
     */
    #[Route('/refresh-token', name: 'refresh_token', methods: ['POST'])]
    public function refreshToken(JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        /** @var Cliente $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'token' => $JWTManager->create($user),
            'user' => [
                'id' => $user->getIdCliente(),
                'email' => $user->getEmail(),
                'nombre' => $user->getNombre(),
            ],
        ]);
    }
}
