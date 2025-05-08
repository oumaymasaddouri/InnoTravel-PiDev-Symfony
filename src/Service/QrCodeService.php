<?php
namespace App\Service;

use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    private BuilderInterface $builder;

    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Create a QR code for a reservation
     *
     * @param string $departure
     * @param string $destination
     * @param string $status
     * @return string Base64-encoded image
     */
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
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return $result->getDataUri(); // Base64-encoded image
    }

    /**
     * Create a custom QR code with specific content
     *
     * @param string $content
     * @param int $size
     * @param string $foregroundColor Hex color code (e.g., '#4285F4')
     * @return string Base64-encoded image
     */
    public function createCustomQrCode(string $content, int $size = 300, string $foregroundColor = '#4285F4'): string
    {
        // Convert hex color to RGB
        $color = $this->hexToRgb($foregroundColor);
        
        $result = $this->builder
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size($size)
            ->margin(10)
            ->foregroundColor(new Color($color['r'], $color['g'], $color['b']))
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->writer(new PngWriter())
            ->build();

        return $result->getDataUri();
    }

    /**
     * Convert hex color to RGB
     *
     * @param string $hex
     * @return array
     */
    private function hexToRgb(string $hex): array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Parse the hex color
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}
