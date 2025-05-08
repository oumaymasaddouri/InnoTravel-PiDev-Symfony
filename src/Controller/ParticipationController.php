<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Entity\User;
use App\Repository\ParticipationRepository;
use App\Repository\UserRepository;
use App\Service\EventEmailService;
use App\Service\QrCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/participations')]
class ParticipationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ParticipationRepository $participationRepository;
    private QrCodeGenerator $qrCodeGenerator;
    private EventEmailService $emailService;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParticipationRepository $participationRepository,
        QrCodeGenerator $qrCodeGenerator,
        EventEmailService $emailService,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->participationRepository = $participationRepository;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
    }

    private function getCurrentUser(Request $request): ?User
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->userRepository->find($userId);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->getSession()->get('admin') === true;
    }

    #[Route('/', name: 'participations_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Check if user is logged in
        $currentUser = $this->getCurrentUser($request);
        if (!$currentUser) {
            // Store the intended destination in the session
            $this->addFlash('info', 'Please log in to view your tickets.');

            // Redirect to login page with a return URL
            return $this->redirectToRoute('app_login', [
                'returnTo' => $request->getUri()
            ]);
        }

        $page = $request->query->getInt('page', 1);

        $result = $this->participationRepository->findByUserWithPagination(
            $currentUser,
            $page,
            10
        );

        return $this->render('participation/index.html.twig', [
            'participations' => $result['participations'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'user' => $currentUser
        ]);
    }

    #[Route('/{id}', name: 'participations_show', methods: ['GET'])]
    public function show(Request $request, Participation $participation): Response
    {
        // Check if user is logged in
        $currentUser = $this->getCurrentUser($request);
        if (!$currentUser && !$this->isAdmin($request)) {
            $this->addFlash('info', 'Please log in to view ticket details.');
            return $this->redirectToRoute('app_login', [
                'returnTo' => $request->getUri()
            ]);
        }

        // Security check - only the owner can see their participation
        if ($participation->getUser() !== $currentUser && !$this->isAdmin($request)) {
            throw $this->createAccessDeniedException('You cannot access this participation.');
        }

        $qrCodeDataUri = $this->qrCodeGenerator->generateTicketQrCode($participation);

        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
            'qrCode' => $qrCodeDataUri,
            'user' => $currentUser
        ]);
    }

    #[Route('/{id}/cancel', name: 'participations_cancel', methods: ['POST'])]
    public function cancel(Request $request, Participation $participation): Response
    {
        // Check if user is logged in
        $currentUser = $this->getCurrentUser($request);
        if (!$currentUser) {
            $this->addFlash('info', 'Please log in to cancel your registration.');
            return $this->redirectToRoute('app_login');
        }

        // Security check - only the owner can cancel their participation
        if ($participation->getUser() !== $currentUser) {
            throw $this->createAccessDeniedException('You cannot cancel this participation.');
        }

        if ($this->isCsrfTokenValid('cancel'.$participation->getId(), $request->request->get('_token'))) {
            $event = $participation->getEvent();

            // Update available spots
            $event->setAvailableSpots($event->getAvailableSpots() + $participation->getNumberOfPersons());

            $this->entityManager->remove($participation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your registration has been cancelled.');
        }

        return $this->redirectToRoute('participations_index');
    }

    #[Route('/verify/{ticketCode}', name: 'participation_verify', methods: ['GET', 'POST'])]
    public function verify(Request $request, string $ticketCode): Response
    {
        // Check if user is admin
        if (!$this->isAdmin($request)) {
            $this->addFlash('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute('app_login');
        }

        $participation = $this->participationRepository->findByTicketCode($ticketCode);

        if (!$participation) {
            $this->addFlash('error', 'Invalid ticket code.');
            return $this->redirectToRoute('admin_events_index');
        }

        if ($request->isMethod('POST')) {
            if (!$participation->isAttended()) {
                $participation->setAttended(true);
                $this->entityManager->flush();

                // Send attendance confirmation email
                $this->emailService->sendAttendanceConfirmationEmail($participation);

                $this->addFlash('success', 'Attendance confirmed successfully.');
            } else {
                $this->addFlash('info', 'This ticket has already been used.');
            }

            return $this->redirectToRoute('participation_verify', ['ticketCode' => $ticketCode]);
        }

        return $this->render('participation/verify.html.twig', [
            'participation' => $participation
        ]);
    }
}
