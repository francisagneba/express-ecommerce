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
    private CarrierRepository $carrierRipo;

    public function __construct(CartServices $cartServices, CarrierRepository $carrierRipo)
    {
        $this->cartServices = $cartServices;
        $this->carrierRipo = $carrierRipo;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request, SessionInterface $session): Response
    {
        if (!$session->isStarted()) {
            $session->start();
        }

        $cart = $this->cartServices->getFullCart();

        $carriers = $this->carrierRipo->findAll();

        if (empty($cart['items'])) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Cart is empty'], Response::HTTP_BAD_REQUEST);
            }
            return $this->redirectToRoute('app_home');
        }

        $cart_json = json_encode($cart);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'carriers' => $carriers,
            'cart_json' => $cart_json
        ]);
    }

    #[Route('/cart/add/{id}/{count}', name: 'app_add_to_cart')]
    public function addToCart(int $id, Request $request, int $count = 1): Response
    {
        try {
            if ($id <= 0 || $count <= 0) {
                return $this->json(['error' => 'Invalid product ID or quantity'], Response::HTTP_BAD_REQUEST);
            }

            $this->cartServices->addToCart($id, $count);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'Product added to cart',
                    'cart' => $this->cartServices->getFullCart()
                ], Response::HTTP_OK);
            }

            return $this->redirectToRoute('app_cart');
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
}
