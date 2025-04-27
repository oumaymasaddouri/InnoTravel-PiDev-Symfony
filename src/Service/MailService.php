<?php

namespace App\Service;
use Exception;
use Mailjet\Client;
use Mailjet\Resources;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailService
{ 
       private Environment $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }


    public function sendEmail(array $container): bool
    {
        $mj = new Client(
            '1ebe66d082ca3ebcfd3cb47fe5e200bf',
            '6981014666d6636d434e0431c69367cf',
            true,
            ['version' => 'v3.1']
        );
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $container['mailInfo']['mailExpeditor'],
                        'Name' => $container['mailInfo']['nameExpeditor'],
                    ],
                    'To' => [
                        [
                            'Email' => $container['mailInfo']['receiverList']['Email'],
                            'Name' => $container['mailInfo']['receiverList']['Name'],
                        ]
                    ],
                    'Subject' => $container['mailInfo']['subject'],
                    'TextPart' => 'Mailer',
                    'HTMLPart' => $this->twig->render($container['view'], [
                            'data'=> $container['data'], 
                        ]
                    ),
                    'CustomID' => 'hedi mailer',
                ],
            ],
        ];
        try {
            $response = $mj->post(Resources::$Email, ['body' => $body]);
            return $response->success();
        } catch (Exception $e) {
            return false;
        }
    }
}