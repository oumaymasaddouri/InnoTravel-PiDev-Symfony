<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private string $stripeSecretKey;
    private string $stripePublishableKey;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ParameterBagInterface $params,
        UrlGeneratorInterface $urlGenerator
    ) {
        // Use the keys from environment variables
        $this->stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'];
        $this->stripePublishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'];
        $this->urlGenerator = $urlGenerator;

        // Initialize Stripe with the secret key
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Create a Stripe Checkout Session for a booking
     *
     * @param Booking $booking
     * @return Session
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Booking $booking): Session
    {
        $hotel = $booking->getHotelId();

        // Calculate the number of nights
        $startDate = $booking->getStartdate();
        $endDate = $booking->getEnddate();
        $interval = $startDate->diff($endDate);
        $nights = $interval->days;

        // Calculate the total price (price per night * number of nights)
        $pricePerNight = $hotel->getPricepernight();
        $totalAmount = $pricePerNight * $nights;

        // Create a Stripe Checkout Session
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $hotel->getName(),
                        'description' => 'Booking from ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                        'images' => [
                            $hotel->getImage() ? 'https://example.com/images/' . $hotel->getImage() : 'https://example.com/images/no-image.jpg',
                        ],
                    ],
                    'unit_amount' => (int)($totalAmount * 100), // Amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('stripe_success', [
                'bookingId' => $booking->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('user_booking_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'booking_id' => $booking->getId(),
            ],
        ]);
    }

    /**
     * Get the Stripe publishable key
     *
     * @return string
     */
    public function getPublishableKey(): string
    {
        return $this->stripePublishableKey;
    }
}
