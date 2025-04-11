<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/', name: 'user_dashboard')]
    public function index(ManagerRegistry $doctine): Response
    {

        return $this->render('User/index.html.twig', [
        ]);
    } 

    #[Route('/about', name: 'about')]
    public function about(ManagerRegistry $doctine): Response
    {
        return $this->render('User/about.html.twig', [
        ]);
    } 

    #[Route('/place', name: 'place')]
    public function place(ManagerRegistry $doctine): Response
    {
        return $this->render('User/places.html.twig', [
        ]);
    } 

    #[Route('/service', name: 'service')]
    public function service(ManagerRegistry $doctine): Response
    {
        return $this->render('User/service.html.twig', [
        ]);
    }

    #[Route('/blog', name: 'blog')]
    public function blog(ManagerRegistry $doctine): Response
    {
        return $this->render('User/blog.html.twig', [
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(ManagerRegistry $doctine): Response
    {
        return $this->render('User/contact.html.twig', [
        ]);
    }
}