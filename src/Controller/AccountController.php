<?php

namespace App\Controller;

use App\Entity\Address;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(AddressRepository $addressRepository): Response
    {
        $user = $this->getUser();

        $addresses = $addressRepository->findByUser($user);

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'addresses' => $addresses,
        ]);
    }
    // #[Route('/api/address', name: 'app_api_address', methods: ['POST'])]
    // public function postAddress(
    //     Request $req,
    //     AddressRepository $addressRepository,
    //     EntityManagerInterface  $manager
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
    //         ->setUser($user)

    //     ;
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