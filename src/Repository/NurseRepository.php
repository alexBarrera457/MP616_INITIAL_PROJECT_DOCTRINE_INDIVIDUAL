<?php

namespace App\Repository;

final class NurseRepository
{
    private const NURSES = [
        ['user' => 'nurse1', 'password' => 'nurse123'],
        ['user' => 'nurse2', 'password' => 'nurse456'],
    ];

    public function validateCredentials(string $user, string $password): bool
    {
        foreach (self::NURSES as $nurse) {
            if ($nurse['user'] === $user && $nurse['password'] === $password) {
                return true;
            }
        }

        return false;
    }
}
