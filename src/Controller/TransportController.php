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

#[Route('/transport')]
class TransportController extends AbstractController
{
    #[Route('/', name: 'app_transport_index', methods: ['GET'])]
    public function index(
        TransportRepository $transportRepository,
        PaginatorInterface $paginator,
        Request $request,
        SessionInterface $session
    ): Response {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');
        $status = $request->query->get('status');
        $vehicleType = $request->query->get('vehicleType');

        $queryBuilder = $transportRepository->createSearchQueryBuilder($searchTerm, $status, $vehicleType);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6,
            [
                'pageParameterName' => 'page',
                'sortFieldParameterName' => 'sort',
                'sortDirectionParameterName' => 'direction'
            ]
        );

        return $this->render('transport/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'selectedStatus' => $status,
            'selectedVehicleType' => $vehicleType,
        ]);
    }

    #[Route('/new', name: 'app_transport_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SessionInterface $session
    ): Response {
        // Check if user is logged in and is admin
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can create new vehicles
        if (!$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to add new vehicles.');
            return $this->redirectToRoute('app_transport_index');
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
            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport/new.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_show', methods: ['GET'])]
    public function show(Transport $transport, SessionInterface $session): Response
    {
        // Check if user is logged in
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('transport/show.html.twig', [
            'transport' => $transport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_transport_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Transport $transport,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SessionInterface $session
    ): Response {
        // Check if user is logged in and is admin
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can edit vehicles
        if (!$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to edit vehicles.');
            return $this->redirectToRoute('app_transport_index');
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
            return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transport/edit.html.twig', [
            'transport' => $transport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_transport_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Transport $transport,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Check if user is logged in and is admin
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        // Only admins can delete vehicles
        if (!$session->get('admin')) {
            $this->addFlash('error', 'You do not have permission to delete vehicles.');
            return $this->redirectToRoute('app_transport_index');
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

        return $this->redirectToRoute('app_transport_index', [], Response::HTTP_SEE_OTHER);
    }
}
