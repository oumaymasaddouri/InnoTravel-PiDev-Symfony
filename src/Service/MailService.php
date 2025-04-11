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
            '8948cf8a466e25b726d175c9438c1b50',
            '732674cfc547547f6565cd10b2e808bb',
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