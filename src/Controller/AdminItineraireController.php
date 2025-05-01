<?php

namespace App\Controller;

use Exception;
use App\Entity\Itineraire;
use App\Form\ItineraireType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminItineraireController extends AbstractController
{
    #[Route('/admin/list-itineraire', name: 'list_itineraire')]
    public function listitineraire(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $repo = $em->getRepository(Itineraire::class);
    
        $queryBuilder = $repo->createQueryBuilder('i');
    
        // Handle filtering by name or activity if provided
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder
                ->where('i.id LIKE :search OR i.name LIKE :search OR i.Activity LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
    
        $pagination = $paginator->paginate(
            $queryBuilder, // query or queryBuilder
            $request->query->getInt('page', 1), // page number
            10 // limit
        );
    
        return $this->render('Admin/itineraires/list.html.twig', [
            'pagination' => $pagination,
            'search' => $search
        ]);
    } 

    #[Route('/admin/create-itineraire', name: 'create_itineraire')]
    public function createitineraire(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $itineraire = new Itineraire();
        $form = $this->createForm(ItineraireType::class, $itineraire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
               
                $em->persist($itineraire);
                $em->flush();
                $this->addFlash('success', 'itineraire créé avec succès.');
            }
            catch (Exception $e) {
                $this->addFlash('error', 'L`action a échoué. Veuillez réessayer.');
            }
            unset($form);
            $form = $this->createForm(ItineraireType::class);
        }

        return $this->render('Admin/itineraires/create.html.twig', [
            'itineraire_form' => $form->createView(),
        ]);
    } 

    #[Route('/admin/view-itineraire/{id}', name: 'view_itineraire')]
    public function viewItineraire(ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $itineraire = $doctrine->getRepository(Itineraire::class)->findOneBy(['id' => $id]);
       
        $form = $this->createForm(ItineraireType::class, $itineraire, [
            'action' => $this->generateUrl('update_itineraire', ['id' => $id])]);

        return $this->render('Admin/itineraires/view.html.twig', [
            'itineraire' => $itineraire,
            'itineraire_form' => $form->createView(),
        ]);
    }

    #[Route('/admin/update-itineraire/{id}', name: 'update_itineraire')]
    public function updateItineraire(Request $request, ManagerRegistry $doctrine, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $itineraire = $doctrine->getRepository(Itineraire::class)->findOneBy(['id' => $id]);
       
        $form = $this->createForm(ItineraireType::class, $itineraire);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($itineraire);
                $em->flush();
                $this->addFlash('success', 'itineraire a été mis à jour avec succès.');
                
            } catch (Exception $e) {
                $this->addFlash('error', 'L`action a échoué. Veuillez réessayer.');
            }
            return $this->redirectToRoute('list_itineraire');
        }

        return $this->render('Admin/itineraires/view.html.twig', [
            'itineraire' => $itineraire,
            'itineraire_form' => $form->createView(),
        ]);
    }

    
    #[Route('/admin/delete-itineraire/{id}', name: 'delete_itineraire', methods: ['POST'])]
    public function deleteItineraire(EntityManagerInterface $entityManager, $id, SessionInterface $session): Response
    {
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $itineraire = $entityManager->getRepository(Itineraire::class)->find($id);
    
        if (!$itineraire) {
            $this->addFlash('error', 'Itinéraire not found!');
            return $this->redirectToRoute('list_itineraire');
        }
    
        try {
            $relationTripItineraires = $itineraire->getRelationTripItineraires();  
            $tripsToDelete = [];
    
            foreach ($relationTripItineraires as $relationTripItineraire) {
                $trip = $relationTripItineraire->getTrip();
    
                if ($trip) {
                    $tripsToDelete[$trip->getId()] = $trip;
                }
    
                $entityManager->remove($relationTripItineraire);
            }
    
            $entityManager->flush();
    
            foreach ($tripsToDelete as $trip) {
                $entityManager->remove($trip);
            }
    
            $entityManager->flush();
    
            $entityManager->remove($itineraire);
            $entityManager->flush();
    
            $this->addFlash('success', 'Itinéraire deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting itinéraire: ' . $e->getMessage());
        }
    
        return $this->redirectToRoute('list_itineraire');
    }
    
}