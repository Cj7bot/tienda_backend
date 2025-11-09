<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Cliente;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testXssPayloadIsHandledCorrectlyByApi(): void
    {
        $xssPayload = '<script>alert("xss");</script>';
        $email = 'xss_user_' . uniqid() . '@example.com';
        $password = 'password123';

        
        $this->client->request(
            'POST',
            '/api/register-jwt',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
                'nombre' => $xssPayload,
            ])
        );

        $this->assertResponseIsSuccessful();
        $registerResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Usuario registrado exitosamente', $registerResponse['message']);

        // 2. Login as the new user
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $email,
                'password' => $password,
            ])
        );

        $this->assertResponseIsSuccessful();
        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $loginResponse);
        $token = $loginResponse['token'];

        // 3. Fetch user data from a protected endpoint
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $meResponse = json_decode($this->client->getResponse()->getContent(), true);

        // 4. Assert the API returns the raw, unescaped payload
        $this->assertEquals($xssPayload, $meResponse['nombre'], 'The API should return the raw XSS payload, leaving escaping to the client.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the database
        $query = $this->entityManager->createQuery('DELETE FROM App\\Entity\\Cliente c WHERE c.email LIKE :email');
        $query->setParameter('email', 'xss_user_%@example.com');
        $query->execute();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }
}
