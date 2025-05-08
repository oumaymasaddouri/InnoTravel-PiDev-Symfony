<?php

namespace App\Controller;

use Exception;
use App\Entity\Trip;
use App\Entity\User;
use App\Form\TripType;
use App\Entity\Itineraire;
use App\Service\MailService;
use App\Entity\RelationTripItineraire;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TripItinearyUserController extends AbstractController
{
    private function getUserFromSession(SessionInterface $session, EntityManagerInterface $em): ?User
    {
        return $session->get('user_id') ? $em->getRepository(User::class)->find($session->get('user_id')) : null;
    }

    #[Route('/user/trip-itinerary', name: 'trip_itinerary_user')]
    public function index(Request $request, ManagerRegistry $doctrine, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 5;
        $status = $request->query->get('status');
        $minBudget = $request->query->get('min_budget');
        $maxBudget = $request->query->get('max_budget');

        $repository = $doctrine->getRepository(Trip::class);
        $query = $repository->findBy(['user' => $user], ['id' => 'DESC']);

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

        return $this->render('user/trip-itineary/list.html.twig', [
            'tripItineraries' => $items,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filterStatus' => $status,
            'filterMinBudget' => $minBudget,
            'filterMaxBudget' => $maxBudget,
            'user' => $user,
        ]);
    }

    #[Route('/user/get-itineraire-list', name: 'user_get_itineraire_list')]
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

    #[Route('/user/create-trip', name: 'user_create_trip')]
    public function createTrip(Request $request, ManagerRegistry $doctrine, MailService $mailService, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $trip = new Trip();
        $form = $this->createForm(TripType::class, $trip)->remove('status')->remove('user');
        $form->handleRequest($request);

        // Get all itineraries for the form
        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $trip->setUser($user);
                $trip->setStatus('Pending');
                $em->persist($trip);

                // Handle itineraries
                $selectedItineraryIds = $request->request->all()['itineraries'] ?? [];

                // Validate that at least one itinerary is selected
                if (!is_array($selectedItineraryIds) || count($selectedItineraryIds) === 0) {
                    $this->addFlash('error', 'Please select at least one itinerary for your trip.');
                    return $this->render('user/trip-itineary/create.html.twig', [
                        'trip_form' => $form->createView(),
                        'itineraires' => $itineraires,
                        'user' => $user,
                    ]);
                }

                // Process selected itineraries
                foreach ($selectedItineraryIds as $itineraireId) {
                    $itineraire = $doctrine->getRepository(Itineraire::class)->find((int) $itineraireId);
                    if ($itineraire) {
                        $relation = new RelationTripItineraire();
                        $relation->setTrip($trip);
                        $relation->setItineraire($itineraire);
                        $em->persist($relation);
                    }
                }

                $em->flush();
                $this->addFlash('success', 'Trip created successfully.');

                // Get admin email from environment variable
                $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@innotravel.tn';
                $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'change_this_password';

                // Find admin user
                $admin = $em->getRepository(User::class)->findOneBy(['roles' => 'ROLE_ADMIN']);
                if (!$admin) {
                    $admin = $em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
                }

                // If admin doesn't exist, create one
                if (!$admin) {
                    // Create a default admin user
                    $admin = new User();
                    $admin->setEmail($adminEmail);
                    $admin->setFirstName('Admin');
                    $admin->setLastName('User');
                    $admin->setPassword(password_hash($adminPassword, PASSWORD_BCRYPT));
                    $admin->setRoles(['ROLE_ADMIN']);
                    $admin->setGender('Male'); // Default value
                    $admin->setDateOfBirth(new \DateTime('1990-01-01')); // Default value
                    $admin->setPhoneNumber('123456789'); // Default value
                    $admin->setCountry('Tunisia'); // Default value
                    $em->persist($admin);
                    $em->flush();
                }

                try {
                    $container = [
                        'mailInfo' => [
                            'mailExpeditor' => 'noreply@innotravel.tn',
                            'nameExpeditor' => 'InnoTravel Notifications',
                            'receiverList' => [
                                    'Email' => $admin->getEmail(),
                                    'Name' =>  $admin->getFirstName() . ' ' . $admin->getLastName(),
                                ],
                            'subject' => 'New Trip Created by ' . $user->getFirstName() . ' ' . $user->getLastName(),
                        ],
                        'view' => 'emails/now-trip.html.twig',
                        'data' => [
                            'trip' => $trip,
                            'user' => $trip->getUser(),
                            'admin' => $admin,
                        ]
                    ];

                    $mailService->sendEmail($container);
                } catch (\Exception $e) {
                    // Log the error but don't stop the process
                    $this->addFlash('warning', 'Trip created successfully, but notification email could not be sent.');
                }

                return $this->redirectToRoute('trip_itinerary_user');
            } catch (Exception $e) {
                $this->addFlash('error', 'An error occurred while creating your trip. Please try again.');
            }
        }

        return $this->render('user/trip-itineary/create.html.twig', [
            'trip_form' => $form->createView(),
            'itineraires' => $itineraires,
            'user' => $user,
        ]);
    }

    #[Route('/user/view-trip/{id}', name: 'user_view_trip')]
    public function viewTrip(ManagerRegistry $doctrine, $id, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $trip = $doctrine->getRepository(Trip::class)->findOneBy(['id' => $id , 'user' => $user]);

        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();

        // Get currently selected itineraries for this trip
        $selectedItineraries = $doctrine->getRepository(RelationTripItineraire::class)
            ->findBy(['Trip' => $trip]);

        // Extract IDs of selected itineraries
        $selectedItineraryIds = [];
        foreach ($selectedItineraries as $relation) {
            $selectedItineraryIds[] = $relation->getItineraire()->getId();
        }

        $form = $this->createForm(TripType::class, $trip)->remove('status')->remove('user');

        return $this->render('user/trip-itineary/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraires' => $itineraires,
            'selectedItineraryIds' => $selectedItineraryIds,
            'user' => $user,
        ]);
    }

    #[Route('/user/update-trip/{id}', name: 'user_update_trip')]
    public function updateTrip(Request $request, ManagerRegistry $doctrine, $id, EntityManagerInterface $em): Response
    {
        $em = $doctrine->getManager();
        $trip = $doctrine->getRepository(Trip::class)->findOneBy(['id' => $id]);

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

        $form = $this->createForm(TripType::class, $trip)->remove('status')->remove('user');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($trip);

                // Remove deselected itineraries
                foreach ($existingRelations as $relation) {
                    $itineraireId = $relation->getItineraire()->getId();
                    if (!in_array($itineraireId, $selectedItineraryIds)) {
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
                return $this->redirectToRoute('trip_itinerary_user');
            } catch (Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('user/trip-itineary/update.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraires' => $itineraires,
            'selectedItineraryIds' => $existingItineraryIds,
            'user' => $trip->getUser(),
        ]);
    }

    #[Route('/user/delete-trip/{id}', name: 'user_delete_trip')]
    public function deleteTrip(ManagerRegistry $doctrine, $id, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $trip = $doctrine->getRepository(Trip::class)->findOneBy(['id' => $id, 'user' => $user]);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('trip_itinerary_user');
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

        return $this->redirectToRoute('trip_itinerary_user');
    }
}
