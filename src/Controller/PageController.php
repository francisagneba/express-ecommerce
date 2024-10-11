<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/page/{slug}', name: 'app_page')]
    public function index(string $slug, PageRepository $pageRepos): Response
    {
        $page = $pageRepos->findOneBy(["slug" => $slug]);

        if (!$page) {
            //Redirect to error page
            return $this->render('page/not-found.html.twig', [
                'controller_name' => 'PageController',
            ]);
        }

        return $this->render('page/index.html.twig', [
            'controller_name' => 'PageController',
            'page' => $page,
        ]);
    }
}
