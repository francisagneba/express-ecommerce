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
    #[Route('/', name: 'app_home')]
    public function index(
        SettingRepository $settingRepo,
        SlidersRepository $slidersRepo,
        CollectionsRepository $collectionsRepo,
        ProductRepository $productRepo,
        PageRepository $pageRepo,
        Request $request
    ): Response {
        $session = $request->getSession();
        $data = $settingRepo->findAll();
        $sliders = $slidersRepo->findAll();
        $collections = $collectionsRepo->findAll();
        $products = $productRepo->findAll();

        //dd($data);
        $session->set("setting", $data[0]);

        $headerPages = $pageRepo->findBy(['isHead' => true]);
        $footerPages = $pageRepo->findBy(['isFoot' => true]);
        //dd($headerPages);
        $session->set("headerpages", $headerPages);
        $session->set("footerPages", $footerPages);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sliders' => $sliders,
            'collections' => $collections,
            'products' => $products,
        ]);
    }
}
