<?php

namespace App\Controller;

use App\Entity\Organizer;
use App\Form\OrganizerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organizer')]
final class OrganizerController extends AbstractController
{
    #[Route(name: 'app_organizer_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $organizers = $entityManager
            ->getRepository(Organizer::class)
            ->findAll();

        return $this->render('organizer/index.html.twig', [
            'organizers' => $organizers,
        ]);
    }

    #[Route('/new', name: 'app_organizer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $organizer = new Organizer();
        $form = $this->createForm(OrganizerType::class, $organizer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($organizer);
            $entityManager->flush();

            return $this->redirectToRoute('app_organizer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('organizer/new.html.twig', [
            'organizer' => $organizer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_organizer_show', methods: ['GET'])]
    public function show(Organizer $organizer): Response
    {
        return $this->render('organizer/show.html.twig', [
            'organizer' => $organizer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_organizer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Organizer $organizer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrganizerType::class, $organizer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_organizer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('organizer/edit.html.twig', [
            'organizer' => $organizer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_organizer_delete', methods: ['POST'])]
    public function delete(Request $request, Organizer $organizer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organizer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($organizer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_organizer_index', [], Response::HTTP_SEE_OTHER);
    }
}
