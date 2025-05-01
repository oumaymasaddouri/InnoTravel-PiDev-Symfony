<?php

namespace App\Controller\AdminCrudUser;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('InnoTravel Admin Panel')
            ->renderContentMaximized(); // Make the content full-width for better view
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('🏠 Dashboard', 'fa fa-home', $this->generateUrl('admin_home', [], UrlGeneratorInterface::ABSOLUTE_URL));
        yield MenuItem::section('Manage Users');
        yield MenuItem::linkToCrud('👤 Travelers', 'fas fa-users', User::class);
        yield MenuItem::section('Analytics');
        yield MenuItem::linkToRoute('📊 Statistics', 'fas fa-chart-pie', 'admin_travelers_statistics');
        yield MenuItem::section('Other');
        yield MenuItem::linkToUrl('🔙 Back to Website', 'fas fa-arrow-left', $this->generateUrl('admin_home', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
