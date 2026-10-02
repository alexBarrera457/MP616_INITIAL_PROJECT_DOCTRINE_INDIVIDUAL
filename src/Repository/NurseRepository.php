<?php

namespace App\Repository;

final class NurseRepository
{
    
    private const NURSES = [
        ['user' => 'nurse1', 'password' => 'n1pass'],
        ['user' => 'nurse2', 'password' => 'n2pass'],
        ['user' => 'nurse3', 'password' => 'n3pass'],
        ['user' => 'nurse4', 'password' => 'n4pass'],
        ['user' => 'nurse5', 'password' => 'n5pass'],
        ['user' => 'nurse6', 'password' => 'n6pass'],
        ['user' => 'nurse7', 'password' => 'n7pass'],
        ['user' => 'nurse8', 'password' => 'n8pass'],
        ['user' => 'nurse9', 'password' => 'n9pass'],
        ['user' => 'nurse10', 'password' => 'n10pass'],
        ['user' => 'nurse11', 'password' => 'n11pass'],
        ['user' => 'nurse12', 'password' => 'n12pass'],
        ['user' => 'nurse13', 'password' => 'n13pass'],
        ['user' => 'nurse14', 'password' => 'n14pass'],
        ['user' => 'nurse15', 'password' => 'n15pass'],
        ['user' => 'nurse16', 'password' => 'n16pass'],
        ['user' => 'nurse17', 'password' => 'n17pass'],
        ['user' => 'nurse18', 'password' => 'n18pass'],
        ['user' => 'nurse19', 'password' => 'n19pass'],
        ['user' => 'nurse20', 'password' => 'n20pass'],
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
