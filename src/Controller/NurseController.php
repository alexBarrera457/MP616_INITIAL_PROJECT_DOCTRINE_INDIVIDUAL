<?php

namespace App\Controller;

use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => "Welcome to the new Controller",
            'path' => "src/Controller/NurseController.php",
        ]);
    }

    #[Route('/login', name: 'app_nurse_login', methods: ['POST'])]
    public function login(Request $request, NurseRepository $nurseRepository): JsonResponse
    {
        $credentials = $request->getPayload()->all();
        $user = $credentials['user'] ?? null;
        $password = $credentials['password'] ?? null;

        if (
            !is_string($user)
            || !is_string($password)
            || !$nurseRepository->validateCredentials($user, $password)
        ) {
            return $this->json(['message' => 'Credenciales incorrectas'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json(['message' => 'Credenciales correctas']);
    }
}
