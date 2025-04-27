<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Hotel;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/user/booking')]
class UserBookingController extends AbstractController
{
    #[Route('/new/{slug}', name: 'user_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        Hotel $hotel,
        Security $security
    ): Response {

        $user = $em->getRepository(\App\Entity\Users::class)->find(3); // ✅ Simulate user ID 3
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }


        $booking = new Booking();
        $booking->setHotelId($hotel);   // ✅ Pass Hotel entity
        $booking->setUserId($user);     // ✅ Pass Users entity
        $booking->setStatus('pending'); // Default status

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($booking);
            $em->flush();

            $this->addFlash('success', 'Booking created successfully!');
            return $this->redirectToRoute('user_booking_index');
        }

        return $this->render('booking/user_new.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
        ]);
    }

    #[Route('/', name: 'user_booking_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Get pagination parameters
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 5; // Number of bookings per page

        // Simulate the same user (ID 3)
        $user = $em->getRepository(\App\Entity\Users::class)->find(3);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        // Get paginated bookings
        $bookingRepository = $em->getRepository(Booking::class);
        $result = $bookingRepository->findByUserPaginated($user, $page, $limit);

        // Extract bookings and pagination data
        $bookings = $result['bookings'];
        $pagination = $result['pagination'];

        return $this->render('booking/user_index.html.twig', [
            'bookings' => $bookings,
            'pagination' => $pagination,
        ]);
    }

}
