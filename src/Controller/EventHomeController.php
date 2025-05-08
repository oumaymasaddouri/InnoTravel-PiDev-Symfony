<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventHomeController extends AbstractController
{
    #[Route('/events/home', name: 'events_home')]
    public function index(): Response
    {
        return $this->render('event/home.html.twig');
    }
}
