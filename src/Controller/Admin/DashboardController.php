<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Entity\User;
use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Product;
use App\Entity\Setting;
use App\Entity\Sliders;
use App\Entity\Category;
use App\Entity\Collections;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Admin\ProductCrudController;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(ProductCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Express Ecommerce');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Products', 'fas fa-list', Product::class);
        yield MenuItem::linkToCrud('Categories', 'fas fa-tag', Category::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Addresses', 'fas fa-address-card', Address::class);
        yield MenuItem::linkToCrud('Pages', 'fas fa-file', Page::class);
        yield MenuItem::linkToCrud('Sliders', 'fas fa-image', Sliders::class);
        yield MenuItem::linkToCrud('Carriers', 'fas fa-car', Carrier::class);
        yield MenuItem::linkToCrud('Collections', 'fas fa-panorama', Collections::class);
        yield MenuItem::linkToCrud('Settings', 'fas fa-gear', Setting::class);
    }
}
