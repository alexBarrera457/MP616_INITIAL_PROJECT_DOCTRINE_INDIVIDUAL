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
        $client->request('GET', '/nurse/find-all');

        self::assertResponseIsSuccessful();

        $nurses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            ['user' => 'ana_torres'],
            ['user' => 'lucia_martin'],
            ['user' => 'carlos_ruiz'],
            ['user' => 'marta_sanchez'],
        ], $nurses);
    }
}
