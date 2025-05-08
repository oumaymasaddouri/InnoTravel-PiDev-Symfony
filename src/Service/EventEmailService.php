<?php

namespace App\Service;

use App\Entity\Participation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EventEmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private QrCodeGenerator $qrCodeGenerator;
    private UrlGeneratorInterface $urlGenerator;
    private string $senderEmail;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        QrCodeGenerator $qrCodeGenerator,
        UrlGeneratorInterface $urlGenerator,
        string $senderEmail
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->senderEmail = $senderEmail;
    }

    public function sendTicketEmail(Participation $participation): void
    {
        $user = $participation->getUser();
        $event = $participation->getEvent();

        $qrCodeDataUri = $this->qrCodeGenerator->generateTicketQrCode($participation);
        $verificationUrl = $this->urlGenerator->generate(
            'participation_verify',
            ['ticketCode' => $participation->getTicketCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $htmlContent = $this->twig->render('emails/event-ticket.html.twig', [
            'participation' => $participation,
            'user' => $user,
            'event' => $event,
            'qrCode' => $qrCodeDataUri,
            'verificationUrl' => $verificationUrl
        ]);

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($user->getEmail())
            ->subject('Your Ticket for ' . $event->getName())
            ->html($htmlContent);

        $this->mailer->send($email);
    }

    public function sendAttendanceConfirmationEmail(Participation $participation): void
    {
        $user = $participation->getUser();
        $event = $participation->getEvent();

        $htmlContent = $this->twig->render('emails/event-attendance-confirmation.html.twig', [
            'participation' => $participation,
            'user' => $user,
            'event' => $event
        ]);

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($user->getEmail())
            ->subject('Attendance Confirmed for ' . $event->getName())
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
