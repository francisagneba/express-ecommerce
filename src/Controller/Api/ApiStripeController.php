<?php

namespace App\Controller\Api;

use Stripe\StripeClient;
use App\Services\StripeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ApiStripeController extends AbstractController
{
    private $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    #[Route('/api/stripe/payment-intent', name: 'app_stripe_payment-intent', methods: ['POST'])]
    public function index(Request $req): Response
    {
        try {
            $stripeSecretKey = $this->stripeService->getPrivateKey();
            $items = $req->toArray()['items'] ?? [];
            //dd($items);

            $stripe = new StripeClient($stripeSecretKey);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $this->calculateOrderAmount($items),
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return $this->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Throwable $th) {
            return $this->json(['error' => $th->getMessage()]);
        }
    }

    public function calculateOrderAmount(array $cart)
    {
        return 2500; //$cart['subTotalWithCarrier'] ?? 0;
    }
}