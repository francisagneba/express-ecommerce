<?php

namespace App\Controller;

use App\Services\CartServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $cartServices;
    public function __construct(CartServices $cartServices)
    {
        $this->cartServices = $cartServices;
    }


    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $cart = $this->cartServices->getFullCart(); // Cette ligne signifie qu'il ya quelque chose dans notre panier
        //Donc si le panier est vide on ne l'affiche plus et on fait une redirection vers la page homme.

        $cart_json = json_encode($cart);

        // return $this->json($cart);

        if (!isset($cart['products'])) {
            return $this->redirectToRoute("app_home");
        }
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            "cart_json" => $cart_json
        ]);
    }


    #[Route('/cart/add/{id}/{count}', name: 'cart_add')]
    public function addToCart(string $id, $count = 1): Response
    {
        $this->cartServices->addToCart($id, $count);

        //dd($this->cartServices->getFullCart());
        return $this->redirectToRoute("app_cart");
    }



    #[Route('/cart/delete/{id}', name: 'cart_delete')]
    public function deleteFromCart($id): Response
    {
        $this->cartServices->deleteFromCart($id);
        return $this->redirectToRoute("app_cart");
    }


    #[Route('/cart/delete-all/{id}', name: 'cart_delete_all')]
    public function deleteAllToCart($id): Response
    {
        $this->cartServices->deleteAllToCart($id);
        return $this->redirectToRoute("app_cart");
    }
}
