<?php

namespace App\Services;

use App\Entity\Product;
use App\Repository\CarrierRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartServices
{
    private $session;
    private $repoProduct;
    private $carrierRipo;
    private $tva = 0.2;

    public function __construct(RequestStack $requestStack, ProductRepository $repoProduct, CarrierRepository $carrierRepo)
    {
        $this->session = $requestStack->getSession(); // Obtenir la session à partir de RequestStack
        $this->repoProduct = $repoProduct;
        $this->carrierRipo = $carrierRepo;
    }

    public function addToCart(int $id, int $count = 1): void
    {
        $product = $this->repoProduct->find($id);
        if (!$product) {
            throw new \Exception("Product not found.");
        }

        $cart = $this->getCart(); // Récupérer le panier

        if (isset($cart[$id])) {
            $cart[$id] += $count;
        } else {
            $cart[$id] = $count;
        }

        $this->updateCart($cart); // Mettre à jour le panier
    }

    public function deleteFromCart(int $id): void
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
            $this->updateCart($cart);
        }
    }

    public function deleteAllToCart(int $id): void
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            unset($cart[$id]);
            $this->updateCart($cart);
        }
    }

    public function updateCart(array $cart): void
    {
        $this->session->set('cart', $cart);
        $this->session->set('cartData', $this->getFullCart());
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = ['items' => []];
        $quantity_cart = 0;
        $subTotal = 0.0;
        //dd($this->carrierRipo->findAll()[0]);
        $carrier = $this->carrierRipo->findAll()[0];

        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);

            if ($product) {
                $price = (float) $product->getSoldePrice(); // Forcer la conversion en float
                $subTotalPrice = $quantity * $price;

                $fullCart['items'][] = [
                    "quantity" => $quantity,
                    "sub_total" => $subTotalPrice,
                    "product" => [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'slug' => $product->getSlug(),
                        'imageUrls' => $product->getImageUrls(),
                        'soldePrice' => $price,
                        'regularPrice' => (float) $product->getRegularPrice(),
                    ],
                ];
                $quantity_cart += $quantity;
                $subTotal += $subTotalPrice;
            }
        }

        $fullCart['data'] = [
            'subTotalHT' => (float) $subTotal,
            'subTotalTTC' => (float) ($subTotal + ($subTotal * $this->tva)),
            'quantity' => $quantity_cart,
            'carrier_id' => $carrier ? $carrier->getId() : null, // Vérifie que $carrier existe
            'carrier_name' => $carrier ? $carrier->getName() : null,
            'carrier_price' => $carrier ? $carrier->getPrice() : null,
        ];

        return $fullCart;
    }
}