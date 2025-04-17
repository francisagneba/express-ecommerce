<?php

namespace App\Controller;

use App\Entity\Address;
use App\Services\CartServices;
use App\Repository\OrderRepository;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccountController extends AbstractController
{
    private CartServices $cartServices;
    private OrderRepository $orderRepo;
    private $session;

    public function __construct(
        CartServices $cartServices,
        RequestStack $requestStack,
        OrderRepository $orderRepo
    ) {
        $this->cartServices = $cartServices;
        $this->orderRepo = $orderRepo;
        $this->session = $requestStack->getSession();
    }

    #[Route('/account', name: 'app_account')]
    public function index(AddressRepository $addressRepository): Response
    {
        $cart = $this->cartServices->getFullCart();

        if (empty($cart["items"])) {
            return $this->redirectToRoute('app_home');
        }

        $user = $this->getUser();

        if (!$user) {
            $this->session->set("next", "app_account");
            return $this->redirectToRoute("app_login");
        }

        $cart_json = json_encode($cart);

        // Tu peux commenter ou ajuster si `createOrder` est temporaire
        // $orderId = $this->createOrder($cart);
        $orderId = null;

        $addresses = $addressRepository->findByUser($user);

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'addresses' => $addresses,
            'cart' => $cart,
            'orderId' => $orderId,
            'cart_json' => $cart_json,
        ]);
    }

    // Si tu veux activer cette route plus tard
    // #[Route('/api/address', name: 'app_api_address', methods: ['POST'])]
    // public function postAddress(
    //     Request $req,
    //     AddressRepository $addressRepository,
    //     EntityManagerInterface $manager
    // ): Response {
    //     $formData = $req->getPayload();
    //     $user = $this->getUser();

    //     $address = new Address();
    //     $address->setName($formData->get('name'))
    //         ->setClientName($formData->get('client_name'))
    //         ->setStreet($formData->get('street'))
    //         ->setCodePostal($formData->get('code_postal'))
    //         ->setCity($formData->get('city'))
    //         ->setState($formData->get('state'))
    //         ->setUser($user);

    //     $manager->persist($address);
    //     $manager->flush();

    //     $addresses = $addressRepository->findByUser($user);

    //     foreach ($addresses as $key => $address) {
    //         $address->setUser(null);
    //         $addresses[$key] = $address;
    //     }

    //     return $this->json($addresses);
    // }
}
