<?php

namespace App\Controller;

use App\Entity\Organizer;
use App\Form\OrganizerType;
use App\Repository\OrganizerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/organizers')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrganizerController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrganizerRepository $organizerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrganizerRepository $organizerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->organizerRepository = $organizerRepository;
    }

    #[Route('/', name: 'admin_organizers_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search');
        
        $result = $this->organizerRepository->findAllWithPagination($page, 10, $search);
        
        return $this->render('admin/organizer/index.html.twig', [
            'organizers' => $result['organizers'],
            'totalPages' => $result['totalPages'],
            'currentPage' => $page,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'admin_organizers_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $organizer = new Organizer();
        $form = $this->createForm(OrganizerType::class, $organizer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($organizer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Organizer created successfully.');
            return $this->redirectToRoute('admin_organizers_index');
        }

        return $this->render('admin/organizer/new.html.twig', [
            'organizer' => $organizer,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'admin_organizers_show', methods: ['GET'])]
    public function show(Organizer $organizer): Response
    {
        return $this->render('admin/organizer/show.html.twig', [
            'organizer' => $organizer
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_organizers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Organizer $organizer): Response
    {
        $form = $this->createForm(OrganizerType::class, $organizer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Organizer updated successfully.');
            return $this->redirectToRoute('admin_organizers_show', ['id' => $organizer->getId()]);
        }

        return $this->render('admin/organizer/edit.html.twig', [
            'organizer' => $organizer,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/toggle-verification', name: 'admin_organizers_toggle_verification', methods: ['POST'])]
    public function toggleVerification(Organizer $organizer): Response
    {
        $organizer->setIsVerified(!$organizer->isIsVerified());
        $this->entityManager->flush();

        $status = $organizer->isIsVerified() ? 'verified' : 'unverified';
        $this->addFlash('success', "Organizer {$status} successfully.");
        
        return $this->redirectToRoute('admin_organizers_show', ['id' => $organizer->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_organizers_delete', methods: ['POST'])]
    public function delete(Request $request, Organizer $organizer): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organizer->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($organizer);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Organizer deleted successfully.');
        }

        return $this->redirectToRoute('admin_organizers_index');
    }
}
