<?php

namespace App\Controller;

use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse_controller')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => "Welcome to the new Controller",
            'path' => "src/Controller/NurseController.php",
        ]);
    }

    #[Route('/index', name: 'app_nurse')]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
    {
        return $this->json($nurseRepository->findAll());
    }
  
    #[Route('/name/{name}', name: 'app_nurse_show_name', methods: ['GET'])]
    public function findByName(string $name, NurseRepository $nurseRepository): JsonResponse
    {
        $name = trim($name);

        if ($name === '') {
            return $this->json([
                'success' => false,
                'message' => 'El nombre no puede estar vacío',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $nurse = $nurseRepository->findByName($name);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Error al consultar los datos',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($nurse === null) {
            return $this->json([
                'success' => false,
                'message' => 'Enfermera no encontrada',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($nurse);
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
            return $this->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'message' => 'Credenciales correctas',
        ]);
    }
}
