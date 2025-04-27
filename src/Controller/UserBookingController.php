<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Hotel;
use App\Form\BookingType;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Stripe\Exception\ApiErrorException;

#[Route('/user/booking')]
class UserBookingController extends AbstractController
{
    #[Route('/new/{slug}', name: 'user_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        Hotel $hotel,
        Security $security,
        StripeService $stripeService
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
            // Get the selected payment method
            $paymentMethod = $form->get('paymentMethod')->getData();

            // Save the booking to the database
            $em->persist($booking);
            $em->flush();

            // Handle payment based on the selected method
            if ($paymentMethod === 'cash') {
                // For cash payments, just save as pending and redirect to bookings list
                $this->addFlash('success', 'Booking created successfully! Please pay at the hotel.');
                return $this->redirectToRoute('user_booking_index');
            } else if ($paymentMethod === 'stripe') {
                try {
                    // Create a Stripe checkout session
                    $session = $stripeService->createCheckoutSession($booking);

                    // Redirect to Stripe checkout
                    return $this->redirect($session->url);
                } catch (ApiErrorException $e) {
                    // Handle Stripe API error
                    $this->addFlash('error', 'Payment processing error: ' . $e->getMessage());

                    // Delete the booking if payment fails
                    $em->remove($booking);
                    $em->flush();

                    return $this->redirectToRoute('user_booking_new', ['slug' => $hotel->getSlug()]);
                }
            }
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
