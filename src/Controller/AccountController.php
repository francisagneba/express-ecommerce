<?php

namespace App\Controller;

use App\Repository\AddressRepository;
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
}
