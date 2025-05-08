<?php

namespace App\Controller;

use Exception;
use App\Entity\Trip;
use App\Form\TripType;
use App\Entity\Itineraire;
use App\Service\MailService;
use App\Entity\RelationTripItineraire;

use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminTripController extends AbstractController
{
    #[Route('/admin/get-itineraire-list', name: 'get_itineraire_list')]
    public function getItineraire(ManagerRegistry $doctrine): JsonResponse
    {
        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();
        $data = [];

        foreach ($itineraires as $itineraire) {
            $data[] = [
                'id' => $itineraire->getId(),
                'name' => $itineraire->getName(),
                'dayProgram' => $itineraire->getDayProgram(),
                'activity' => $itineraire->getActivity(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/admin/trip-itinerary', name: 'admin_trip_itinerary')]
    public function index(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 5;
        $status = $request->query->get('status');
        $minBudget = $request->query->get('minBudget');
        $maxBudget = $request->query->get('maxBudget');

        $repository = $doctrine->getRepository(Trip::class);
        $query = $repository->findBy([], ['id' => 'DESC']);

        // Filter by status if provided
        if ($status !== null && $status !== '') {
            $query = array_filter($query, function($trip) use ($status) {
                return $trip->getStatus() === $status;
            });
        }

        // Filter by min budget if provided
        if ($minBudget !== null && $minBudget !== '') {
            $query = array_filter($query, function($trip) use ($minBudget) {
                return (float)$trip->getBudget() >= (float)$minBudget;
            });
        }

        // Filter by max budget if provided
        if ($maxBudget !== null && $maxBudget !== '') {
            $query = array_filter($query, function($trip) use ($maxBudget) {
                return (float)$trip->getBudget() <= (float)$maxBudget;
            });
        }

        // Manual pagination
        $totalItems = count($query);
        $totalPages = ceil($totalItems / $limit);

        // Get items for current page
        $offset = ($page - 1) * $limit;
        $items = array_slice($query, $offset, $limit);

        return $this->render('admin/trip-itineary/list.html.twig', [
            'tripItineraries' => $items,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filterStatus' => $status,
            'filterMinBudget' => $minBudget,
            'filterMaxBudget' => $maxBudget,
        ]);
    }

    #[Route('/admin/view-trip/{id}', name: 'admin_view_trip')]
    public function viewTrip(ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $trip = $doctrine->getRepository(Trip::class)->find($id);
        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('admin_trip_itinerary');
        }

        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();

        // Get currently selected itineraries for this trip
        $selectedItineraries = $doctrine->getRepository(RelationTripItineraire::class)
            ->findBy(['Trip' => $trip]);

        // Extract IDs of selected itineraries
        $selectedItineraryIds = [];
        foreach ($selectedItineraries as $relation) {
            $selectedItineraryIds[] = $relation->getItineraire()->getId();
        }

        $form = $this->createForm(TripType::class, $trip);

        return $this->render('admin/trip-itineary/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraires' => $itineraires,
            'selectedItineraryIds' => $selectedItineraryIds,
        ]);
    }

    #[Route('/admin/update-trip/{id}', name: 'admin_update_trip')]
    public function updateTrip(Request $request, ManagerRegistry $doctrine, $id, MailService $mailService, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $trip = $doctrine->getRepository(Trip::class)->find($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('admin_trip_itinerary');
        }

        // Get all itineraries
        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();

        // Get currently selected itineraries for this trip
        $existingRelations = $doctrine->getRepository(RelationTripItineraire::class)
            ->findBy(['Trip' => $trip]);

        // Extract IDs of existing itineraries
        $existingItineraryIds = [];
        foreach ($existingRelations as $relation) {
            $existingItineraryIds[] = $relation->getItineraire()->getId();
        }

        // Get newly selected itineraries from the form
        $selectedItineraryIds = $request->request->all()['itineraries'] ?? [];

        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($trip);

                // Remove deselected itineraries
                foreach ($existingRelations as $relation) {
                    $itineraireId = $relation->getItineraire()->getId();
                    if (!is_array($selectedItineraryIds) || !in_array($itineraireId, $selectedItineraryIds)) {
                        $em->remove($relation);
                    }
                }

                // Add newly selected itineraries
                if (is_array($selectedItineraryIds)) {
                    foreach ($selectedItineraryIds as $itineraireId) {
                        if (!in_array($itineraireId, $existingItineraryIds)) {
                            $itineraire = $doctrine->getRepository(Itineraire::class)->find((int) $itineraireId);
                            if ($itineraire) {
                                $relation = new RelationTripItineraire();
                                $relation->setTrip($trip);
                                $relation->setItineraire($itineraire);
                                $em->persist($relation);
                            }
                        }
                    }
                }

                $em->flush();
                $this->addFlash('success', 'Trip updated successfully.');

                // Send email to user if status changed
                if ($trip->getStatus() === 'Approved' || $trip->getStatus() === 'Rejected') {
                    try {
                        $user = $trip->getUser();

                        if ($user) {
                            $container = [
                                'mailInfo' => [
                                    'mailExpeditor' => 'admin@innotravel.tn',
                                    'nameExpeditor' => 'InnoTravel Admin',
                                    'receiverList' => [
                                            'Email' => $user->getEmail(),
                                            'Name' =>  $user->getFirstName() . ' ' . $user->getLastName(),
                                        ],
                                    'subject' => 'Trip Status Update',
                                ],
                                'view' => 'emails/trip-status-update.html.twig',
                                'data' => [
                                    'trip' => $trip,
                                    'user' => $user,
                                ]
                            ];

                            $mailService->sendEmail($container);
                        } else {
                            $this->addFlash('warning', 'Trip updated successfully, but user not found for notification.');
                        }
                    } catch (\Exception $e) {
                        // Log the error but don't stop the process
                        $this->addFlash('warning', 'Trip updated successfully, but notification email could not be sent: ' . $e->getMessage());
                    }
                }

                return $this->redirectToRoute('admin_trip_itinerary');
            } catch (Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('admin/trip-itineary/update.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraires' => $itineraires,
            'selectedItineraryIds' => $existingItineraryIds,
        ]);
    }

    #[Route('/admin/delete-trip/{id}', name: 'admin_delete_trip')]
    public function deleteTrip(ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $trip = $doctrine->getRepository(Trip::class)->find($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('admin_trip_itinerary');
        }

        try {
            // Delete related relations first
            $relations = $doctrine->getRepository(RelationTripItineraire::class)->findBy(['Trip' => $trip]);
            foreach ($relations as $relation) {
                $em->remove($relation);
            }

            $em->remove($trip);
            $em->flush();

            $this->addFlash('success', 'Trip deleted successfully.');
        } catch (Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_trip_itinerary');
    }

    #[Route('/admin/itineraire', name: 'admin_itineraire')]
    public function itineraireList(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $repository = $doctrine->getRepository(Itineraire::class);
        $allItineraires = $repository->findBy([], ['id' => 'DESC']);

        // Manual pagination
        $totalItems = count($allItineraires);
        $totalPages = ceil($totalItems / $limit);

        // Get items for current page
        $offset = ($page - 1) * $limit;
        $itineraires = array_slice($allItineraires, $offset, $limit);

        return $this->render('admin/itineraire/list.html.twig', [
            'itineraires' => $itineraires,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/admin/create-itineraire', name: 'admin_create_itineraire')]
    public function createItineraire(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $itineraire = new Itineraire();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $dayProgram = $request->request->get('dayProgram');
            $activity = $request->request->get('activity');

            if (!empty($name) && !empty($dayProgram) && !empty($activity)) {
                $itineraire->setName($name);
                $itineraire->setDayProgram($dayProgram);
                $itineraire->setActivity($activity);

                $em = $doctrine->getManager();
                $em->persist($itineraire);
                $em->flush();

                $this->addFlash('success', 'Itinerary created successfully.');
                return $this->redirectToRoute('admin_itineraire');
            } else {
                $this->addFlash('error', 'All fields are required.');
            }
        }

        return $this->render('admin/itineraire/create.html.twig', [
            'itineraire' => $itineraire,
        ]);
    }

    #[Route('/admin/update-itineraire/{id}', name: 'admin_update_itineraire')]
    public function updateItineraire(Request $request, ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $itineraire = $doctrine->getRepository(Itineraire::class)->find($id);

        if (!$itineraire) {
            $this->addFlash('error', 'Itinerary not found.');
            return $this->redirectToRoute('admin_itineraire');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $dayProgram = $request->request->get('dayProgram');
            $activity = $request->request->get('activity');

            if (!empty($name) && !empty($dayProgram) && !empty($activity)) {
                $itineraire->setName($name);
                $itineraire->setDayProgram($dayProgram);
                $itineraire->setActivity($activity);

                $em->flush();

                $this->addFlash('success', 'Itinerary updated successfully.');
                return $this->redirectToRoute('admin_itineraire');
            } else {
                $this->addFlash('error', 'All fields are required.');
            }
        }

        return $this->render('admin/itineraire/update.html.twig', [
            'itineraire' => $itineraire,
        ]);
    }

    #[Route('/admin/delete-itineraire/{id}', name: 'admin_delete_itineraire')]
    public function deleteItineraire(ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $itineraire = $doctrine->getRepository(Itineraire::class)->find($id);

        if (!$itineraire) {
            $this->addFlash('error', 'Itinerary not found.');
            return $this->redirectToRoute('admin_itineraire');
        }

        try {
            $em->remove($itineraire);
            $em->flush();

            $this->addFlash('success', 'Itinerary deleted successfully.');
        } catch (Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_itineraire');
    }
}
