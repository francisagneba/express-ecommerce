<?php

namespace App\Controller;

use App\Services\WishListService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WishListController extends AbstractController
{
    public function __construct(
        private WishListService $wishlistService,
    ) {
        $this->wishlistService = $wishlistService;
    }

    #[Route('/wishlist', name: 'app_wish_list')]
    public function index(): Response
    {
        $wishlist = $this->wishlistService->getWishListDetails();

        $wishlist_json = json_encode($wishlist);

        return $this->render('wish_list/index.html.twig', [
            'controller_name' => 'WishListController',
            'wishlist' => $wishlist,
            'wishlist_json' => $wishlist_json,
        ]);
    }

    #[Route('/wishlist/add/{productId}', name: 'app_add_to_wishlist')]
    public function addToWishList(string $productId): Response
    {
        $this->wishlistService->addToWishList($productId);
        $wishlist = $this->wishlistService->getWishListDetails();

        //return $this->redirectToRoute("app_wish_list");

        return $this->json($wishlist);
    }
    #[Route('/wishlist/remove/{productId}', name: 'app_remove_to_wishlist')]
    public function removeToWishList(string $productId): Response
    {
        $this->wishlistService->removeToWishList($productId);
        $wishlist = $this->wishlistService->getWishListDetails();

        //return $this->redirectToRoute("app_wish_list");

        return $this->json($wishlist);
    }
    #[Route('/wishlist/get', name: 'app_get_wishlist')]
    public function getWishList(): Response
    {
        $wishlist = $this->wishlistService->getWishListDetails();

        return $this->json($wishlist);
    }
}
