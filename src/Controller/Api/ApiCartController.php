<?php

namespace App\Controller\Api;

use App\Repository\CarrierRepository;
use App\Services\CartServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiCartController extends AbstractController
{
    #[Route('/api/cart/update/carrier/{id}', name: 'app_api_api_cart', methods: ['GET'])]
    public function index($id, CarrierRepository $carrierRepo, CartServices $cartServices): Response
    {
        $carrier = $carrierRepo->findOneById($id);

        if (!$carrier) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Carrier noy found !"
            ]);
        }
        $cartServices->update("carrier", [
            "id" => $carrier->getId(),
            "name" => $carrier->getName(),
            "description" => $carrier->getDescription(),
            "price" => $carrier->getPrice(),
        ]);
        $cart = $cartServices->getFullCart();

        return $this->json([
            "isSuccess" => true,
            "data" => $cart
        ]);
    }
}
