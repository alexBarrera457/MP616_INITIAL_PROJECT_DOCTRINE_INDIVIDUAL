<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NurseControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse');

        self::assertResponseIsSuccessful();
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse1',
            'password' => 'nurse123',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"message":"Credenciales correctas"}',
            $client->getResponse()->getContent()
        );
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse1',
            'password' => 'incorrecta',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Credenciales incorrectas"}',
            $client->getResponse()->getContent()
        );
    }
}
