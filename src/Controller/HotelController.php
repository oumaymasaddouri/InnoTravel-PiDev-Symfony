<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Form\HotelType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/hotel')]
final class HotelController extends AbstractController
{
    #[Route(name: 'app_hotel_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $hotelRepository = $entityManager->getRepository(Hotel::class);
        $bookingRepository = $entityManager->getRepository(\App\Entity\Booking::class);

        // Get all hotels
        $hotels = $hotelRepository->findAll();

        // Calculate total hotels count
        $totalHotels = count($hotels);

        // Calculate average hotel price
        $avgPrice = 0;
        if ($totalHotels > 0) {
            $totalPrice = 0;
            foreach ($hotels as $hotel) {
                $totalPrice += $hotel->getPricepernight();
            }
            $avgPrice = $totalPrice / $totalHotels;
        }

        // Get total bookings count
        $totalBookings = count($bookingRepository->findAll());

        // Get pending and confirmed bookings count
        $pendingConfirmedBookings = count($bookingRepository->createQueryBuilder('b')
            ->where('b.status = :pending OR b.status = :confirmed')
            ->setParameter('pending', 'pending')
            ->setParameter('confirmed', 'confirmed')
            ->getQuery()
            ->getResult());

        // Get chart data
        $bookingsByMonth = $bookingRepository->getBookingsByMonth();
        $bookingsByStatus = $bookingRepository->getBookingsByStatus();
        $revenuePerHotel = $hotelRepository->getRevenuePerHotel(5); // Top 5 hotels by revenue

        // Prepare month labels for the line chart
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return $this->render('hotel/index.html.twig', [
            'hotels' => $hotels,
            'totalHotels' => $totalHotels,
            'totalBookings' => $totalBookings,
            'pendingConfirmedBookings' => $pendingConfirmedBookings,
            'avgPrice' => $avgPrice,
            'chartData' => [
                'bookingsByMonth' => [
                    'labels' => array_values($monthNames),
                    'data' => array_values($bookingsByMonth)
                ],
                'bookingsByStatus' => [
                    'labels' => ['Pending', 'Confirmed', 'Cancelled'],
                    'data' => [
                        $bookingsByStatus['pending'],
                        $bookingsByStatus['confirmed'],
                        $bookingsByStatus['cancelled']
                    ]
                ],
                'revenuePerHotel' => $revenuePerHotel
            ]
        ]);
    }

    #[Route('/new', name: 'app_hotel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $hotel = new Hotel();
        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('hotel_images_directory'),
                        $newFilename
                    );
                    $hotel->setImage($newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }

            $entityManager->persist($hotel);
            $entityManager->flush();

            return $this->redirectToRoute('app_hotel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hotel/new.html.twig', [
            'hotel' => $hotel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_hotel_show', methods: ['GET'])]
    public function show(Hotel $hotel): Response
    {
        return $this->render('hotel/show.html.twig', [
            'hotel' => $hotel,

        ]);
    }

    #[Route('/{id}/edit', name: 'app_hotel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Hotel $hotel, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Delete old image if exists
                    $oldImage = $hotel->getImage();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('hotel_images_directory').'/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $imageFile->move(
                        $this->getParameter('hotel_images_directory'),
                        $newFilename
                    );
                    $hotel->setImage($newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_hotel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hotel/edit.html.twig', [
            'hotel' => $hotel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hotel_delete', methods: ['POST'])]
    public function delete(Request $request, Hotel $hotel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hotel->getId(), $request->getPayload()->getString('_token'))) {
            // Delete image file if exists
            $image = $hotel->getImage();
            if ($image) {
                $imagePath = $this->getParameter('hotel_images_directory').'/'.$image;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($hotel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_hotel_index', [], Response::HTTP_SEE_OTHER);
    }


}

