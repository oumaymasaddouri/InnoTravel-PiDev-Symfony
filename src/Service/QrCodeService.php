<?php
namespace App\Service;

use Endroid\QrCode\Builder\BuilderInterface;

class QrCodeService
{
    private BuilderInterface $builder;

    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    public function createQrCode(string $departure, string $destination, string $status): string
    {
        $text = sprintf(
            "Your Reservation:\nFrom: %s\nTo: %s\nStatus: %s",
            $departure,
            $destination,
            $status
        );
        $result = $this->builder
            ->data($text)
            ->size(200)
            ->margin(10)
            ->build();

        return $result->getDataUri(); // Base64-encoded image
    }
}
