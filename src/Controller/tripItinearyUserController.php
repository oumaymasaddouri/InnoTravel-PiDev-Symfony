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
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class tripItinearyUserController extends AbstractController
{
    private function getUserFromSession(SessionInterface $session, EntityManagerInterface $em): ?User
    {
        return $session->get('user_id') ? $em->getRepository(User::class)->find($session->get('user_id')) : null;
    }

    #[Route('/user/trip-itinerary', name: 'trip_itinerary_user')]
    public function index(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $status = $request->query->get('status');
        $date = $request->query->get('date');
        $minBudget = $request->query->get('min_budget');
        $maxBudget = $request->query->get('max_budget');
        $page = $request->query->getInt('page', 1);  // Current page, default is 1
        $limit = 10;  // Number of items per page
    
        // Create a query builder for filtering trip itineraries
        $qb = $doctrine->getRepository(Trip::class)->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);
    
        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
    
        if ($minBudget !== null && $minBudget !== '') {
            $qb->andWhere('t.budget >= :minBudget')
               ->setParameter('minBudget', (float) $minBudget);
        }
    
        if ($maxBudget !== null && $maxBudget !== '') {
            $qb->andWhere('t.budget <= :maxBudget')
               ->setParameter('maxBudget', (float) $maxBudget);
        }
    
        $qb->orderBy('t.id', 'DESC');
    
        // Paginate the results
        $pagination = $paginator->paginate(
            $qb, // The query builder
            $page, // Current page number
            $limit // Results per page
        );
    
        // Calculate total pages
        $totalPages = ceil($pagination->getTotalItemCount() / $limit);
    
        return $this->render('user/trip-itineary/list.html.twig', [
            'tripItineraries' => $pagination,
            'currentPage' => $page,
            'totalPages' => $totalPages, // Pass totalPages to Twig
            'filterStatus' => $status,
            'filterDate' => $date,
            'filterMinBudget' => $minBudget,
            'filterMaxBudget' => $maxBudget,
            'user' => $user,
        ]);
    }

    #[Route('/user/get-itineraire-list', name: 'user_get_itineraire_list', options: ['expose' => true])]
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

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $trip->setStatus('Pending');
                $trip->setUser($user);
                $em->persist($trip);

                $itineraireIds = $request->request->all('itineraires'); 
                if (!is_array($itineraireIds)) {
                    $itineraireIds = []; 
                }
                
                foreach ($itineraireIds as $itineraireId) {
                    $itineraire = $em->getRepository(Itineraire::class)->find($itineraireId);
                    if ($itineraire) {
                        $relation = new RelationTripItineraire();
                        $relation->setTrip($trip);
                        $relation->setItineraire($itineraire);
                        $em->persist($relation);
                    }
                }
                $em->flush();
                $this->addFlash('success', 'trip créé avec succès.');

                $admin = $em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getSingleResult();
                              
                $container = [
                    'mailInfo' => [
                        'mailExpeditor' => 'saddourioumayma@gmail.com',
                        'nameExpeditor' => 'saddouri oumayma',
                        'receiverList' => [
                                'Email' => $admin->getEmail(), 
                                'Name' =>  $admin->getFirstname() . ' ' . $admin->getLastname(),
                            ],
                        'subject' => 'New Trip Created', 
                    ],
                    'view' => 'emails/now-trip.html.twig',
                    'data' => [
                        'trip' => $trip,
                        'user' => $trip->getUser(),
                        'admin' => $admin, 
                    ]
                ];

                $mailService->sendEmail($container);
            }
            catch (Exception $e) {
                dd($e);
                $this->addFlash('error', 'Laction a échoué. Veuillez réessayer.');
            }
            unset($form);
            $form = $this->createForm(TripType::class)->remove('status')->remove('user');
        }

        return $this->render('user/trip-itineary/create.html.twig', [
            'trip_form' => $form->createView(),
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
    
        // Extract only IDs for pre-filling the form
        $selectedItineraryIds = array_map(function ($relation) {
            return $relation->getItineraire()->getId();
        }, $selectedItineraries);

        $form = $this->createForm(TripType::class, $trip, [
            'action' => $this->generateUrl('user_update_trip', ['id' => $id])])->remove('status')->remove('user');

        return $this->render('user/trip-itineary/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraire_list' => $itineraires,
            'selectedItineraries' => $selectedItineraryIds,
            'user' => $user,
        ]);
    }

    #[Route('/user/update-trip/{id}', name: 'user_update_trip')]
    public function updateTrip(Request $request, ManagerRegistry $doctrine, $id, MailService $mailService, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $em = $doctrine->getManager();
        $trip = $doctrine->getRepository(Trip::class)->findOneBy(['id' => $id]);
    
        // Get all itineraries
        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();
    
        // Get currently selected itineraries for this trip
        $existingRelations = $doctrine->getRepository(RelationTripItineraire::class)
            ->findBy(['Trip' => $trip]);
    
        // Extract existing itinerary IDs
        $existingItineraryIds = array_map(fn($relation) => $relation->getItineraire()->getId(), $existingRelations);
    
        // Get selected itineraries from form submission
        $selectedItineraryIds = $request->request->all('itineraires') ?? [];
    
        $form = $this->createForm(TripType::class, $trip)->remove('status')->remove('user');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($trip);
    
                // Remove deselected itineraries
                foreach ($existingRelations as $relation) {
                    if (!in_array($relation->getItineraire()->getId(), $selectedItineraryIds)) {
                        $em->remove($relation);
                    }
                }
    
                // Add newly selected itineraries
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
    
                $em->flush();
                
                $this->addFlash('success', 'Le voyage a été mis à jour avec succès.');
            } catch (\Exception $e) {
                dd($e);
                $this->addFlash('error', 'L`action a échoué. Veuillez réessayer.');
            }
    
            return $this->redirectToRoute('trip_itinerary_user');
        }
    
        return $this->render('user/trip-itineary/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraire_list' => $itineraires,
            'selectedItineraries' => $selectedItineraryIds,
        ]);
    }
    

    #[Route('/user/delete-trip/{id}', name: 'user_delete_trip', methods: ['POST'], options: ['expose' => true])]
    public function deleteTrip(EntityManagerInterface $entityManager, $id, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $trip = $entityManager->getRepository(Trip::class)->findOneBy(['id' => $id , 'user' => $user]);
    
        if (!$trip || $trip->getStatus() == "Accept" || $trip->getStatus() == "Canceled") {
            $this->addFlash('error', 'Trip not found or cannot be deleted!');
            return $this->redirectToRoute('list_trip');
        }
                
        try {
            $RelationTripItineraires = $trip->getRelationTripItineraires();  
    
            foreach ($RelationTripItineraires as $RelationTripItineraire) {
                $entityManager->remove($RelationTripItineraire); 
            }
    
            $entityManager->remove($trip);
            
            $entityManager->flush();
    
            $this->addFlash('success', 'Trip and its relations deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting trip and relations: ' . $e->getMessage());
        }
    
        return $this->redirectToRoute('trip_itinerary_user'); 
    }    

}