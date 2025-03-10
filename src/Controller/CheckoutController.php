<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Services\CartServices;
use App\Services\StripeService;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    private $cartServices;
    private $stripeService;
    private $session;
    private $em;

    public function __construct(
        CartServices $cartServices,
        RequestStack $requestStack,
        StripeService $stripeService,
        EntityManagerInterface $em
    ) {
        $this->cartServices = $cartServices;
        $this->stripeService = $stripeService;
        $this->session = $requestStack->getSession();
        $this->em = $em;
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function index(AddressRepository $addressRepository): Response
    {
        $cart = $this->cartServices->getFullCart();

        if (empty($cart["items"])) {
            return $this->redirectToRoute('app_home');
        }

        $user = $this->getUser();

        if (!$user) {
            $this->session->set("next", "app_checkout");
            return $this->redirectToRoute("app_login");
        }

        $addresses = $addressRepository->findByUser($user);
        $cart_json = json_encode($cart);

        $orderId = $this->createOrder($cart);

        $public_key = $this->stripeService->getPublicKey();

        return $this->render('checkout/index.html.twig', [
            'controller_name' => 'CheckoutController',
            'cart' => $cart,
            'orderId' => $orderId,
            'cart_json' => $cart_json,
            'addresses' => $addresses,
            'public_key' => $public_key,
        ]);
    }

    public function createOrder(array $cart): int
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw new \Exception("Utilisateur non connecté");
        }

        $order = new Order();

        $carrier = $cart["carrier"] ?? [
            "name" => "Inconnu",
            "price" => 0,
            "id" => 0
        ];

        $order->setClientName($user->getFullName())
            ->setBillingAddress("")
            ->setShippingAddress("")
            ->setOrderCost($cart["data"]["subTotalTTC"] ?? 0)
            ->setTaxe($cart["data"]["taxe"] ?? 0)
            ->setOrderCostTtc($cart["data"]["subTotalWithCarrier"] ?? 0)
            ->setCarrierName($cart["data"]["carrier_name"] ?? "Inconnu")
            ->setCarrierPrice($cart["data"]["carrier_price"] ?? 0)
            ->setCarrierId(isset($cart["data"]["carrier_id"]) ? (int) $cart["data"]["carrier_id"] : 0)
            ->setQuantity($cart["data"]["quantity"] ?? 0)
            ->setStatus("pending"); // Ajout d'un statut par défaut

        $this->em->persist($order);

        foreach ($cart["items"] as $item) {
            $product = $item["product"];
            $orderDetails = new OrderDetails();
            $orderDetails->setProductName($product['name'] ?? "Produit inconnu")
                ->setProductDescription($product['description'] ?? "")
                ->setProductSoldePrice($product['soldePrice'] ?? 0)
                ->setProductRegularPrice($product['regularPrice'] ?? 0)
                ->setQuantity($item['quantity'] ?? 0)
                ->setSubtotal($item['sub_total'] ?? 0)
                ->setTaxe($item['taxe'] ?? 0)
                ->setMyOrder($order);

            $this->em->persist($orderDetails);
        }

        $this->em->flush();

        return $order->getId();
    }
}
