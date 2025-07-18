<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route('/api/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $nombre = $data['nombre'] ?? null;

        if (!$email || !$password || !$nombre) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios'], 400);
        }

        // Buscar último ID y generar nuevo ID incremental tipo PI01, PI02, ...
        $lastUser = $em->getRepository(User::class)->findOneBy([], ['id_usuario' => 'DESC']);

        if ($lastUser) {
            $lastIdNumber = (int) substr($lastUser->getIdUsuario(), 2); // quitar 'PI'
            $newIdNumber = $lastIdNumber + 1;
        } else {
            $newIdNumber = 1;
        }

        $newId = 'PI' . str_pad($newIdNumber, 2, '0', STR_PAD_LEFT);

        $user = new User();
        $user->setIdUsuario($newId);
        $user->setEmail($email);
        $user->setNombre($nombre);
        $user->setRoles(['ROLE_USER']);
        $user->setFechaRegistro(new \DateTime()); // Establecer la fecha actual
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Usuario registrado correctamente'], 201);
    }
    #[Route('/api/login', name: 'user_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email y contraseña son obligatorios'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Contraseña incorrecta'], 401);
        }

        return new JsonResponse([
            'message' => 'Inicio de sesión exitoso',
            'nombre' => $user->getNombre(),
            'email' => $user->getEmail(),
            'id' => $user->getIdUsuario()
        ]);
    }
}
