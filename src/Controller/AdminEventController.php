<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Organizer;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\OrganizerRepository;
use App\Repository\ParticipationRepository;
use App\Service\EventDescriptionAISuggester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private OrganizerRepository $organizerRepository;
    private ParticipationRepository $participationRepository;
    private EventDescriptionAISuggester $descriptionGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        OrganizerRepository $organizerRepository,
        ParticipationRepository $participationRepository,
        EventDescriptionAISuggester $descriptionGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->organizerRepository = $organizerRepository;
        $this->participationRepository = $participationRepository;
        $this->descriptionGenerator = $descriptionGenerator;
    }

    #[Route('/', name: 'admin_events_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search');
        $category = $request->query->get('category');
        $status = $request->query->get('status');

        $isActive = null;
        if ($status === 'active') {
            $isActive = true;
        } elseif ($status === 'inactive') {
            $isActive = false;
        }

        $result = $this->eventRepository->findAllEventsForAdmin(
            $page,
            10,
            $search,
            $category,
            $isActive
        );

        $categories = $this->eventRepository->findAllCategories();

        return $this->render('admin/event/index.html.twig', [
            'events' => $result['events'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'categories' => $categories
        ]);
    }

    #[Route('/new', name: 'admin_events_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate AI description if requested
            if ($request->request->has('generate_description')) {
                $description = $this->descriptionGenerator->generateEventDescription(
                    $event->getName(),
                    $event->getLocation(),
                    $event->getCategory()
                );
                $event->setDescription($description);
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Event created successfully.');
            return $this->redirectToRoute('admin_events_index');
        }

        $organizers = $this->organizerRepository->findAll();

        return $this->render('admin/event/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'organizers' => $organizers
        ]);
    }

    #[Route('/{id}', name: 'admin_events_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $totalParticipants = $this->participationRepository->countTotalParticipants($event);
        $attendedParticipants = $this->participationRepository->countAttendedParticipants($event);

        $result = $this->participationRepository->findByEventWithPagination($event, 1, 10);

        return $this->render('admin/event/show.html.twig', [
            'event' => $event,
            'totalParticipants' => $totalParticipants,
            'attendedParticipants' => $attendedParticipants,
            'participations' => $result['participations'],
            'totalPages' => $result['totalPages'],
            'currentPage' => 1
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate AI description if requested
            if ($request->request->has('generate_description')) {
                $description = $this->descriptionGenerator->generateEventDescription(
                    $event->getName(),
                    $event->getLocation(),
                    $event->getCategory()
                );
                $event->setDescription($description);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Event updated successfully.');
            return $this->redirectToRoute('admin_events_show', ['id' => $event->getId()]);
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_events_toggle_status', methods: ['POST'])]
    public function toggleStatus(Event $event): Response
    {
        $event->setIsActive(!$event->isIsActive());
        $this->entityManager->flush();

        $status = $event->isIsActive() ? 'activated' : 'deactivated';
        $this->addFlash('success', "Event {$status} successfully.");

        return $this->redirectToRoute('admin_events_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_events_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Event deleted successfully.');
        }

        return $this->redirectToRoute('admin_events_index');
    }

    #[Route('/{id}/participations', name: 'admin_events_participations', methods: ['GET'])]
    public function participations(Request $request, Event $event): Response
    {
        $page = $request->query->getInt('page', 1);

        $result = $this->participationRepository->findByEventWithPagination($event, $page, 10);

        return $this->render('admin/event/participations.html.twig', [
            'event' => $event,
            'participations' => $result['participations'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page
        ]);
    }

    #[Route('/generate-description', name: 'admin_events_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request): Response
    {
        $name = $request->request->get('name');
        $location = $request->request->get('location');
        $category = $request->request->get('category');

        if (!$name || !$location || !$category) {
            return $this->json([
                'error' => 'Missing required parameters'
            ], 400);
        }

        try {
            $description = $this->descriptionGenerator->generateEventDescription($name, $location, $category);

            return $this->json([
                'description' => $description
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate description: ' . $e->getMessage()
            ], 500);
        }
    }
}
