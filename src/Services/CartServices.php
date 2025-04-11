<?php

namespace App\Services;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Repository\CarrierRepository;
use App\Repository\OrderDetailsRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartServices
{
    private $session;
    private $repoProduct;
    private $carrierRepo;
    // private $tva = 0.2;
    // private $taxe;
    private $settingRepo;

    public function __construct(RequestStack $requestStack, ProductRepository $repoProduct, CarrierRepository $carrierRepo, OrderDetailsRepository $orderDetailsRepo, SettingRepository $settingRepo)
    {
        $this->session = $requestStack->getSession(); // Obtenir la session à partir de RequestStack
        $this->repoProduct = $repoProduct;
        $this->carrierRepo = $carrierRepo;
        $this->settingRepo = $settingRepo;
    }

    public function addToCart(int $id, int $count = 1): void
    {
        $product = $this->repoProduct->find($id);
        if (!$product) {
            throw new \Exception("Product not found.");
        }

        $cart = $this->get('cart'); // Récupérer le panier

        if (isset($cart[$id])) {
            $cart[$id] += $count;
        } else {
            $cart[$id] = $count;
        }

        $this->update("cart", $cart); // Mettre à jour le panier
    }


    public function deleteFromCart(int $id): void
    {
        $cart = $this->get('cart');

        if (isset($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
            $this->update("cart", $cart);
        }
    }

    public function deleteAllToCart(int $id): void
    {
        $cart = $this->get('cart');

        if (isset($cart[$id])) {
            unset($cart[$id]);
            $this->update("cart", $cart);
        }
    }

    public function update($key, array $cart): void
    {
        $this->session->set($key, $cart);
        $this->session->set('cartData', $this->getFullCart());
    }

    public function updateCarrier($carrier): void
    {
        $this->update("carrier", $carrier);
    }

    public function get($key): mixed
    {
        return $this->session->get($key);
    }

    public function getFullCart(): array
    {
        $cart = $this->get('cart');
        $fullCart = ['items' => []];
        $quantity_cart = 0;
        $subTotal = 0.0;

        $carrier = $this->get("carrier");

        if (!$carrier) {
            $carrierEntity = $this->carrierRepo->findAll()[0] ?? null;

            if ($carrierEntity) {
                $carrier = [
                    "id" => $carrierEntity->getId(),
                    "name" => $carrierEntity->getName(),
                    "description" => $carrierEntity->getDescription(),
                    "price" => $carrierEntity->getPrice(),
                ];
                $this->update("carrier", $carrier);
            }
        }

        $taxe_rate = 0;

        $setting = $this->settingRepo->findOneBy(["website_name" => "Express Ecommerce"]);

        if ($setting) {
            $taxe_rate = $setting->getTaxeRate() / 100;
        }

        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);

            if ($product) {
                $price = (float) $product->getSoldePrice(); // Forcer la conversion en float
                $subTotalPrice = $quantity * $price;

                $fullCart['items'][] = [
                    "quantity" => $quantity,
                    'order_cost_ht' => round($subTotalPrice / (1 + $taxe_rate)),
                    'taxe' => round($taxe_rate * $subTotalPrice / (1 + $taxe_rate)),
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
                //$taxe = 0;
            }
        }

        if (!isset($carrier['id'])) {
            $carrier = [
                "id" => null,
                "name" => "Aucun transporteur",
                "price" => 0,
            ];
        }


        $fullCart['data'] = [
            'subTotalHT' => round((float) ($subTotal / (1 + $taxe_rate))),
            'subTotalTTC' => round((float) ($subTotal)),
            'subTotalWithCarrier' => round((float) (($subTotal +  $carrier['price']))),
            'quantity' => $quantity_cart,
            'carrier_id' => is_array($carrier) ? $carrier['id'] : ($carrier ? $carrier->getId() : null),
            'carrier_name' => is_array($carrier) ? $carrier['name'] : ($carrier ? $carrier->getName() : null),
            'carrier_price' => is_array($carrier) ? $carrier['price'] : ($carrier ? $carrier->getPrice() : null),
            //'taxe' => $subTotal * $this->tva,
            'taxe' => round((float) ($subTotal / (1 + $taxe_rate)) * $taxe_rate),

        ];

        // dd($fullCart['data']['subTotalWithCarrier']);


        return $fullCart;
    }
}
