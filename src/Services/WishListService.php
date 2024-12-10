<?php

namespace App\Services;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class WishListService
{
    private SessionInterface $session;

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepo
    ) {
        // Initialisation de la session via RequestStack
        $this->session = $requestStack->getSession();
    }

    public function getWishList(): array
    {
        return $this->session->get("wishlist", []);
    }

    public function updateWishList(array $wishlist): void
    {
        $this->session->set("wishlist", $wishlist);
    }

    public function addToWishList(int $productId): bool
    {
        $product = $this->productRepo->find($productId);

        if (!$product) {
            return false; // Le produit n'existe pas
        }

        $wishlist = $this->getWishList();

        if (!in_array($productId, $wishlist)) {
            $wishlist[] = $productId;
            $this->updateWishList($wishlist);
        }

        return true; // Produit ajouté avec succès
    }

    public function removeToWishList(int $productId): void
    {
        $wishlist = $this->getWishList();

        if (($key = array_search($productId, $wishlist)) !== false) {
            unset($wishlist[$key]);
            $this->updateWishList(array_values($wishlist));
        }
    }

    public function clearWishList(): void
    {
        $this->updateWishList([]);
    }

    public function getWishListDetails(): array
    {
        $wishlist = $this->getWishList();
        $result = [];

        foreach ($wishlist as $productId) {
            $product = $this->productRepo->find($productId);
            if ($product) {
                $result[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'imageUrls' => $product->getImageUrls(),
                    'soldePrice' => $product->getSoldePrice(),
                    'regularPrice' => $product->getRegularPrice(),
                    'stock' => $product->getStock(),
                ];
            }
        }

        return $result;
    }
}
