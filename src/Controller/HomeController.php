<?php

namespace App\Controller;

use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SettingRepository $settingRepo, Request $request): Response
    {
        $session = $request->getSession();
        $data = $settingRepo->findAll();

        //dd($data);
        $session->set("setting", $data[0]);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
