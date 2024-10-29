<?php

namespace App\Services;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartServices
{
    private $session;
    private $repoProduct;
    private $tva = 0.2;

    public function __construct(RequestStack $requestStack, ProductRepository $repoProduct)
    {
        $this->session = $requestStack->getSession(); // Obtenir la session à partir de RequestStack
        $this->repoProduct = $repoProduct;
    }

    public function addToCart(int $id, int $count = 1): void
    {
        $product = $this->repoProduct->find($id);
        if (!$product) {
            throw new \Exception("Product not found.");
        }

        $cart = $this->getCart(); // Récupérer le panier

        // Si le produit existe déjà dans le panier, on incrémente la quantité
        if (isset($cart[$id])) {
            $cart[$id] += $count; // Incrémenter la quantité
        } else {
            // Sinon, on l'ajoute avec la quantité fournie
            $cart[$id] = $count;
        }

        $this->updateCart($cart); // Mettre à jour le panier
    }

    public function deleteFromCart(int $id): void
    {
        $cart = $this->getCart(); // On récupère le panier

        if (isset($cart[$id])) {  // Si c'est défini, c'est que le produit existe déjà dans le panier
            if ($cart[$id] > 1) { // Vérifie si le produit existe plus d'une fois
                $cart[$id]--;
            } else {
                unset($cart[$id]); // Si on a un seul produit, on le retire
            }
            $this->updateCart($cart); // Mise à jour du panier
        }
    }

    public function deleteAllToCart(int $id): void
    {
        $cart = $this->getCart(); // On récupère le panier

        if (isset($cart[$id])) {  // Si c'est défini, c'est que le produit existe déjà dans le panier
            unset($cart[$id]); // On supprime tous les produits du panier
            $this->updateCart($cart); // Mise à jour du panier
        }
    }

    public function deleteCart(): void
    {
        $this->updateCart([]); // Vide le panier
    }

    public function updateCart(array $cart): void
    {
        $this->session->set('cart', $cart);
        $this->session->set('cartData', $this->getFullCart()); // Données du produit dans la session
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = ['items' => []]; // Assurez-vous que 'items' est initialisé
        $quantity_cart = 0;
        $subTotal = 0;

        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);

            if ($product) {
                $subTotalPrice = $quantity * $product->getRegularPrice(); // Calcul du sous-total
                $fullCart['items'][] = [
                    "quantity" => $quantity,
                    "sub_total" => $subTotalPrice, // Ajoutez le sous-total ici
                    "product" => [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'slug' => $product->getSlug(),
                        'imageUrls' => $product->getImageUrls(),
                        'soldePrice' => $product->getSoldePrice(),
                        'regularPrice' => $product->getRegularPrice(),
                    ],
                ];
                $quantity_cart += $quantity;
                $subTotal += $subTotalPrice; // Mettez à jour le sous-total
            } else {
                $this->deleteFromCart($id); // Supprime le produit s'il n'existe pas
            }
        }

        // Ajout des totaux dans le panier
        $fullCart['data'] = [
            "quantity_cart" => $quantity_cart,
            "subTotalHT" => $subTotal,
            "taxe" => round($subTotal * $this->tva, 2),
            "subTotalTTC" => round(($subTotal + ($subTotal * $this->tva)), 2)
        ];

        return $fullCart;
    }

    public function getTax(): float
    {
        return $this->tva; // Ajoutez cette méthode pour obtenir la taxe si nécessaire
    }
}