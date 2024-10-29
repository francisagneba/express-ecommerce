<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
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
        CategoryRepository $categoryRepo,
        PageRepository $pageRepo,
        Request $request
    ): Response {
        $session = $request->getSession();
        $data = $settingRepo->findAll();
        $sliders = $slidersRepo->findAll();
        $collections = $collectionsRepo->findBy(['isMega' => false]);
        $megacollections = $collectionsRepo->findBy(['isMega' => true]);
        $categories = $categoryRepo->findBy(['isMega' => true]);

        //dd($data);
        $session->set("setting", $data[0]);

        $headerPages = $pageRepo->findBy(['isHead' => true]);
        $footerPages = $pageRepo->findBy(['isFoot' => true]);
        //dd($headerPages);

        $productsBestSeller = $this->repoProduct->findBy(['isBestSeller' => true]);
        $productsNewArrival = $this->repoProduct->findBy(['isNewArrival' => true]);
        $productsFeatured = $this->repoProduct->findBy(['isFeatured' => true]);
        $productsSpecialOffer = $this->repoProduct->findBy(['isSpecialOffer' => true]);

        // ici on met ça dans la session

        $session->set("headerpages", $headerPages);
        $session->set("footerPages", $footerPages);
        $session->set("categories", $categories);
        $session->set("megaCollections", $megacollections);

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

    #[Route('/product/{slug}', name: 'app_product_by_slug')]
    public function showProduct(string $slug)
    {
        $product = $this->repoProduct->findOneBy(['slug' => $slug]);

        if (!$product) {
            //error
            return $this->redirectToRoute('app_error');
        }

        return $this->render('product/show_product_by_slug.html.twig', [

            'product' => $product,
        ]);
    }

    #[Route('/product/get/{id}', name: 'app_product_by_id')]
    public function getProductById(string $id)
    {
        $product = $this->repoProduct->findOneBy(['id' => $id]);

        if (!$product) {
            // error
            return $this->json(false);
        }

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'imageUrls' => $product->getImageUrls(),
            'soldePrice' => $product->getSoldePrice(),
            'regularPrice' => $product->getRegularPrice(),
        ]);
    }

    #[Route('/error}', name: 'app_error')]
    public function errorPage()
    {

        //Redirect to error page
        return $this->render('page/not-found.html.twig', [
            'controller_name' => 'PageController',
        ]);
    }
}