<?php

namespace App\Controller\Api;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiOrderController extends AbstractController
{
    #[Route('/api/order', name: 'app_api_order', methods: ["POST"])]
    public function update(Request $req, OrderRepository $orderRepo, EntityManagerInterface $em): Response
    {
        $data = $req->getPayload();

        $id = $data->get("orderId");

        $order = $orderRepo->find($id);

        if (!$order) {
            return $this->redirectToRoute('/checkout');
        }

        $order->setBillingAddress($data->get('billing_address'))
            ->setShippingAddress($data->get('shipping_address'));

        $em->persist($order);
        $em->flush();

        return $this->render('api/api_order/index.html.twig', [
            'controller_name' => 'ApiOrderController',
        ]);
    }
}
