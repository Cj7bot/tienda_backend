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
use App\Service\EmailService;

class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private HubInterface $hub,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private EmailService $emailService,
        private Pdf $knpSnappyPdf
    ) {}

    #[Route('/api/checkout/process-order', name: 'process_order', methods: ['POST'])]
    public function processOrder(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

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
        $pedido->setFechaPedido(new \DateTime('now', new \DateTimeZone('America/Lima')));
        $pedido->setEstado('procesando');

        // Validar datos requeridos del frontend
        $items = $data['items'] ?? [];
        if (empty($items)) {
            return $this->json([
                'success' => false,
                'message' => 'El carrito no puede estar vacío.',
                'emailSent' => false,
                'emailError' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        // Guardar información adicional del pedido (dirección, método de pago, etc.)
        $address = $data['address'] ?? null;
        $deliveryOption = $data['deliveryOption'] ?? 'pickup';
        $paymentMethod = $data['paymentMethod'] ?? 'credit';
        $termsAccepted = $data['termsAccepted'] ?? false;
        
        if (!$termsAccepted) {
            return $this->json([
                'success' => false,
                'message' => 'Debe aceptar los términos y condiciones.',
                'emailSent' => false,
                'emailError' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->beginTransaction();
        try {
            $this->logger->info('Iniciando procesamiento de pedido.');
            $cantidadTotal = 0;
            $totalPedido = 0;

            foreach ($items as $item) {
                // El frontend puede enviar 'id' o 'productId'
                $productId = $item['id'] ?? $item['productId'] ?? null;
                $quantity = $item['cantidad'] ?? $item['quantity'] ?? 1;
                
                if (!$productId) {
                    throw new \Exception("ID de producto no especificado en el item.");
                }
                
                $producto = $this->entityManager->getRepository(Product::class)->find($productId);

                if (!$producto) {
                    throw new \Exception("Producto con ID {$productId} no encontrado.");
                }

                if ($producto->getStock() < $quantity) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->getNombre()}");
                }

                $detalle = new DetallePedido();
                $detalle->setPedido($pedido);
                $detalle->setProducto($producto);
                $detalle->setCantidad($quantity);
                $detalle->setPrecioUnitario((string)$producto->getPrecio());

                $pedido->addDetallePedido($detalle);

                $producto->setStock($producto->getStock() - $quantity);
                $this->entityManager->persist($producto);

                $cantidadTotal += $quantity;
                $totalPedido += $detalle->getSubtotal();
            }

            $pedido->setCantidad($cantidadTotal);
            $pedido->setTotal((string)$totalPedido);

            $this->entityManager->persist($pedido);
            $this->entityManager->flush();

            // --- Enviar correo de confirmación con PDF (en un bloque try-catch para no detener el flujo) ---
            $emailSent = false;
            $emailError = null;
            try {
                $this->logger->info('Generando PDF para el pedido ' . $pedido->getIdPedido());
                $html = $this->renderView('invoice/invoice.html.twig', ['order' => $pedido]);
                $pdfContent = $this->knpSnappyPdf->getOutputFromHtml($html);
                $this->logger->info('PDF generado correctamente.');

                $this->logger->info('Enviando correo de confirmación a ' . $cliente->getEmail());
                
                // Usar el EmailService mejorado con template HTML profesional
                $emailSent = $this->emailService->sendOrderReceiptEmail(
                    $cliente->getEmail(),
                    $cliente->getNombre(),
                    $pedido->getIdPedido(),
                    (float)$pedido->getTotal(),
                    $pdfContent
                );
                
                if ($emailSent) {
                    $this->logger->info('Correo de confirmación enviado con éxito.');
                } else {
                    $this->logger->warning('El servicio de email retornó false - revisar logs del EmailService');
                }
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
                $this->logger->error('Error al generar PDF o enviar correo: ' . $emailError, [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
            }
            // --- Fin del envío de correo ---

            // Publicar actualización a Mercure (opcional - no detiene el flujo si falla)
            try {
                $update = new Update(
                    '/orders/new',
                    json_encode(['orderId' => $pedido->getIdPedido(), 'total' => $pedido->getTotal(), 'cliente' => $pedido->getCliente()->getNombre()])
                );
                $this->hub->publish($update);
                $this->logger->info('Notificación Mercure publicada correctamente.');
            } catch (\Exception $e) {
                $this->logger->warning('No se pudo publicar a Mercure (no crítico): ' . $e->getMessage());
            }

            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->logger->error('Error al procesar el pedido: ' . $e->getMessage(), ['exception' => $e]);
            $this->entityManager->rollback();
            return $this->json([
                'success' => false,
                'message' => 'Error al procesar el pedido: ' . $e->getMessage(),
                'emailSent' => false,
                'emailError' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Respuesta en el formato esperado por el frontend
        $responseMessage = $emailSent 
            ? 'Pedido procesado exitosamente' 
            : 'Pedido creado pero email no enviado';
            
        return $this->json([
            'success' => true,
            'orderId' => 'ORD-' . $pedido->getIdPedido(),
            'emailSent' => $emailSent,
            'emailError' => $emailError,
            'message' => $responseMessage
        ], Response::HTTP_OK);
    }

    #[Route('/api/orders', name: 'create_order', methods: ['POST'])]
    public function createOrder(Request $request): Response
    {
        // Este endpoint es un alias de /api/checkout/process-order
        // para mantener compatibilidad con el frontend
        return $this->processOrder($request);
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
