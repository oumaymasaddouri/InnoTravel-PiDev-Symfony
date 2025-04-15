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
    #[Route('/new/{hotelId}', name: 'user_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        int $hotelId,
        Security $security
    ): Response {
        $hotel = $em->getRepository(Hotel::class)->find($hotelId);
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found.');
        }

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
    public function index(EntityManagerInterface $em, Security $security): Response
    {
        $user = $security->getUser();
        $bookings = $em->getRepository(Booking::class)->findBy([
            'userId' => $user,
        ]);

        return $this->render('booking/user_index.html.twig', [
            'bookings' => $bookings,
        ]);
    }
}
