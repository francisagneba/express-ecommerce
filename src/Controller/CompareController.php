<?php

namespace App\Controller;

use App\Services\CompareService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CompareController extends AbstractController
{
    public function __construct(
        private CompareService $compareService
    ) {}

    #[Route('/compare', name: 'app_compare')]
    public function index(Request $request): Response
    {
        $compare = $this->compareService->getCompareDetails();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($compare);
        }

        return $this->render('compare/index.html.twig', [
            'controller_name' => 'CompareController',
            'compare' => $compare,
            'compare_json' => json_encode($compare)
        ]);
    }


    #[Route('/compare/add/{productId}', name: 'app_add_to_compare')]
    public function addToCompare(int $productId): JsonResponse
    {
        try {
            $this->compareService->addToCompare($productId);
            $compare = $this->compareService->getCompareDetails();

            return new JsonResponse(['success' => true, 'compare' => $compare], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }



    #[Route('/compare/remove/{productId}', name: 'app_remove_to_compare')]
    public function removeToCompare(int $productId): JsonResponse
    {
        $this->compareService->removeToCompare($productId);
        $compare = $this->compareService->getCompareDetails();

        return new JsonResponse(['success' => true, 'compare' => $compare], 200);
    }

    #[Route('/compare/get', name: 'app_get_compare')]
    public function getCompare(): JsonResponse
    {
        $compare = $this->compareService->getCompareDetails();

        return new JsonResponse($compare);
    }
}
