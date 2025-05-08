<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class MailService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendEmail(array $container): void
    {
        $mailInfo = $container['mailInfo'];
        $view = $container['view'];
        $data = $container['data'];

        $html = $this->twig->render($view, $data);

        $email = (new Email())
            ->from($mailInfo['mailExpeditor'])
            ->to($mailInfo['receiverList']['Email'])
            ->subject($mailInfo['subject'])
            ->html($html);

        $this->mailer->send($email);
    }
}
