<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Hotel;
use App\Entity\User;
use App\Form\BookingType;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Stripe\Exception\ApiErrorException;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/user/booking')]
class UserBookingController extends AbstractController
{
    #[Route('/new/{slug}', name: 'user_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        Hotel $hotel,
        Security $security,
        StripeService $stripeService,
        SessionInterface $session
    ): Response {

        // Get user from session
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $booking = new Booking();
        $booking->setHotelId($hotel);   // ✅ Pass Hotel entity
        $booking->setUserId($user);     // ✅ Pass User entity
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
            'user' => $user,
            'form' => $form->createView(),
            'hotel' => $hotel,
        ]);
    }

    #[Route('/', name: 'user_booking_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        // Get pagination parameters
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 5; // Number of bookings per page

        // Get user from session
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get paginated bookings
        $bookingRepository = $em->getRepository(Booking::class);
        $result = $bookingRepository->findByUserPaginated($user, $page, $limit);

        // Extract bookings and pagination data
        $bookings = $result['bookings'];
        $pagination = $result['pagination'];

        return $this->render('booking/user_index.html.twig', [
            'user' => $user,
            'bookings' => $bookings,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/invoice/{id}', name: 'booking_invoice_download', methods: ['GET'])]
    public function downloadInvoice(Booking $booking, SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Get user from session
        $user = $this->getUserFromSession($session, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        // Instantiate Dompdf
        $dompdf = new Dompdf($options);

        // Get booking details
        $hotel = $booking->getHotelId();
        $user = $booking->getUserId();

        // Calculate number of nights
        $startDate = $booking->getStartdate();
        $endDate = $booking->getEnddate();
        $interval = $startDate->diff($endDate);
        $nights = $interval->days;

        // Calculate total amount
        $pricePerNight = $hotel->getPricepernight();
        $totalAmount = $pricePerNight * $nights;

        // Generate invoice HTML
        $html = $this->renderView('booking/invoice_pdf.html.twig', [
            'booking' => $booking,
            'hotel' => $hotel,
            'user' => $user,
            'nights' => $nights,
            'totalAmount' => $totalAmount,
            'invoiceNumber' => 'INV-' . date('Y') . '-' . str_pad($booking->getId(), 5, '0', STR_PAD_LEFT),
            'invoiceDate' => new \DateTime(),
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the PDF
        $dompdf->render();

        // Generate a filename
        $filename = 'invoice-booking-' . $booking->getId() . '.pdf';

        // Return the PDF as a response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    private function getUserFromSession(SessionInterface $session, EntityManagerInterface $em): ?User
    {
        return $session->get('user_id') ? $em->getRepository(User::class)->find($session->get('user_id')) : null;
    }
}
