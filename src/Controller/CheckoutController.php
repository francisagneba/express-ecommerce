<?php

namespace App\Controller;

use App\Services\CartServices;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    private CartServices $cartServices;
    private $session;

    public function __construct(CartServices $cartServices, RequestStack $requestStack)
    {
        $this->cartServices = $cartServices;
        $this->session = $requestStack->getSession();
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function index(): Response
    {

        $cart = $this->cartServices->getFullCart();

        if (!count($cart["items"])) {
            return $this->redirectToRoute('app_home');
        }

        $user = $this->getUser();

        if (!$user) {
            $this->session->set("next", "app_checkout");
            return $this->redirectToRoute("app_login");
        }

        // $cart_json = json_encode($cart);

        return $this->render('checkout/index.html.twig', [
            'controller_name' => 'CheckoutController',
            'cart' => $cart,
        ]);
    }
}
