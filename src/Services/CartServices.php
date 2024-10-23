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

    public function addToCart($id, $count = 1)
    {
        $cart = $this->getCart(); // Récupérer le panier

        // Si le produit existe déjà dans le panier, on incrémente la quantité
        if (!empty($cart[$id])) {
            $cart[$id] += $count; // Incrémenter la quantité
        } else {
            // Sinon, on l'ajoute avec la quantité fournie
            $cart[$id] = $count;
        }

        $this->updateCart($cart); // Mettre à jour le panier
    }


    // public function addToCart($productId, $count = 1)
    // {
    //     // [
    //     //     '1' => 3,
    //     //     '25' => 1,
    //     // ]
    //     $cart = $this->getCart();

    //     if(!empty($cart[$productId])){
    //         // product exist in cart
    //         $cart[$productId] += $count;
    //     }else{
    //         // product not exist
    //         $cart[$productId] = $count;
    //     }

    //     $this->updateCart($cart);
    // }

    public function deleteFromCart($id)
    {
        $cart = $this->getCart(); // On recupére le panier

        if (isset($cart[$id])) {  //si c'est définit, c'est que le produit existe déjà dans le panier

            if ($cart[$id] > 1) { // Nous allons poser la condition pour savoir si le produit existe plus d'une fois ds le panier
                $cart[$id]--;
            } else {
                unset($cart[$id]); // Si on a un seul produit , on le retire purement et sinplement
            }
            $this->updateCart($cart); //on procèce à la mise à jour du panier, donc mise à jour de la session
        }
    }

    public function deleteAllToCart($id)
    {  // Ici on supprime tout les produits du panier
        $cart = $this->getCart(); // On recupére le panier

        if (isset($cart[$id])) {  //si c'est définit, c'est que le produit existe déjà dans le panier

            unset($cart[$id]); // On supprime ici tous les produits du panier

            $this->updateCart($cart); //on procèce à la mise à jour du panier, donc mise à jour de la session
        }
    }

    public function deleteCart()
    {
        $this->updateCart([]); // Ici il nous vide vraiment le panier

    }

    public function updateCart($cart)
    {
        $this->session->set('cart', $cart);
        $this->session->set('cartData', $this->getFullCart()); //variable de session contenant les données du produit
    }

    public function getCart()
    {
        return $this->session->get('cart', []);
    }

    // Pour finir on va creer une methode pour recupere tous les produis du panier
    public function getFullCart()
    {
        $cart = $this->getCart();
        $fullCart = [];
        $quantity_cart = 0;
        $subTotal = 0;

        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);

            if ($product) {
                $fullCart['products'][] = [
                    "quantity" => $quantity,
                    "product" => [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'imageUrls' => $product->getImageUrls(),
                        'soldePrice' => $product->getSoldePrice(),
                        'regularPrice' => $product->getRegularPrice(),
                    ],
                ];
                $quantity_cart += $quantity;
                $subTotal += $quantity * $product->getRegularPrice() / 100;
            } else {
                $this->deleteFromCart($id); // Supprimer le produit s'il n'existe pas
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
}
