<?php

namespace App\Controller;

use App\Entity\Hotel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/hotel')]
final class UserHotelController extends AbstractController
{
    #[Route('/', name: 'user_hotel_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $hotels = $entityManager->getRepository(Hotel::class)->findAll();

        return $this->render('hotel/user_index.html.twig', [
            'hotels' => $hotels,
        ]);
    }

    #[Route('/{id}', name: 'user_hotel_show', methods: ['GET'])]
    public function show(Hotel $hotel): Response
    {
        return $this->render('hotel/user_show.html.twig', [
            'hotel' => $hotel,
        ]);
    }
}
