<?php

namespace App\Controller;

use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse')]
    public function index(): Response
    {
        return $this->json([
            'message' => "Welcome to the new Controller",
            'path' => "src/Controller/NurseController.php",
        ]);
    }

    #[Route('/nurse/find-all', name: 'app_nurse_get_all', methods: ['GET'])]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
    {
        return $this->json($nurseRepository->findAll());
    }
}

