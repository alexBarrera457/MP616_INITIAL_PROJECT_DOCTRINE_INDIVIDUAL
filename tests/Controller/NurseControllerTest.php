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

    public function testGetAllReturnsOnlyNurseNames(): void
    {
        $client = static::createClient();
        $client->request('GET', '/index');

        self::assertResponseIsSuccessful();

        $nurses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            ['user' => 'nurse1'],
            ['user' => 'nurse2'],
        ], $nurses);
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
