<?php

namespace App\Tests\Security;

use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecurityTest extends KernelTestCase
{
    public function testFindByEmailIsSafeFromSqlInjection(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ClienteRepository $clientRepository */
        $clientRepository = $container->get(ClienteRepository::class);

        
        $payload = "' OR '1'='1";

        
        $client = $clientRepository->findByEmail($payload);

        $this->assertNull($client, "Doctrine should prevent SQL injection and return null for a malicious email.");
    }
}
