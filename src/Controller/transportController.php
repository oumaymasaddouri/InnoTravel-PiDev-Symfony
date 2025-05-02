<?php

namespace App\Controller;

use App\Entity\transport;
use App\Form\transportType;
use App\Repository\transportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/transport')]
final class transportController extends AbstractController
{
    #[Route(name: 'app_transport_index', methods: ['GET'])]
    public function index(transportRepository $transportRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $transportRepository->createPaginatedQueryBuilder();

        // Get the total number of records
        $totalRecords = $transportRepository->count([]);

        // Calculate items per page dynamically
        $minItemsPerPage = 5;
        $maxItemsPerPage = 20;
        $targetPages = 5; // Aim for around 5 pages if possible

        if ($totalRecords <= $minItemsPerPage) {
            $itemsPerPage = $minItemsPerPage;
        } elseif ($totalRecords <= $targetPages * $minItemsPerPage) {
            $itemsPerPage = $minItemsPerPage;
        } else {
            // Calculate items per page to aim for ~5 pages
            $itemsPerPage = min(
                $maxItemsPerPage,
                max($minItemsPerPage, (int) ceil($totalRecords / $targetPages))
            );
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $itemsPerPage,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        return $this->render('transport/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_transport_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transport = new transport();
        $form = $this->createForm(transportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($transport);
            $entityManager->flush();

            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport/new.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_show', methods: ['GET'])]
    public function show(transport $transport): Response
    {
        return $this->render('transport/show.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transport_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, transport $transport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(transportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport/edit.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_delete', methods: ['POST'])]
    public function delete(Request $request, transport $transport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($transport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
    }
}