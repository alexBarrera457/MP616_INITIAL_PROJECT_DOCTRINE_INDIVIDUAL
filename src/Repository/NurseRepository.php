<?php

namespace App\Repository;

final class NurseRepository
{
    public function findAll(): array
    {
        $nurses = [
            [
                'user' => 'ana_torres',
                'password' => 'demo-password-1',
            ],
            [
                'user' => 'lucia_martin',
                'password' => 'demo-password-2',
            ],
            [
                'user' => 'carlos_ruiz',
                'password' => 'demo-password-3',
            ],
            [
                'user' => 'marta_sanchez',
                'password' => 'demo-password-4',
            ],
        ];

        return array_map(
            static fn (array $nurse): array => ['user' => $nurse['user']],
            $nurses,
        );
    }
}