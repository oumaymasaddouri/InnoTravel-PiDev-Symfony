<?php

namespace App\Controller;

use App\Entity\Transport;
use App\Form\TransportType;
use App\Repository\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/transport')]
class TransportAdminController extends AbstractController
{
    #[Route('/', name: 'admin_transport_index', methods: ['GET'])]
    public function index(
        TransportRepository $transportRepository,
        PaginatorInterface $paginator,
        Request $request,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');
        $vehicleType = $request->query->get('vehicleType');

        $queryBuilder = $transportRepository->createSearchQueryBuilder($searchTerm, $status, $vehicleType);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        // Get statistics for dashboard
        $transports = $transportRepository->findAll();
        $totalVehicles = count($transports);
        $activeCount = $transportRepository->getCountByStatus('Active');
        $inactiveCount = $transportRepository->getCountByStatus('Inactive');
        $avgLuggageCapacity = $transportRepository->getAverageLuggageCapacity();

        // Get vehicle type distribution
        $vehicleTypeStats = $transportRepository->getVehicleTypeDistribution();

        // Get color distribution
        $colorStats = $transportRepository->getColorDistribution();

        return $this->render('transport_admin/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status,
            'selectedVehicleType' => $vehicleType,
            'totalVehicles' => $totalVehicles,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'avgLuggageCapacity' => $avgLuggageCapacity,
            'vehicleTypeStats' => $vehicleTypeStats,
            'colorStats' => $colorStats,
        ]);
    }

    #[Route('/new', name: 'admin_transport_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        $transport = new Transport();
        $form = $this->createForm(TransportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('transport_images_directory'),
                        $newFilename
                    );
                    $transport->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $entityManager->persist($transport);
            $entityManager->flush();

            $this->addFlash('success', 'Transport vehicle created successfully.');
            return $this->redirectToRoute('admin_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport_admin/new.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_transport_show', methods: ['GET'])]
    public function show(
        int $id,
        TransportRepository $transportRepository,
        SessionInterface $session
    ): Response
    {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the transport entity
        $transport = $transportRepository->find($id);

        // If not found, redirect to index with error message
        if (!$transport) {
            $this->addFlash('error', 'Transport vehicle not found.');
            return $this->redirectToRoute('admin_transport_index');
        }

        return $this->render('transport_admin/show.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_transport_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        TransportRepository $transportRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the transport entity
        $transport = $transportRepository->find($id);

        // If not found, redirect to index with error message
        if (!$transport) {
            $this->addFlash('error', 'Transport vehicle not found.');
            return $this->redirectToRoute('admin_transport_index');
        }

        $form = $this->createForm(TransportType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('transport_images_directory'),
                        $newFilename
                    );

                    // Remove old image if exists
                    $oldFilename = $transport->getImageFilename();
                    if ($oldFilename) {
                        $oldFilePath = $this->getParameter('transport_images_directory').'/'.$oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }

                    $transport->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Transport vehicle updated successfully.');
            return $this->redirectToRoute('admin_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport_admin/edit.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_transport_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        TransportRepository $transportRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Find the transport entity
        $transport = $transportRepository->find($id);

        // If not found, redirect to index with error message
        if (!$transport) {
            $this->addFlash('error', 'Transport vehicle not found.');
            return $this->redirectToRoute('admin_transport_index');
        }

        if ($this->isCsrfTokenValid('delete'.$transport->getId(), $request->request->get('_token'))) {
            // Remove image file if exists
            $filename = $transport->getImageFilename();
            if ($filename) {
                $filePath = $this->getParameter('transport_images_directory').'/'.$filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $entityManager->remove($transport);
            $entityManager->flush();

            $this->addFlash('success', 'Transport vehicle deleted successfully.');
        }

        return $this->redirectToRoute('admin_transport_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/statistics', name: 'admin_transport_statistics', methods: ['GET'])]
    public function statistics(
        TransportRepository $transportRepository,
        SessionInterface $session
    ): Response {
        // Check if admin is logged in
        if (!$session->get('admin')) {
            return $this->redirectToRoute('app_login');
        }

        // Get statistics
        $totalVehicles = $transportRepository->count([]);
        $activeCount = $transportRepository->getCountByStatus('Active');
        $inactiveCount = $transportRepository->getCountByStatus('Inactive');
        $avgLuggageCapacity = $transportRepository->getAverageLuggageCapacity();

        // Get vehicle type distribution
        $vehicleTypeStats = $transportRepository->getVehicleTypeDistribution();

        // Get color distribution
        $colorStats = $transportRepository->getColorDistribution();

        return $this->render('transport_admin/statistics.html.twig', [
            'totalVehicles' => $totalVehicles,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'avgLuggageCapacity' => $avgLuggageCapacity,
            'vehicleTypeStats' => $vehicleTypeStats,
            'colorStats' => $colorStats,
        ]);
    }
}
