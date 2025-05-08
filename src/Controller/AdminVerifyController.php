<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/verify')]
#[IsGranted('ROLE_ADMIN')]
class AdminVerifyController extends AbstractController
{
    #[Route('/', name: 'admin_verify_index')]
    public function index(): Response
    {
        return $this->render('admin/event/verify.html.twig');
    }
}
