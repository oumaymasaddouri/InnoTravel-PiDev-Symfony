<?php

namespace App\Service;

use App\Entity\Participation;

class QrCodeGenerator
{
    /**
     * Generate a QR code for a ticket using Google Charts API
     */
    public function generateTicketQrCode(Participation $participation): string
    {
        $event = $participation->getEvent();
        $user = $participation->getUser();

        $ticketData = [
            'ticket_code' => $participation->getTicketCode(),
            'event_name' => $event->getName(),
            'event_date' => $event->getStartDate()->format('Y-m-d H:i'),
            'location' => $event->getLocation(),
            'user_name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'persons' => $participation->getNumberOfPersons(),
            'registration_date' => $participation->getRegistrationDate()->format('Y-m-d H:i')
        ];

        $qrContent = json_encode($ticketData);

        // Use Google Charts API to generate QR code
        $url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($qrContent) . '&chld=H|1';

        return $url;
    }

    /**
     * Generate a verification URL for a ticket
     */
    public function generateVerificationUrl(Participation $participation): string
    {
        return '/participation/verify/' . $participation->getTicketCode();
    }
}
