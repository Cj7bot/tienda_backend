<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $fromEmail = 'noreply@pureinkafoods.com',
        string $fromName = 'Pure Inka Foods'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Envía un email de bienvenida a un nuevo cliente
     */
    public function sendWelcomeEmail(string $toEmail, string $customerName): bool
    {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject('¡Bienvenido a Pure Inka Foods!')
                ->html($this->getWelcomeEmailTemplate($customerName));

            $this->mailer->send($email);
            $this->logger->info('Welcome email sent successfully', ['to' => $toEmail]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envía un email de confirmación de pedido
     */
    public function sendOrderConfirmationEmail(
        string $toEmail,
        string $customerName,
        int $orderId,
        float $total
    ): bool {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject("Confirmación de Pedido #$orderId")
                ->html($this->getOrderConfirmationTemplate($customerName, $orderId, $total));

            $this->mailer->send($email);
            $this->logger->info('Order confirmation email sent successfully', [
                'to' => $toEmail,
                'order_id' => $orderId
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send order confirmation email', [
                'to' => $toEmail,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envía un email con el comprobante de pedido adjunto (PDF)
     */
    public function sendOrderReceiptEmail(
        string $toEmail,
        string $customerName,
        int $orderId,
        float $total,
        string $pdfContent
    ): bool {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject("Comprobante de Compra - Pedido #{$orderId}")
                ->html($this->getOrderReceiptTemplate($customerName, $orderId, $total))
                ->attach($pdfContent, sprintf('comprobante-pedido-%s.pdf', $orderId), 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Order receipt email sent successfully', [
                'to' => $toEmail,
                'order_id' => $orderId
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send order receipt email', [
                'to' => $toEmail,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Envía un email genérico
     */
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $htmlContent,
        ?string $textContent = null
    ): bool {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject($subject)
                ->html($htmlContent);

            if ($textContent) {
                $email->text($textContent);
            }

            $this->mailer->send($email);
            $this->logger->info('Email sent successfully', [
                'to' => $toEmail,
                'subject' => $subject
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Template HTML para email de bienvenida
     */
    private function getWelcomeEmailTemplate(string $customerName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2E8B57;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2E8B57;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            color: #777;
            font-size: 12px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a Pure Inka Foods!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{$customerName}</strong>,</p>
            
            <p>¡Gracias por registrarte en Pure Inka Foods! Estamos emocionados de tenerte como parte de nuestra comunidad.</p>
            
            <p>Ahora puedes disfrutar de:</p>
            <ul>
                <li>Productos auténticos de Perú</li>
                <li>Ofertas exclusivas para miembros</li>
                <li>Seguimiento de tus pedidos</li>
                <li>Historial de compras</li>
            </ul>
            
            <p>¡Comienza a explorar nuestros productos ahora!</p>
            
            <a href="http://localhost:5173" class="button">Ir a la Tienda</a>
        </div>
        <div class="footer">
            <p><strong>Pure Inka Foods International</strong><br>
            © 2025 Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Template HTML para email de confirmación de pedido
     */
    private function getOrderConfirmationTemplate(string $customerName, int $orderId, float $total): string
    {
        $formattedTotal = number_format($total, 2);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2E8B57;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2E8B57;
        }
        .order-info p {
            margin: 10px 0;
        }
        .total {
            font-size: 24px;
            color: #2E8B57;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2E8B57;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Pedido Confirmado!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{$customerName}</strong>,</p>
            
            <p>Hemos recibido tu pedido y lo estamos procesando. Aquí están los detalles:</p>
            
            <div class="order-info">
                <p><strong>Número de Pedido:</strong> #{$orderId}</p>
                <p><strong>Total:</strong> <span class="total">\$ {$formattedTotal}</span></p>
                <p><strong>Estado:</strong> En proceso</p>
            </div>
            
            <p>Te enviaremos otro email cuando tu pedido sea enviado.</p>
            
            <p>Puedes ver el estado de tu pedido en cualquier momento desde tu cuenta.</p>
            
            <a href="http://localhost:5173/orders/{$orderId}" class="button">Ver Mi Pedido</a>
        </div>
        <div class="footer">
            <p><strong>Pure Inka Foods International</strong><br>
            © 2025 Todos los derechos reservados</p>
            <p>Si tienes alguna pregunta, contáctanos en support@pureinkafoods.com</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Template HTML para email de comprobante con PDF adjunto
     */
    private function getOrderReceiptTemplate(string $customerName, int $orderId, float $total): string
    {
        $formattedTotal = number_format($total, 2);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2E8B57;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .order-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2E8B57;
        }
        .order-info p {
            margin: 10px 0;
        }
        .total {
            font-size: 24px;
            color: #2E8B57;
            font-weight: bold;
        }
        .attachment-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .attachment-notice strong {
            color: #856404;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Gracias por tu Compra!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{$customerName}</strong>,</p>
            
            <p>Tu pedido ha sido procesado exitosamente. Aquí están los detalles:</p>
            
            <div class="order-info">
                <p><strong>Número de Pedido:</strong> #{$orderId}</p>
                <p><strong>Total Pagado:</strong> <span class="total">\$ {$formattedTotal}</span></p>
                <p><strong>Estado:</strong> En proceso</p>
                <p><strong>Fecha:</strong> {$this->getCurrentDate()}</p>
            </div>
            
            <div class="attachment-notice">
                <p><strong>📎 Comprobante Adjunto</strong></p>
                <p>Hemos adjuntado tu comprobante de compra en formato PDF. Puedes descargarlo, imprimirlo o guardarlo para tus registros.</p>
            </div>
            
            <p>Te notificaremos cuando tu pedido sea enviado.</p>
            
            <p>Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos.</p>
        </div>
        <div class="footer">
            <p><strong>Pure Inka Foods International</strong><br>
            © 2025 Todos los derechos reservados</p>
            <p>📧 support@pureinkafoods.com | 📞 +51 123 456 789</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Obtiene la fecha actual formateada
     */
    private function getCurrentDate(): string
    {
        return date('d/m/Y H:i');
    }
}
