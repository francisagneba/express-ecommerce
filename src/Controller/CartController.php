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
        // Récupère le panier complet
        $cart = $this->cartServices->getFullCart();

        // Si le panier est vide, redirige vers la page d'accueil
        if (empty($cart['items'])) {
            return $this->redirectToRoute('app_home');
        }

        // Convertir les données du panier en JSON pour les utiliser côté client
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
            // Vérification des paramètres d'entrée
            if ($id <= 0 || $count <= 0) {
                return $this->json(['error' => 'Invalid product ID or quantity'], Response::HTTP_BAD_REQUEST);
            }

            // Ajout du produit au panier
            $this->cartServices->addToCart($id, $count);

            // Si la requête est AJAX, renvoyer une réponse JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'Product added to cart',
                    'cart' => $this->cartServices->getFullCart()
                ], Response::HTTP_OK);
            }

            // Si la requête n'est pas AJAX, rediriger vers le panier
            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/delete/{id}/1', name: 'cart_delete')]
    public function deleteFromCart(int $id, Request $request): Response
    {
        try {
            // Suppression du produit du panier
            $this->cartServices->deleteFromCart($id);

            // Si la requête est AJAX, renvoyer une réponse JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'Product removed from cart',
                    'cart' => $this->cartServices->getFullCart()
                ]);
            }

            // Sinon, rediriger vers le panier
            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/delete-all/{id}/{quantity}', name: 'cart_delete_all')]
    public function deleteAllFromCart(int $id, Request $request): Response
    {
        try {
            // Suppression complète du produit du panier
            $this->cartServices->deleteAllToCart($id);

            // Si la requête est AJAX, renvoyer une réponse JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'All products removed from cart',
                    'cart' => $this->cartServices->getFullCart()
                ]);
            }

            // Sinon, rediriger vers le panier
            return $this->redirectToRoute('app_cart');
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/cart/get', name: 'app_get_cart')]
    public function getCart(): Response
    {
        try {
            // Récupérer les informations du panier
            $cart = $this->cartServices->getFullCart();

            // Si le panier est vide, renvoyer une réponse JSON vide
            if (empty($cart['items'])) {
                return $this->json([
                    'items' => [],
                    'cart_count' => 0,
                    'sub_total' => 0,
                    'total' => 0
                ], Response::HTTP_OK);
            }

            // Renvoyer les détails du panier avec sous-total et total
            return $this->json([
                'items' => $cart['items'],
                'cart_count' => count($cart['items']),
                'sub_total' => $cart['data']['subTotalHT'],
                'total' => $cart['data']['subTotalTTC']
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
