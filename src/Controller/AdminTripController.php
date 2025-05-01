<?php

namespace App\Controller;

use Exception;
use App\Entity\Trip;
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

class AdminTripController extends AbstractController
{
    #[Route('/admin/get-itineraire-list', name: 'get_itineraire_list', options: ['expose' => true])]
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

    #[Route('/admin/list-trip', name: 'list_trip')]
    public function listitineraire(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $repo = $em->getRepository(Trip::class);
    
        $queryBuilder = $repo->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->addSelect('u');
    
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->where('u.firstName LIKE :search OR t.status LIKE :search OR t.id LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );
    
        return $this->render('Admin/trip/list.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    #[Route('/admin/create-trip', name: 'create_trip')]
    public function createTrip(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $trip = new Trip();
        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
               
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
            }
            catch (Exception $e) {
                $this->addFlash('error', 'L`action a échoué. Veuillez réessayer.');
            }
            unset($form);
            $form = $this->createForm(TripType::class);
        }

        return $this->render('Admin/trip/create.html.twig', [
            'trip_form' => $form->createView(),
        ]);
    } 

    #[Route('/admin/view-trip/{id}', name: 'view_trip')]
    public function viewTrip(ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $trip = $doctrine->getRepository(Trip::class)->findOneBy(['id' => $id]);
       
        $itineraires = $doctrine->getRepository(Itineraire::class)->findAll();

        // Get currently selected itineraries for this trip
        $selectedItineraries = $doctrine->getRepository(RelationTripItineraire::class)
            ->findBy(['Trip' => $trip]);
    
        // Extract only IDs for pre-filling the form
        $selectedItineraryIds = array_map(function ($relation) {
            return $relation->getItineraire()->getId();
        }, $selectedItineraries);

        $form = $this->createForm(TripType::class, $trip, [
            'action' => $this->generateUrl('update_trip', ['id' => $id])]);

        return $this->render('Admin/trip/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraire_list' => $itineraires,
            'selectedItineraries' => $selectedItineraryIds,
        ]);
    }

    #[Route('/admin/update-trip/{id}', name: 'update_trip')]
    public function updateTrip(Request $request, ManagerRegistry $doctrine, $id, MailService $mailService, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

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
    
        $form = $this->createForm(TripType::class, $trip);
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

                    // Status has changed, send an email
                    $container = [
                        'mailInfo' => [
                            'mailExpeditor' => 'saddourioumayma@gmail.com',
                            'nameExpeditor' => 'saddouri oumayma',
                            'receiverList' => [
                                'Email' => $trip->getUser()->getEmail(), 
                                'Name' =>  $trip->getUser()->getFirstName() . ' ' . $trip->getUser()->getLastName(),
                            ],
                            'subject' => 'Trip Status Changed', 
                        ],
                        'view' => 'emails/status_change.html.twig',
                        'data' => [
                            'trip' => $trip,
                            'user' => $trip->getUser()
                        ]
                    ];
    
                    $mailService->sendEmail($container);
                
                $this->addFlash('success', 'Le voyage a été mis à jour avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'L`action a échoué. Veuillez réessayer.');
            }
    
            return $this->redirectToRoute('list_trip');
        }
    
        return $this->render('Admin/trip/view.html.twig', [
            'trip' => $trip,
            'trip_form' => $form->createView(),
            'itineraire_list' => $itineraires,
            'selectedItineraries' => $selectedItineraryIds,
        ]);
    }
    
    #[Route('/admin/delete-trip/{id}', name: 'delete_trip', methods: ['POST'])]
    public function deleteTrip(EntityManagerInterface $entityManager, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $trip = $entityManager->getRepository(Trip::class)->find($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found!');
            return $this->redirectToRoute('list_trip');
        }

        try {
            $RelationTripItineraires = $trip->getRelationTripItineraires();  
    
            foreach ($RelationTripItineraires as $RelationTripItineraire) {
                $entityManager->remove($RelationTripItineraire); 
            }
    
            $entityManager->remove($trip);
            $entityManager->flush();
            $this->addFlash('success', 'trip deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting trip: ' . $e->getMessage());
        }

        return $this->redirectToRoute('list_trip'); 
    }
}