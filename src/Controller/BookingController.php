<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/booking')]
final class BookingController extends AbstractController
{
    #[Route('/',name: 'app_booking_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $bookingRepository = $entityManager->getRepository(Booking::class);
        $hotelRepository = $entityManager->getRepository(\App\Entity\Hotel::class);

        // Get all bookings
        $bookings = $bookingRepository->findAll();

        // Calculate total bookings count
        $totalBookings = count($bookings);

        // Get pending and confirmed bookings count
        $pendingConfirmedBookings = count($bookingRepository->createQueryBuilder('b')
            ->where('b.status = :pending OR b.status = :confirmed')
            ->setParameter('pending', 'pending')
            ->setParameter('confirmed', 'confirmed')
            ->getQuery()
            ->getResult());

        // Get total hotels count
        $totalHotels = count($hotelRepository->findAll());

        // Calculate average hotel price
        $hotels = $hotelRepository->findAll();
        $avgPrice = 0;
        if (count($hotels) > 0) {
            $totalPrice = 0;
            foreach ($hotels as $hotel) {
                $totalPrice += $hotel->getPricepernight();
            }
            $avgPrice = $totalPrice / count($hotels);
        }

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

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
            'totalBookings' => $totalBookings,
            'pendingConfirmedBookings' => $pendingConfirmedBookings,
            'totalHotels' => $totalHotels,
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



    #[Route('/new', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_booking_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($booking);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
    }
}
