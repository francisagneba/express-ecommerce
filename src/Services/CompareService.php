<?php

namespace App\Services;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CompareService
{
    private SessionInterface $session;

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepo
    ) {
        // Initialisation de la session via RequestStack
        $this->session = $requestStack->getSession();
    }

    public function getCompare(): array
    {
        return $this->session->get("compare", []);
    }

    public function updateCompare(array $compare): void
    {
        $this->session->set("compare", $compare);
    }

    public function addToCompare(int $productId): bool
    {
        $product = $this->productRepo->find($productId);

        if (!$product) {
            return false; // Le produit n'existe pas
        }

        $compare = $this->getCompare();

        if (!in_array($productId, $compare)) {
            $compare[] = $productId;
            $this->updateCompare($compare);
        }

        return true; // Produit ajouté avec succès
    }

    public function removeToCompare(int $productId): void
    {
        $compare = $this->getCompare();

        if (($key = array_search($productId, $compare)) !== false) {
            unset($compare[$key]);
            $this->updateCompare(array_values($compare));
        }
    }

    public function clearCompare(): void
    {
        $this->updateCompare([]);
    }

    public function getCompareDetails(): array
    {
        $compare = $this->getCompare();
        $result = [];

        foreach ($compare as $productId) {
            $product = $this->productRepo->find($productId);
            if ($product) {
                $result[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'imageUrls' => $product->getImageUrls(),
                    'soldePrice' => $product->getSoldePrice(),
                    'regularPrice' => $product->getRegularPrice(),
                ];
            }
        }

        return $result;
    }
}
