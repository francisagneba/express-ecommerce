<?php

namespace App\Controller\Api;

use App\Entity\Address;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class ApiAddressController extends AbstractController
{
    #[Route('/address', name: 'app_api_post_address', methods: ['POST'])]
    public function index(
        Request $req,
        AddressRepository $addressRepository,
        EntityManagerInterface  $manager
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Not authorized",
                "data" => []
            ]);
        }

        $formData = json_decode($req->getContent(), true); // Correct way to get JSON data

        if (!$formData) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Invalid data",
                "data" => []
            ]);
        }

        $address = new Address();
        $address->setName($formData['name'] ?? null)
            ->setClientName($formData['client_name'] ?? null)
            ->setStreet($formData['street'] ?? null)
            ->setCodePostal($formData['code_postal'] ?? null)
            ->setCity($formData['city'] ?? null)
            ->setState($formData['state'] ?? null)
            ->setUser($user);

        $manager->persist($address);
        $manager->flush();

        $addresses = $addressRepository->findBy(['user' => $user]);

        foreach ($addresses as $key => $addr) {
            $addr->setUser(null);
            $addresses[$key] = $addr;
        }

        return $this->json([
            "isSuccess" => true,
            "data" => $addresses
        ]);
    }

    #[Route('/address/{id}', name: 'app_api_delete_address', methods: ['DELETE'])]
    public function delete(
        $id,
        AddressRepository $addressRepository,
        EntityManagerInterface  $manager
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Not authorized",
                "data" => []
            ]);
        }

        $address = $addressRepository->find($id); // Correct method

        if (!$address) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Address not found!",
                "data" => []
            ]);
        }

        if ($user !== $address->getUser()) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Not authorized!",
                "data" => []
            ]);
        }

        $manager->remove($address);
        $manager->flush();

        $addresses = $addressRepository->findBy(['user' => $user]);

        foreach ($addresses as $key => $addr) {
            $addr->setUser(null);
            $addresses[$key] = $addr;
        }

        return $this->json([
            "isSuccess" => true,
            "data" => $addresses
        ]);
    }

    #[Route('/address/{id}', name: 'app_api_put_address', methods: ['PUT'])]
    public function update(
        $id,
        Request $req,
        AddressRepository $addressRepository,
        EntityManagerInterface  $manager
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Not authorized",
                "data" => []
            ]);
        }

        $address = $addressRepository->find($id); // Correct method

        if (!$address) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Address not found!",
                "data" => []
            ]);
        }

        if ($user !== $address->getUser()) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Not authorized!",
                "data" => []
            ]);
        }

        $formData = json_decode($req->getContent(), true);

        if (!is_array($formData)) {
            return $this->json([
                "isSuccess" => false,
                "message" => "Invalid JSON format",
                "data" => []
            ]);
        }

        // Start update
        $address->setName($formData['name'] ?? $address->getName())
            ->setClientName(isset($formData['client_name']) ? $formData['client_name'] : $address->getClientName())
            ->setStreet($formData['street'] ?? $address->getStreet())
            ->setCodePostal($formData['code_postal'] ?? $address->getCodePostal())
            ->setCity($formData['city'] ?? $address->getCity())
            ->setState($formData['state'] ?? $address->getState());


        $manager->persist($address);
        $manager->flush();

        $addresses = $addressRepository->findBy(['user' => $user]);

        foreach ($addresses as $key => $addr) {
            $addr->setUser(null);
            $addresses[$key] = $addr;
        }

        return $this->json([
            "isSuccess" => true,
            "data" => $addresses
        ]);
    }
}