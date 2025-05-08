<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminHomeController extends AbstractController
{
    #[Route('/', name: 'admin_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/dashboard', name: 'admin')]
    public function dashboard(): Response
    {
        return $this->render('admin/home.html.twig');
    }
}
