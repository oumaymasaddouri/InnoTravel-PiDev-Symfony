<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Entity\User;
use App\Form\ParticipationType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\EventEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
class EventController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private EventEmailService $emailService;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        EventEmailService $emailService,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
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

    #[Route('/', name: 'events_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search');
        $category = $request->query->get('category');

        $startDate = null;
        if ($request->query->has('start_date')) {
            $startDateStr = $request->query->get('start_date');
            if (!empty($startDateStr)) {
                $startDate = new \DateTime($startDateStr);
            }
        }

        $endDate = null;
        if ($request->query->has('end_date')) {
            $endDateStr = $request->query->get('end_date');
            if (!empty($endDateStr)) {
                $endDate = new \DateTime($endDateStr);
                // Set to end of day
                $endDate->setTime(23, 59, 59);
            }
        }

        $result = $this->eventRepository->findActiveEvents(
            $page,
            9, // Show 9 events per page for grid layout
            $search,
            $category,
            $startDate,
            $endDate
        );

        $categories = $this->eventRepository->findAllCategories();
        $upcomingEvents = $this->eventRepository->findUpcomingEvents(5);
        $currentUser = $this->getCurrentUser($request);

        return $this->render('event/index.html.twig', [
            'events' => $result['events'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'search' => $search,
            'category' => $category,
            'startDate' => $startDate ? $startDate->format('Y-m-d') : null,
            'endDate' => $endDate ? $endDate->format('Y-m-d') : null,
            'categories' => $categories,
            'upcomingEvents' => $upcomingEvents,
            'user' => $currentUser
        ]);
    }

    #[Route('/{id}', name: 'events_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Event $event): Response
    {
        // Check if event is active
        if (!$event->isIsActive()) {
            $this->addFlash('error', 'This event is no longer available.');
            return $this->redirectToRoute('events_index');
        }

        $participation = new Participation();
        $participation->setEvent($event);

        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $this->getCurrentUser($request);
            if (!$currentUser) {
                $this->addFlash('error', 'You must be logged in to register for an event.');
                return $this->redirectToRoute('app_login');
            }

            // Check if event is full
            if ($event->isFull()) {
                $this->addFlash('error', 'This event is fully booked.');
                return $this->redirectToRoute('events_show', ['id' => $event->getId()]);
            }

            // Check if requested number of persons exceeds available spots
            if ($participation->getNumberOfPersons() > $event->getAvailableSpots()) {
                $this->addFlash('error', "Only {$event->getAvailableSpots()} spots available.");
                return $this->redirectToRoute('events_show', ['id' => $event->getId()]);
            }

            $participation->setUser($currentUser);

            $this->entityManager->persist($participation);

            // Update available spots
            $event->setAvailableSpots($event->getAvailableSpots() - $participation->getNumberOfPersons());

            $this->entityManager->flush();

            // Send ticket email
            $this->emailService->sendTicketEmail($participation);

            $this->addFlash('success', 'Registration successful! Check your email for the ticket.');
            return $this->redirectToRoute('participations_show', ['id' => $participation->getId()]);
        }

        $upcomingEvents = $this->eventRepository->findUpcomingEvents(3);
        $currentUser = $this->getCurrentUser($request);

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'upcomingEvents' => $upcomingEvents,
            'user' => $currentUser
        ]);
    }

    #[Route('/category/{category}', name: 'events_by_category', methods: ['GET'])]
    public function byCategory(Request $request, string $category): Response
    {
        $page = $request->query->getInt('page', 1);

        $result = $this->eventRepository->findActiveEvents(
            $page,
            9,
            null,
            $category
        );

        $categories = $this->eventRepository->findAllCategories();
        $upcomingEvents = $this->eventRepository->findUpcomingEvents(5);
        $currentUser = $this->getCurrentUser($request);

        return $this->render('event/index.html.twig', [
            'events' => $result['events'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'category' => $category,
            'categories' => $categories,
            'categoryTitle' => $category,
            'upcomingEvents' => $upcomingEvents,
            'user' => $currentUser
        ]);
    }
}
