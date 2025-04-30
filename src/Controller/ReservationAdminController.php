<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\Reservation1Type;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation/admin')]
final class ReservationAdminController extends AbstractController
{
    
    #[Route('/', name: 'app_reservation_admin_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository, PaginatorInterface $paginator): Response 
    {
        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');
    
        $queryBuilder = $reservationRepository->createSearchQueryBuilder($searchTerm, $status);
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );
    
        // Get counts for statistics
        $totalReservations = $reservationRepository->count([]);
        $confirmedCount = $reservationRepository->getCountByStatus('Confirmed');
        $pendingCount = $reservationRepository->getCountByStatus('Pending');
        $cancelledCount = $reservationRepository->getCountByStatus('Cancelled'); // Fix typo if needed
        
        // Create structured data for the chart
        $statusStats = [
            ['status' => 'Confirmed', 'count' => $confirmedCount],
            ['status' => 'Pending', 'count' => $pendingCount],
            ['status' => 'Cancelled', 'count' => $cancelledCount]
        ];
    
        return $this->render('reservation_admin/index.html.twig', [
            'pagination' => $pagination,
            'totalReservations' => $totalReservations,
            'confirmedCount' => $confirmedCount,
            'pendingCount' => $pendingCount,
            'cancelledCount' => $cancelledCount,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status,
            'statusStats' => $statusStats, // Changed from topStatus to statusStats
        ]);
    }
    #[Route('/new', name: 'app_reservation_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(Reservation1Type::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_admin/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_admin_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation_admin/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Reservation1Type::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_admin/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}
