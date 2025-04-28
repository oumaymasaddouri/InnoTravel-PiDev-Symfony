<?php

namespace App\Controller;

use App\Entity\transport;
use App\Form\transport1Type;
use App\Repository\transportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;



#[Route('/transport/admin')]
final class transportAdminController extends AbstractController
{
    #[Route(name: 'app_transport_admin_index', methods: ['GET'])]
    public function index(TransportRepository $transportRepository, ChartBuilderInterface $chartBuilder, PaginatorInterface $paginator, Request $request): Response
    {
        $searchTerm = $request->query->get('search');

        $queryBuilder = $transportRepository->createSearchQueryBuilder($searchTerm);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        $transports = $transportRepository->findAll();

        // First chart
        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $colors = array_count_values(array_map(fn($transport) => $transport->getCarColor(), $transports));
        $chart->setData([
            'labels' => array_keys($colors),
            'datasets' => [
                [
                    'label' => 'Number of Cars',
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                    'data' => array_values($colors),
                ],
            ],
        ]);
        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'position' => 'bottom'
                ],
            ],
        ]);

        // Second chart
        $chart2 = $chartBuilder->createChart(Chart::TYPE_BAR);
        $topVehicles = array_slice(
            array_filter($transports, fn($t) => $t->getMaxLuggage() !== null),
            0,
            5
        );

        $chart2->setData([
            'labels' => array_map(fn($t) => $t->getVehicleType(), $topVehicles),
            'datasets' => [
                [
                    'label' => 'Max Luggage',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                    'data' => array_map(fn($t) => $t->getMaxLuggage(), $topVehicles),
                ],
            ],
        ]);
        $chart2->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
                'x' => [ // Added this to make Chart.js 3+ happy
                    'beginAtZero' => true,
                ],
            ],
        ]);

        return $this->render('transport_admin/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'transports' => $transports,
            'chart_transport_statistics' => $chart,
            'chart_top_vehicles' => $chart2,
        ]);
    }

    #[Route('/new', name: 'app_transport_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transport = new transport();
        $form = $this->createForm(transport1Type::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($transport);
            $entityManager->flush();

            return $this->redirectToRoute('app_transport_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport_admin/new.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_admin_show', methods: ['GET'])]
    public function show(transport $transport): Response
    {
        return $this->render('transport_admin/show.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transport_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, transport $transport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(transport1Type::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_transport_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport_admin/edit.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_transport_admin_delete', methods: ['POST'])]

    public function delete(Request $request, transport $transport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($transport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_transport_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}
