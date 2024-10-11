<?php

namespace App\Controller;

use App\Repository\CollectionsRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SlidersRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class HomeController extends AbstractController
{
    private $repoProduct;

    public function __construct(ProductRepository $repoProduct)
    {
        $this->repoProduct = $repoProduct;
    }

    #[Route('/', name: 'app_home')]
    public function index(
        SettingRepository $settingRepo,
        SlidersRepository $slidersRepo,
        CollectionsRepository $collectionsRepo,
        PageRepository $pageRepo,
        Request $request
    ): Response {
        $session = $request->getSession();
        $data = $settingRepo->findAll();
        $sliders = $slidersRepo->findAll();
        $collections = $collectionsRepo->findAll();

        //dd($data);
        $session->set("setting", $data[0]);

        $headerPages = $pageRepo->findBy(['isHead' => true]);
        $footerPages = $pageRepo->findBy(['isFoot' => true]);
        //dd($headerPages);

        $productsBestSeller = $this->repoProduct->findBy(['isBestSeller' => true]);
        $productsNewArrival = $this->repoProduct->findBy(['isNewArrival' => true]);
        $productsFeatured = $this->repoProduct->findBy(['isFeatured' => true]);
        $productsSpecialOffer = $this->repoProduct->findBy(['isSpecialOffer' => true]);

        $session->set("headerpages", $headerPages);
        $session->set("footerPages", $footerPages);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sliders' => $sliders,
            'collections' => $collections,
            'productsBestSeller' => $productsBestSeller,
            'productsNewArrival' => $productsNewArrival,
            'productsFeatured' =>  $productsFeatured,
            'productsSpecialOffer' => $productsSpecialOffer,

        ]);
    }
}
