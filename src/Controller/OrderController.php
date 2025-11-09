<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\DetallePedido;
use App\Entity\Pedido;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use App\Service\MailtrapEmailService;

class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private HubInterface $hub,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Pdf $knpSnappyPdf,
        private MailtrapEmailService $mailtrapEmailService
    ) {}

    #[Route('/api/checkout/process-order', name: 'process_order', methods: ['POST'])]
    public function processOrder(Request $request): Response
    {
        $this->logger->info('Petición recibida en /api/checkout/process-order.');
        
        $data = json_decode($request->getContent(), true);

        $this->logger->info('Datos recibidos del frontend:', $data ?? []);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['message' => 'JSON inválido'], Response::HTTP_BAD_REQUEST);
        }

        /** @var Cliente $cliente */
        $cliente = $this->security->getUser();

        if (!$cliente) {
            // En un entorno de producción, deberías devolver un error 401.
            // Para desarrollo, podemos buscar un cliente por defecto.
            $cliente = $this->entityManager->getRepository(Cliente::class)->find(1); // Cambia 1 por un ID de cliente que exista
            if (!$cliente) {
                return $this->json(['message' => 'Usuario no autenticado y cliente de prueba no encontrado.'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $pedido = new Pedido();
        $pedido->setCliente($cliente);
        $pedido->setFechaPedido(new \DateTime());
        $pedido->setEstado('procesando');

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return $this->json(['message' => 'El carrito no puede estar vacío.'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->beginTransaction();
        try {
            $this->logger->info('Iniciando procesamiento de pedido.');
            $cantidadTotal = 0;
            $totalPedido = 0;

            foreach ($items as $item) {
                // Obtener la cantidad del item (soporta múltiples nombres de campo)
                $cantidad = $item['quantity'] ?? $item['qty'] ?? $item['cantidad'] ?? null;
                
                if ($cantidad === null) {
                    throw new \Exception("El campo 'quantity' es requerido para cada producto. Item recibido: " . json_encode($item));
                }

                $producto = $this->entityManager->getRepository(Product::class)->find($item['id']);

                if (!$producto) {
                    throw new \Exception("Producto con ID {$item['id']} no encontrado.");
                }

                if ($producto->getStock() < $cantidad) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->getNombre()}");
                }

                $detalle = new DetallePedido();
                $detalle->setPedido($pedido);
                $detalle->setProducto($producto);
                $detalle->setCantidad($cantidad);
                $detalle->setPrecioUnitario((string)$producto->getPrecio());

                $pedido->addDetallePedido($detalle);

                $producto->setStock($producto->getStock() - $cantidad);
                $this->entityManager->persist($producto);

                $cantidadTotal += $cantidad;
                $totalPedido += $detalle->getSubtotal();
            }

            $pedido->setCantidad($cantidadTotal);
            $pedido->setTotal((string)$totalPedido);

            $this->entityManager->persist($pedido);
            $this->entityManager->flush();

            // --- Enviar comprobante de pago por correo usando Mailtrap ---
            $this->mailtrapEmailService->sendPaymentReceipt($pedido, $cliente->getEmail());
            // --- Fin del envío de correo ---

            // Publicar actualización a Mercure (opcional, no crítico)
            try {
                $update = new Update(
                    '/orders/new',
                    json_encode(['orderId' => $pedido->getIdPedido(), 'total' => $pedido->getTotal(), 'cliente' => $pedido->getCliente()->getNombre()])
                );
                $this->hub->publish($update);
                $this->logger->info('Notificación Mercure enviada con éxito.');
            } catch (\Exception $e) {
                $this->logger->warning('No se pudo enviar la notificación Mercure (no crítico): ' . $e->getMessage());
            }

            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();

            $errorDetails = [
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            $this->logger->error('Error CRÍTICO al procesar el pedido.', $errorDetails);

            return $this->json($errorDetails, Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Pago realizado correctamente',
            'orderId' => $pedido->getIdPedido()
        ], Response::HTTP_CREATED);
    }

    #[Route('/order/{id}/invoice', name: 'order_invoice_pdf', methods: ['GET'])]
    public function generateInvoicePdf(Pedido $pedido): Response
    {
        if (!$pedido) {
            throw $this->createNotFoundException('El pedido no existe.');
        }

        // Renderizar la plantilla Twig a HTML
        $html = $this->renderView('invoice/invoice.html.twig', [
            'order' => $pedido,
        ]);

        // Generar el nombre del archivo
        $filename = sprintf('comprobante-pedido-%s.pdf', $pedido->getIdPedido());

        // Devolver el PDF como una respuesta para descargar o mostrar en el navegador
        return new PdfResponse(
            $this->knpSnappyPdf->getOutputFromHtml($html),
            $filename
        );
    }
}
