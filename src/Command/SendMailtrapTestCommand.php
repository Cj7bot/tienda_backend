<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-mailtrap',
    description: 'Envía un email de prueba usando Mailtrap'
)]
class SendMailtrapTestCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Enviando email de prueba a Mailtrap...');

            $email = (new Email())
                ->from('hello@demomailtrap.com')
                ->to('matihacksito@gmail.com')
                ->subject('Email de prueba - Mailtrap')
                ->text('Este es un email de prueba desde Pure Inka Foods')
                ->html('<p><strong>¡Hola!</strong></p><p>Este es un email de prueba desde Pure Inka Foods usando Mailtrap.</p>');

            $this->mailer->send($email);

            $io->success('Email enviado exitosamente a Mailtrap. Revisa tu bandeja de entrada en Mailtrap.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al enviar el email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
