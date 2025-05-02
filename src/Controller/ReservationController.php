<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\QrCodeService;
use Psr\Log\LoggerInterface;

final class ReservationController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'homepage')]
    public function home(
        ReservationRepository $reservationRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response {
        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');

        $query = $reservationRepository->searchAndFilter($searchTerm, $status);

        $reservations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status
        ]);
    }

    #[Route('/reservation', name: 'app_reservation_index', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response {
        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');

        $query = $reservationRepository->searchAndFilter($searchTerm, $status);

        $reservations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status
        ]);
    }

    #[Route('/reservation/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Reservation created successfully!');

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/reservation/{id}/ticket', name: 'app_reservation_ticket')]
    public function generateTicket(Reservation $reservation, QrCodeService $qrCodeService): Response
    {
        $qrCodeImage = $qrCodeService->createQrCode(
            $reservation->getPickupAddress(), 
            $reservation->getDestinationAddress(),
            $reservation->getStatus()
        );

        $html = $this->renderView('reservation/ticket_pdf.html.twig', [
            'reservation' => $reservation,
            'qrCode' => $qrCodeImage,
        ]);

        // Configure Dompdf options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="ticket.pdf"',
            ]
        );
    }
}