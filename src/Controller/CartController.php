<?php

namespace App\Controller;

use App\Services\CartServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class CartController extends AbstractController
{
    private CartServices $cartServices;

    public function __construct(CartServices $cartServices)
    {
        $this->cartServices = $cartServices;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(): Response
    {
        $cart = $this->cartServices->getFullCart();

        // Assurez-vous que le panier n'est pas vide avant d'essayer de l'afficher
        if (!isset($cart['items']) || empty($cart['items'])) {
            return $this->redirectToRoute('app_home');
        }

        // Encodage JSON du panier pour utilisation côté client
        $cart_json = json_encode($cart);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cart_json' => $cart_json
        ]);
    }

    #[Route('/cart/add/{id}/{count}', name: 'app_add_to_cart')]
    public function addToCart(int $id, int $count = 1, Request $request): Response
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
                    'cart' => $this->cartServices->getFullCart() // Vérifie que cela renvoie le bon format
                ], Response::HTTP_OK);
            }

            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




    #[Route('/cart/delete/{id}/1', name: 'cart_delete')]
    public function deleteFromCart(int $id, Request $request): Response
    {
        $this->cartServices->deleteFromCart($id);

        // Si la requête est Ajax, on renvoie une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'status' => 'success',
                'message' => 'Product removed from cart',
                'cart' => $this->cartServices->getFullCart()
            ]);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/delete-all/{id}/{count}', name: 'cart_delete_all')]
    public function deleteAllToCart(int $id, Request $request, int $count = 1): Response
    {
        $this->cartServices->deleteAllToCart($id, $count);

        // Si la requête est Ajax, on renvoie une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'status' => 'success',
                'message' => 'All products removed from cart',
                'cart' => $this->cartServices->getFullCart()
            ]);
        }

        return $this->redirectToRoute('app_cart');
    }



    #[Route('/cart/get', name: 'app_get_cart')]
    public function getCart(): Response
    {
        $cart = $this->cartServices->getFullCart();

        // Vérification que le panier existe et contient des articles
        if (!$cart || !isset($cart['items'])) {
            return $this->json([
                'items' => [],
                'cart_count' => 0,
                'sub_total' => 0,
                'total' => 0, // Assurez-vous d'inclure le total
            ], Response::HTTP_OK);
        }

        // Calculer le sous-total et le total si ce n'est pas déjà fait dans getFullCart()
        $subTotal = 0;
        foreach ($cart['items'] as $item) {
            $subTotal += $item['sub_total']; // Assurez-vous que sub_total existe dans chaque item
        }

        // Ajoutez d'autres calculs si nécessaire pour le total
        $total = $subTotal; // Ajustez si vous avez des frais d'expédition ou des taxes

        // Ajoutez sub_total et total à la réponse
        return $this->json([
            'items' => $cart['items'],
            'cart_count' => count($cart['items']),
            'sub_total' => $subTotal,
            'total' => $total,
        ], Response::HTTP_OK);
    }
}