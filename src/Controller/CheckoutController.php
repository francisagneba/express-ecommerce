<?php

namespace App\Controller;

use App\Services\CartServices;
use App\Repository\AddressRepository;
use App\Services\StripeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    private $cartServices;
    private $stripeService;
    private $session;

    public function __construct(
        CartServices $cartServices,
        RequestStack $requestStack,
        StripeService $stripeService,
    ) {
        $this->cartServices = $cartServices;
        $this->stripeService = $stripeService;
        $this->session = $requestStack->getSession();
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function index(AddressRepository $addressRepository): Response
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

        $addresses = $addressRepository->findByUser($user);
        $cart_json = json_encode($cart);

        $public_key = $this->stripeService->getPublicKey();

        return $this->render('checkout/index.html.twig', [
            'controller_name' => 'CheckoutController',
            'cart' => $cart,
            'cart_json' => $cart_json,
            'addresses' => $addresses,
            'public_key' => $public_key,
        ]);
    }
}