<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Cliente;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route('/api/register-simple', name: 'user_register_simple', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $apellido = $data['apellido'] ?? '';

        if (!$email || !$password || !$nombre) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios'], 400);
        }

        // Verificar si el email ya existe
        $existingCliente = $em->getRepository(Cliente::class)->findOneBy(['email' => $email]);
        if ($existingCliente) {
            return new JsonResponse(['error' => 'El email ya está registrado'], 400);
        }

        $cliente = new Cliente();
        $cliente->setEmail($email);
        $cliente->setNombre($nombre);
        $cliente->setApellido($apellido);
        $cliente->setRoles(['ROLE_USER']);
        $cliente->setFechaRegistro(new \DateTime());
        $hashedPassword = $passwordHasher->hashPassword($cliente, $password);
        $cliente->setPassword($hashedPassword);

        $em->persist($cliente);
        $em->flush();

        return new JsonResponse(['message' => 'Cliente registrado correctamente'], 201);
    }
    #[Route('/api/login-simple', name: 'user_login_simple', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email y contraseña son obligatorios'], 400);
        }

        $cliente = $em->getRepository(Cliente::class)->findOneBy(['email' => $email]);

        if (!$cliente) {
            return new JsonResponse(['error' => 'Cliente no encontrado'], 404);
        }

        if (!$passwordHasher->isPasswordValid($cliente, $password)) {
            return new JsonResponse(['error' => 'Contraseña incorrecta'], 401);
        }

        return new JsonResponse([
            'message' => 'Inicio de sesión exitoso',
            'nombre' => $cliente->getNombre(),
            'apellido' => $cliente->getApellido(),
            'email' => $cliente->getEmail(),
            'id' => $cliente->getIdCliente()
        ]);
    }
}
