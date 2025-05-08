<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/reservation')]
class ReservationAdminController extends AbstractController
{
    #[Route('/', name: 'admin_reservation_index', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository,
        PaginatorInterface $paginator,
        Request $request,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');

        $query = $reservationRepository->searchAndFilter($searchTerm, $status);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        // Get statistics for dashboard
        $stats = $reservationRepository->getReservationStatistics();

        return $this->render('reservation_admin/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'admin_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Reservation created successfully.');
            return $this->redirectToRoute('admin_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_admin/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_show', methods: ['GET'])]
    public function show(
        int $id,
        ReservationRepository $reservationRepository,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the reservation entity
        $reservation = $reservationRepository->find($id);

        // If not found, redirect to index with error message
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        return $this->render('reservation_admin/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the reservation entity
        $reservation = $reservationRepository->find($id);

        // If not found, redirect to index with error message
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        $form = $this->createForm(ReservationType::class, $reservation, [
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Reservation updated successfully.');
            return $this->redirectToRoute('admin_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_admin/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the reservation entity
        $reservation = $reservationRepository->find($id);

        // If not found, redirect to index with error message
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Reservation deleted successfully.');
        }

        return $this->redirectToRoute('admin_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/status/{status}', name: 'admin_reservation_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        int $id,
        string $status,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the reservation entity
        $reservation = $reservationRepository->find($id);

        // If not found, redirect to index with error message
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        if ($this->isCsrfTokenValid('status'.$reservation->getId(), $request->request->get('_token'))) {
            if (in_array($status, ['pending', 'confirmed', 'canceled'])) {
                $reservation->setStatus($status);
                $entityManager->flush();

                $this->addFlash('success', 'Reservation status updated to ' . ucfirst($status) . '.');
            } else {
                $this->addFlash('error', 'Invalid status.');
            }
        }

        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/statistics', name: 'admin_reservation_statistics', methods: ['GET'])]
    public function statistics(
        ReservationRepository $reservationRepository,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Get statistics
        $stats = $reservationRepository->getReservationStatistics();

        // Get total reservations
        $totalReservations = $reservationRepository->count([]);

        return $this->render('reservation_admin/statistics.html.twig', [
            'stats' => $stats,
            'totalReservations' => $totalReservations,
        ]);
    }
}
