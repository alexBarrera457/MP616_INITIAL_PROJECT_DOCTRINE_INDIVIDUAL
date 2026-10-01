<?php

namespace App\Repository;

final class NurseRepository
{
    
    private const NURSES = [
        ['user' => 'nurse1', 'password' => 'nurse123'],
        ['user' => 'nurse2', 'password' => 'nurse456'],
    ];
  
    public function findAll(): array
    {
       return array_map(
            static fn (array $nurse): array => [
                'user' => $nurse['user'],
            ],
            self::NURSES
        );

    }

    /**
     * Busca una enfermera por nombre de usuario (sin distinguir mayúsculas/minúsculas).
     * Devuelve null si no existe.
     */
    public function findByName(string $name): ?array
    {
        foreach (self::NURSES as $nurse) {
            if (strcasecmp($nurse['user'], $name) === 0) {
                return ['user' => $nurse['user']];
            }
        }

        return null;
    }

    public function validateCredentials(string $user, string $password): bool
    {
        foreach (self::NURSES as $nurse) {
            if ($nurse['user'] === $user && 
                $nurse['password'] === $password) {
                return true;
            }
        }

        return false;
    }
}
