<?php

namespace App\Controller;

use App\Services\CartServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    private CartServices $cartServices;

    public function __construct(CartServices $cartServices)
    {
        $this->cartServices = $cartServices;
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function index(): Response
    {
        $cart = $this->cartServices->getFullCart();

        if (!count($cart["items"])) {
            return $this->redirectToRoute('app_home');
        }

        // $cart_json = json_encode($cart);

        return $this->render('checkout/index.html.twig', [
            'controller_name' => 'CheckoutController',
            'cart' => $cart,
        ]);
    }
}