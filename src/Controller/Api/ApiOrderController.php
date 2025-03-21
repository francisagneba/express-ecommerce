<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Address;
use App\Repository\OrderRepository;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiOrderController extends AbstractController
{
    #[Route('/api/order', name: 'app_api_order', methods: ['POST'])]
    public function update(
        Request $req,
        OrderRepository $orderRepo,
        AddressRepository $addressRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($req->getContent(), true);

        if (!$data) {
            return new JsonResponse(["error" => "Données JSON invalides"], Response::HTTP_BAD_REQUEST);
        }

        $id = $data['orderid'] ?? null;

        if (!$id) {
            return new JsonResponse(["error" => "L'ID de la commande est manquant"], Response::HTTP_BAD_REQUEST);
        }

        $order = $orderRepo->find($id);

        if (!$order) {
            return new JsonResponse(["error" => "Commande non trouvée"], Response::HTTP_NOT_FOUND);
        }

        // Récupération des adresses en base
        $billingAddress = isset($data['billing_address']) ? $addressRepo->find($data['billing_address']) : null;
        $shippingAddress = isset($data['shipping_address']) ? $addressRepo->find($data['shipping_address']) : null;

        if (!$billingAddress || !$shippingAddress) {
            return new JsonResponse(["error" => "Adresse de facturation ou de livraison invalide"], Response::HTTP_BAD_REQUEST);
        }

        $order->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);

        $em->flush();

        return new JsonResponse(["success" => true, "message" => "Commande mise à jour"]);
    }
}