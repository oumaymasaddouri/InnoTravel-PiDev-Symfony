<?php

namespace App\Controller;

use App\Entity\Trip;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(ManagerRegistry $doctine): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not authorized to access this resource.');
        }

        return $this->render('Admin/index.html.twig', [
        ]);
    } 

    #[Route('/trip/status-count', name: 'trip_status_count', options:['expose' => true])]
    public function statusCount(ManagerRegistry $doctrine): JsonResponse
    {
        $em = $doctrine->getManager();

        // Get the count of trips by status
        $tripRepository = $em->getRepository(Trip::class);

        $statusCounts = $tripRepository->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) AS count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // Get the total count of trips
        $totalTrips = $tripRepository->count([]);

        return new JsonResponse([
            'statusCounts' => $statusCounts,
            'totalTrips' => $totalTrips
        ]);
    }
}