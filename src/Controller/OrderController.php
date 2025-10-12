<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\DetallePedido;
use App\Entity\Pedido;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
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

class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private HubInterface $hub,
        private MailerInterface $mailer,
        #[Target('knp_snappy.pdf')] private Pdf $knpSnappyPdf
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
        $pedido->setFechaPedido(new \DateTime());
        $pedido->setEstado('procesando');

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return $this->json(['message' => 'El carrito no puede estar vacío.'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->beginTransaction();
        try {
            $cantidadTotal = 0;
            $totalPedido = 0;

            foreach ($items as $item) {
                $producto = $this->entityManager->getRepository(Product::class)->find($item['id']);

                if (!$producto) {
                    throw new \Exception("Producto con ID {$item['id']} no encontrado.");
                }

                if ($producto->getStock() < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->getNombre()}");
                }

                $detalle = new DetallePedido();
                $detalle->setPedido($pedido);
                $detalle->setProducto($producto);
                $detalle->setCantidad($item['quantity']);
                $detalle->setPrecioUnitario((string)$producto->getPrecio());

                $pedido->addDetallePedido($detalle);

                $producto->setStock($producto->getStock() - $item['quantity']);
                $this->entityManager->persist($producto);

                $cantidadTotal += $item['quantity'];
                $totalPedido += $detalle->getSubtotal();
            }

            $pedido->setCantidad($cantidadTotal);
            $pedido->setTotal((string)$totalPedido);

            $this->entityManager->persist($pedido);
            $this->entityManager->flush();

            // --- Enviar correo de confirmación con PDF ---
            $html = $this->renderView('invoice/invoice.html.twig', ['order' => $pedido]);
            $pdfContent = $this->knpSnappyPdf->getOutputFromHtml($html);

            $email = (new Email())
                ->from('ventas@pureinkafoods.com')
                ->to($cliente->getEmail())
                ->subject('Confirmación de tu pedido #' . $pedido->getIdPedido())
                ->text('¡Gracias por tu compra! Adjuntamos el comprobante de tu pedido.')
                ->html('<p>¡Gracias por tu compra! Adjuntamos el comprobante de tu pedido.</p>')
                ->attach($pdfContent, sprintf('comprobante-pedido-%s.pdf', $pedido->getIdPedido()), 'application/pdf');

            $this->mailer->send($email);
            // --- Fin del envío de correo ---

            // Publicar actualización a Mercure
            $update = new Update(
                '/orders/new',
                json_encode(['orderId' => $pedido->getIdPedido(), 'total' => $pedido->getTotal(), 'cliente' => $pedido->getCliente()->getNombre()])
            );
            $this->hub->publish($update);

            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Pedido procesado con éxito',
            'orderId' => $pedido->getIdPedido()
        ], Response::HTTP_CREATED);
    }

    #[Route('/order/{id}/invoice', name: 'order_invoice_pdf', methods: ['GET'])]
    public function generateInvoicePdf(Pedido $pedido, #[Target('knp_snappy.pdf')] Pdf $knpSnappyPdf): Response
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
            $knpSnappyPdf->getOutputFromHtml($html),
            $filename
        );
    }
}
