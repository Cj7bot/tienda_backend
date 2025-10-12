<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Autenticador personalizado para manejo de JWT
 * Este autenticador se usa como fallback cuando JWT no está disponible
 */
class UserAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function supports(Request $request): ?bool
    {
        // Solo soportar rutas específicas que no sean manejadas por JWT
        return $request->getPathInfo() === '/api/login_check' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            throw new AuthenticationException('Datos de autenticación inválidos');
        }

        // Extraer credenciales - compatible con el formato del frontend
        $username = $data['username'] ?? $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new AuthenticationException('Username y password son obligatorios');
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Devolver null permite que otros handlers (como JWT) procesen la respuesta
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'error' => 'Credenciales inválidas',
            'message' => 'Email o contraseña incorrectos'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $data = [
            'error' => 'No autenticado',
            'message' => 'Se requiere autenticación para acceder a este recurso'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
