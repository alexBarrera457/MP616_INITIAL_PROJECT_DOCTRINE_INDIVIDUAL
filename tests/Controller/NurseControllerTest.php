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
            ['user' => 'nurse3'],
            ['user' => 'nurse4'],
            ['user' => 'nurse5'],
            ['user' => 'nurse6'],
            ['user' => 'nurse7'],
            ['user' => 'nurse8'],
            ['user' => 'nurse9'],
            ['user' => 'nurse10'],
            ['user' => 'nurse11'],
            ['user' => 'nurse12'],
            ['user' => 'nurse13'],
            ['user' => 'nurse14'],
            ['user' => 'nurse15'],
            ['user' => 'nurse16'],
            ['user' => 'nurse17'],
            ['user' => 'nurse18'],
            ['user' => 'nurse19'],
            ['user' => 'nurse20'],
        ], $nurses);
    }
  
    public function testFindByNameReturnsNurseWhenExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/nurse1');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"user":"nurse1"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByNameIsCaseInsensitive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/NURSE2');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"user":"nurse2"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByNameReturnsNotFoundWhenNameDoesNotExist(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/noexiste');

        self::assertResponseStatusCodeSame(404);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"Enfermera no encontrada"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByNameWithBlankNameReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/%20');

        self::assertResponseStatusCodeSame(400);
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse1',
            'password' => 'n1pass',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"message":"Credenciales correctas"}',
            $client->getResponse()->getContent()
        );
    }

    public function testLoginWithNewNurseCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse3',
            'password' => 'n3pass',
        ]);

        self::assertResponseIsSuccessful();
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
