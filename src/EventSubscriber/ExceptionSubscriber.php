<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Solo manejar excepciones de API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $statusCode = $this->getStatusCode($exception);
        $errorData = $this->getErrorData($exception, $statusCode);

        // Log del error (excepto errores de autenticación comunes)
        if ($statusCode >= 500) {
            $this->logger->error('API Error', [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_uri' => $request->getUri(),
                'request_method' => $request->getMethod(),
            ]);
        }

        $response = new JsonResponse($errorData, $statusCode);
        
        // Headers de seguridad
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        $event->setResponse($response);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($exception instanceof AccessDeniedException) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getErrorData(\Throwable $exception, int $statusCode): array
    {
        $errorData = [
            'error' => [
                'code' => $statusCode,
                'message' => $this->getErrorMessage($exception, $statusCode),
            ]
        ];

        // En desarrollo, incluir más detalles del error
        if ($_ENV['APP_ENV'] === 'dev') {
            $errorData['error']['debug'] = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice($exception->getTrace(), 0, 5), // Solo primeras 5 líneas
            ];
        }

        return $errorData;
    }

    private function getErrorMessage(\Throwable $exception, int $statusCode): string
    {
        // Mensajes de error seguros para producción
        return match ($statusCode) {
            Response::HTTP_UNAUTHORIZED => 'No autenticado. Token requerido.',
            Response::HTTP_FORBIDDEN => 'Acceso denegado. Permisos insuficientes.',
            Response::HTTP_NOT_FOUND => 'Recurso no encontrado.',
            Response::HTTP_METHOD_NOT_ALLOWED => 'Método no permitido.',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'Datos de entrada inválidos.',
            Response::HTTP_TOO_MANY_REQUESTS => 'Demasiadas solicitudes. Inténtalo más tarde.',
            Response::HTTP_INTERNAL_SERVER_ERROR => 'Error interno del servidor.',
            default => ($_ENV['APP_ENV'] === 'dev') ? $exception->getMessage() : 'Error en la solicitud.',
        };
    }
}
