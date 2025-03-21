<?php

namespace App\Controller\Api;

use App\Repository\OrderRepository;
use App\Services\PaypalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class ApiPaypalController extends AbstractController
{
    private $paypalService;
    private $client;
    private $paypal_public_key;
    private $paypal_private_key;
    private $base;

    public function __construct(PaypalService $paypalService, HttpClientInterface $client)
    {
        $this->paypalService = $paypalService;
        $this->paypal_public_key = $this->paypalService->getPublicKey();
        $this->paypal_private_key = $this->paypalService->getPrivateKey();
        $this->base = $this->paypalService->getBaseUrl();
        $this->client = $client;
    }

    #[Route('/api/paypal/orders', name: 'app_paypal_orders', methods: ["POST"])]
    public function index(Request $req, OrderRepository $orderRepo): Response
    {
        $data = json_decode($req->getContent(), true);
        $orderId = $data["orderId"] ?? null;

        if (!$orderId) {
            return $this->json(['error' => "Order ID is missing!"], Response::HTTP_BAD_REQUEST);
        }

        $order = $orderRepo->find($orderId);

        if (!$order) {
            return $this->json(['error' => "Order not found!"], Response::HTTP_NOT_FOUND);
        }

        $result = $this->createOrder($order);

        return $this->json($result['jsonResponse']);
    }

    public function generateAccessToken()
    {
        try {
            if (empty($this->paypal_public_key) || empty($this->paypal_private_key)) {
                throw new Exception("MISSING_API_CREDENTIALS");
            }

            $auth = base64_encode($this->paypal_public_key . ":" . $this->paypal_private_key);

            $response = $this->client->request(
                'POST',
                $this->base . '/v1/oauth2/token',
                [
                    'body' => "grant_type=client_credentials",
                    'headers' => ['Authorization' => "Basic " . $auth]
                ]
            );

            $data = $response->toArray();

            return $data['access_token'] ?? null;
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function createOrder($order)
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['error' => 'Failed to get access token'];
        }

        $url = $this->base . '/v2/checkout/orders';
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $order->getOrderCostTtc() / 100,
                    ],
                ],
            ],
        ];

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'json' => $payload,
        ]);

        return $this->handleResponse($response);
    }

    public function captureOrder($orderID)
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['error' => 'Failed to get access token'];
        }

        $url = $this->base . '/v2/checkout/orders/' . $orderID . '/capture';

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $this->handleResponse($response);
    }

    public function handleResponse($response)
    {
        try {
            $jsonResponse = json_decode($response->getContent(), true);
            return [
                'jsonResponse' => $jsonResponse,
                'httpStatusCode' => $response->getStatusCode(),
            ];
        } catch (Exception $error) {
            throw new Exception((string) $response->getContent());
        }
    }
}
