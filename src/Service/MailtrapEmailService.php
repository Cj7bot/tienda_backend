<?php

namespace App\Service;

use App\Entity\Pedido;
use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twig\Environment;

class MailtrapEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Pdf $knpSnappyPdf,
        private Environment $twig,
        private LoggerInterface $logger
    ) {}

    /**
     * Envía un comprobante de pago por correo electrónico usando Mailtrap
     * 
     * @param Pedido $pedido El pedido para el cual se generará el comprobante
     * @param string $recipientEmail Email del destinatario
     * @return bool True si el envío fue exitoso, false en caso contrario
     */
    public function sendPaymentReceipt(Pedido $pedido, string $recipientEmail): bool
    {
        try {
            $this->logger->info('Generando PDF para el pedido ' . $pedido->getIdPedido());
            
            // Generar el HTML desde la plantilla Twig
            $html = $this->twig->render('invoice/invoice.html.twig', ['order' => $pedido]);
            
            // Generar el PDF
            $pdfContent = $this->knpSnappyPdf->getOutputFromHtml($html);
            $this->logger->info('PDF generado correctamente. Tamaño: ' . strlen($pdfContent) . ' bytes');

            // Crear el email
            $this->logger->info('Enviando comprobante de pago a ' . $recipientEmail);
            $email = (new Email())
                ->from('hello@demomailtrap.com')  // Usar el dominio de ejemplo de Mailtrap
                ->to($recipientEmail)
                ->subject('Comprobante de Pago - Pedido #' . $pedido->getIdPedido())
                ->text('¡Gracias por tu compra! Adjuntamos el comprobante de tu pedido.')
                ->html($this->generateEmailHtml($pedido))
                ->attach($pdfContent, sprintf('comprobante-pedido-%s.pdf', $pedido->getIdPedido()), 'application/pdf');

            // Enviar el email a través de Mailtrap
            $this->mailer->send($email);
            $this->logger->info('Comprobante de pago enviado con éxito a ' . $recipientEmail . ' vía Mailtrap');
            
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Error de transporte al enviar comprobante: ' . $e->getMessage(), [
                'exception' => $e,
                'debug' => $e->getDebug()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error al generar o enviar comprobante de pago: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Genera el HTML del cuerpo del email
     */
    private function generateEmailHtml(Pedido $pedido): string
    {
        $cliente = $pedido->getCliente();
        $total = $pedido->getTotal();
        $fecha = $pedido->getFechaPedido()->format('d/m/Y H:i');
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .order-details p { margin: 8px 0; }
                .highlight { font-weight: bold; color: #4CAF50; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>¡Gracias por tu compra!</h1>
                </div>
                <div class="content">
                    <p>Estimado/a <strong>{$cliente->getNombre()}</strong>,</p>
                    <p>Tu pedido ha sido procesado exitosamente. Adjuntamos el comprobante de pago en formato PDF.</p>
                    
                    <div class="order-details">
                        <h3>Detalles del Pedido</h3>
                        <p><strong>Número de Pedido:</strong> #{$pedido->getIdPedido()}</p>
                        <p><strong>Fecha:</strong> {$fecha}</p>
                        <p><strong>Total:</strong> <span class="highlight">S/ {$total}</span></p>
                        <p><strong>Estado:</strong> {$pedido->getEstado()}</p>
                    </div>
                    
                    <p>Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos.</p>
                </div>
                <div class="footer">
                    <p>Pure Inka Foods - Productos de calidad para ti</p>
                    <p>Este es un correo automático, por favor no responder.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
