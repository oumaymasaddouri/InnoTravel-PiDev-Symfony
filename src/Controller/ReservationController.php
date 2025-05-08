<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Transport;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository,
        PaginatorInterface $paginator,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');

        $query = $reservationRepository->searchAndFilter($searchTerm, $status, $user);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        return $this->render('reservation/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        TransportRepository $transportRepository
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if there are active transports
        $activeTransports = $transportRepository->findBy(['status' => 'Active']);
        if (empty($activeTransports)) {
            $this->addFlash('error', 'No active transport vehicles available for reservation. Please try again later or contact support.');

            // Create a new transport vehicle for demonstration purposes
            $transport = new Transport();
            $transport->setVehicleType('car');
            $transport->setCarModel('Demo Car');
            $transport->setCarColor('Black');
            $transport->setLicensePlate('123tunis456');
            $transport->setMaxLuggage(3);
            $transport->setStatus('Active');

            $entityManager->persist($transport);
            $entityManager->flush();

            $this->addFlash('success', 'A demo vehicle has been created for testing purposes.');

            // Refresh the active transports list
            $activeTransports = $transportRepository->findBy(['status' => 'Active']);
        }

        // Check if a transport ID was provided in the request
        $transportId = $request->query->get('transport');
        $selectedTransport = null;

        if ($transportId) {
            $selectedTransport = $transportRepository->find($transportId);
            if (!$selectedTransport) {
                $this->addFlash('error', 'The selected vehicle was not found.');
                return $this->redirectToRoute('app_transport_index');
            }

            if ($selectedTransport->getStatus() !== 'Active') {
                $this->addFlash('error', 'The selected vehicle is not available for reservation.');
                return $this->redirectToRoute('app_transport_index');
            }
        }

        $reservation = new Reservation();
        $reservation->setUser($user);

        // If a transport was selected, pre-select it in the form
        if ($selectedTransport) {
            $reservation->setTransport($selectedTransport);
            $this->addFlash('info', 'Vehicle "' . $selectedTransport->getCarModel() . '" has been pre-selected for your reservation.');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If the transport field was disabled and we have a transport_id hidden field,
            // make sure to set the transport from the hidden field
            if ($selectedTransport && $form->has('transport_id')) {
                $transportId = $form->get('transport_id')->getData();
                $transport = $transportRepository->find($transportId);
                if ($transport) {
                    $reservation->setTransport($transport);
                }
            }

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Your reservation has been created successfully and is pending confirmation.');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(
        Reservation $reservation,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if the reservation belongs to the user
        if ($reservation->getUser() !== $user && !$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to view this reservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if the reservation belongs to the user
        if ($reservation->getUser() !== $user && !$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to edit this reservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Check if the reservation is already confirmed or canceled
        if ($reservation->getStatus() !== 'pending' && !$session->get('admin')) {
            $this->addFlash('error', 'You cannot edit a reservation that is already confirmed or canceled.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Your reservation has been updated successfully.');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if the reservation belongs to the user
        if ($reservation->getUser() !== $user && !$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to delete this reservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Your reservation has been deleted successfully.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if the reservation belongs to the user
        if ($reservation->getUser() !== $user && !$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to cancel this reservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus('canceled');
            $entityManager->flush();

            $this->addFlash('success', 'Your reservation has been canceled successfully.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/ticket', name: 'app_reservation_ticket', methods: ['GET'])]
    public function generateTicket(
        Reservation $reservation,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if the reservation belongs to the user
        if ($reservation->getUser() !== $user && !$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to view this ticket.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Check if the reservation is confirmed
        if ($reservation->getStatus() !== 'confirmed') {
            $this->addFlash('error', 'You can only generate tickets for confirmed reservations.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        // Generate HTML for the ticket
        $html = $this->renderView('reservation/ticket_pdf.html.twig', [
            'reservation' => $reservation,
            'user' => $user,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Generate a filename
        $filename = 'reservation-ticket-'.$reservation->getId().'.pdf';

        // Return the PDF as a response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
