<?php

namespace App\Controller\Api;

use App\Repository\OrderRepository;
use Stripe\StripeClient;
use App\Services\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ApiStripeController extends AbstractController
{
    private $stripeService;
    private $orderRepo;

    public function __construct(StripeService $stripeService, OrderRepository $orderRepo)
    {
        $this->stripeService = $stripeService;
        $this->orderRepo = $orderRepo;
    }

    #[Route('/api/stripe/payment-intent/{orderId}', name: 'app_stripe_payment-intent', methods: ['POST'])]
    public function index(Request $req, string $orderId, EntityManagerInterface $em): Response
    {
        try {
            $stripeSecretKey = $this->stripeService->getPrivateKey();

            $order = $this->orderRepo->find($orderId);

            if (!$order) {
                return $this->json(['error' => "Order not found!"], Response::HTTP_NOT_FOUND);
            }

            $stripe = new StripeClient($stripeSecretKey);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $order->getOrderCostTtc() * 100, // Stripe attend des centimes
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $order->setStripeClientSecret($paymentIntent->client_secret);
            $em->persist($order);
            $em->flush();

            return $this->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Throwable $th) {
            return $this->json(['error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
