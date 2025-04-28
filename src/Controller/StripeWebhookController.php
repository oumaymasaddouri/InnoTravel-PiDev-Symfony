<?php

namespace App\Controller;

use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request, EntityManagerInterface $em): Response
    {
        // Set your Stripe secret key from environment variable
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Get the webhook payload
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_your_webhook_signing_secret'; // Get from environment variable

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return new Response('Webhook signature verification failed', 400);
        } catch (\Exception $e) {
            // Invalid payload
            return new Response('Invalid payload', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                // Get the booking ID from the metadata
                $bookingId = $session->metadata->booking_id ?? null;

                if ($bookingId) {
                    // Update the booking status to 'confirmed'
                    $booking = $em->getRepository(Booking::class)->find($bookingId);

                    if ($booking) {
                        $booking->setStatus('confirmed');
                        $em->flush();
                    }
                }
                break;

            // Add other event types as needed

            default:
                // Unexpected event type
                return new Response('Unexpected event type: ' . $event->type, 400);
        }

        return new Response('Webhook received', 200);
    }

    #[Route('/stripe/success/{bookingId}', name: 'stripe_success', methods: ['GET'])]
    public function success(int $bookingId, EntityManagerInterface $entityManager): Response
    {
        // Manually retrieve the booking entity from the database
        $booking = $entityManager->getRepository(Booking::class)->find($bookingId);

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Update booking status to confirmed if it's not already
        if ($booking->getStatus() !== 'confirmed') {
            $booking->setStatus('confirmed');
            $entityManager->flush();
        }

        // Calculate total amount
        $startDate = $booking->getStartdate();
        $endDate = $booking->getEnddate();
        $interval = $startDate->diff($endDate);
        $nights = $interval->days;

        $pricePerNight = $booking->getHotelId()->getPricepernight();
        $totalAmount = $pricePerNight * $nights;

        // Render the success page
        return $this->render('booking/stripe_success.html.twig', [
            'booking' => $booking,
            'totalAmount' => $totalAmount,
            'nights' => $nights,
        ]);
    }
}
