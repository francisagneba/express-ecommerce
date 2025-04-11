<?php

namespace App\Controller;

use App\Repository\CarrierRepository;
use App\Services\CartServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartController extends AbstractController
{
    private CartServices $cartServices;
    private CarrierRepository $carrierRepo;

    public function __construct(CartServices $cartServices, CarrierRepository $carrierRepo)
    {
        $this->cartServices = $cartServices;
        $this->carrierRepo = $carrierRepo;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request, SessionInterface $session): Response
    {
        // if (!$session->isStarted()) {
        //     $session->start();
        // }

        $cart = $this->cartServices->getFullCart();

        $carriers = $this->carrierRepo->findAll();

        foreach ($carriers as $key => $carrier) {
            $carriers[$key] = [
                "id" => $carrier->getId(),
                "name" => $carrier->getName(),
                "description" => $carrier->getDescription(),
                "price" => $carrier->getPrice(),
            ];
        }

        if (empty($cart['items'])) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Cart is empty'], Response::HTTP_BAD_REQUEST);
            }
            return $this->redirectToRoute('app_home');
        }

        $cart_json = json_encode($cart);
        $carriers_json = json_encode($carriers);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'carriers' => $carriers,
            'cart_json' => $cart_json,
            'carriers_json' => $carriers_json
        ]);
    }

    #[Route('/cart/add/{id}/{count}', name: 'app_add_to_cart')]
    public function addToCart(int $id, Request $request, int $count = 1): Response
    {
        try {
            if (!$request->isXmlHttpRequest()) {
                // Si ce n'est pas une requête AJAX, envoyer une erreur ou rediriger
                return $this->json([
                    'error' => 'Unexpected request type. Please use AJAX.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->cartServices->addToCart($id, $count);

            return $this->json([
                'status' => 'success',
                'message' => 'Product added to cart',
                'cart' => $this->cartServices->getFullCart()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    #[Route('/cart/delete/{id}/1', name: 'cart_delete')]
    public function deleteFromCart(int $id, Request $request): Response
    {
        try {
            $this->cartServices->deleteFromCart($id);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'Product removed from cart',
                    'cart' => $this->cartServices->getFullCart()
                ], Response::HTTP_OK);
            }

            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/delete-all/{id}/{quantity}', name: 'cart_delete_all')]
    public function deleteAllFromCart(int $id, Request $request): Response
    {
        try {
            $this->cartServices->deleteAllToCart($id);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'All products removed from cart',
                    'cart' => $this->cartServices->getFullCart()
                ]);
            }

            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/get', name: 'app_get_cart')]
    public function getCart(): Response
    {
        try {
            $cart = $this->cartServices->getFullCart();

            if (empty($cart['items'])) {
                return $this->json([
                    'error' => 'Cart is empty'
                ], Response::HTTP_OK);
            }

            return $this->json([
                'items' => $cart['items'],
                'cart_count' => count($cart['items']),
                'sub_total' => (float) ($cart['data']['subTotalHT'] ?? 0),
                'total' => (float) ($cart['data']['subTotalTTC'] ?? 0)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/carrier', name: 'app_update_cart_carrier', methods: ["POST"])]
    public function updateCartCarrier(Request $req): Response
    {
        $id = $req->getPayload()->get("carrierId");
        //dd($id);
        $carrier = $this->carrierRepo->findOneById($id);

        if (!$carrier) {
            return $this->redirectToRoute("app_home");
        }
        $this->cartServices->update("carrier", [
            "id" => $carrier->getId(),
            "name" => $carrier->getName(),
            "description" => $carrier->getDescription(),
            "price" => $carrier->getPrice(),
        ]);

        return $this->redirectToRoute("app_cart");

        //return $this->json($cart);
    }
}
