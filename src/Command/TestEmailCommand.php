<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envía un email de prueba para verificar la configuración de Mailtrap',
)]
class TestEmailCommand extends Command
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email del destinatario')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Tipo de email (welcome, order, custom)', 'welcome')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nombre del destinatario', 'Usuario de Prueba');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $type = $input->getOption('type');
        $name = $input->getOption('name');

        $io->title('🧪 Prueba de Servicio de Email con Mailtrap');
        $io->info("Enviando email de tipo '{$type}' a: {$email}");

        $success = false;

        switch ($type) {
            case 'welcome':
                $io->text('📧 Enviando email de bienvenida...');
                $success = $this->emailService->sendWelcomeEmail($email, $name);
                break;

            case 'order':
                $orderId = rand(1000, 9999);
                $total = rand(50, 500) + (rand(0, 99) / 100);
                $io->text("📧 Enviando confirmación de pedido #{$orderId} por \${$total}...");
                $success = $this->emailService->sendOrderConfirmationEmail(
                    $email,
                    $name,
                    $orderId,
                    $total
                );
                break;

            case 'custom':
                $io->text('📧 Enviando email personalizado...');
                $success = $this->emailService->sendEmail(
                    $email,
                    'Email de Prueba - Pure Inka Foods',
                    '<h1>¡Hola!</h1><p>Este es un email de prueba desde Pure Inka Foods.</p><p>Si ves este mensaje, tu configuración de Mailtrap está funcionando correctamente.</p>',
                    'Hola! Este es un email de prueba desde Pure Inka Foods.'
                );
                break;

            default:
                $io->error("Tipo de email no válido: {$type}");
                $io->note('Tipos válidos: welcome, order, custom');
                return Command::FAILURE;
        }

        if ($success) {
            $io->success('✅ ¡Email enviado exitosamente a Mailtrap!');
            $io->section('📬 Próximos pasos:');
            $io->listing([
                'Ve a tu inbox de Mailtrap: https://mailtrap.io/inboxes',
                'Verifica que el email haya llegado',
                'Revisa el contenido HTML y el diseño',
                'Comprueba que todos los datos se muestren correctamente'
            ]);
            return Command::SUCCESS;
        } else {
            $io->error('❌ Error al enviar el email.');
            $io->section('🔧 Solución de problemas:');
            $io->listing([
                'Verifica que MAILER_DSN esté configurado correctamente en .env',
                'Tu configuración actual debería ser: smtp://caf1c21aa85a87:e0e70ca0522379@sandbox.smtp.mailtrap.io:2525',
                'Asegúrate de que las credenciales de Mailtrap sean correctas',
                'Revisa los logs en var/log/ para más detalles'
            ]);
            return Command::FAILURE;
        }
    }
}
