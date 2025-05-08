<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyService
{
    private const API_URL = 'https://api.exchangerate.host/';
    private const CACHE_EXPIRATION = 3600; // 1 hour
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private FilesystemAdapter $cache;

    // Hardcoded exchange rates as fallback
    private array $fallbackRates = [
        'USD' => 1.0,
        'EUR' => 0.93,
        'GBP' => 0.79,
        'JPY' => 151.77,
        'CAD' => 1.38,
        'AUD' => 1.52,
        'CHF' => 0.91,
        'CNY' => 7.24,
        'INR' => 83.50,
        'TND' => 3.12
    ];

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['CURRENCY_API_KEY'] ?? ''; // Get API key from environment variable
        $this->cache = new FilesystemAdapter('currency_rates', self::CACHE_EXPIRATION);
    }

    /**
     * Get available currencies
     *
     * @return array
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'JPY' => 'Japanese Yen (¥)',
            'CAD' => 'Canadian Dollar (C$)',
            'AUD' => 'Australian Dollar (A$)',
            'CHF' => 'Swiss Franc (CHF)',
            'CNY' => 'Chinese Yuan (¥)',
            'INR' => 'Indian Rupee (₹)',
            'TND' => 'Tunisian Dinar (DT)'
        ];
    }

    /**
     * Get currency symbols
     *
     * @return array
     */
    public function getCurrencySymbols(): array
    {
        return [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'INR' => '₹',
            'TND' => 'DT'
        ];
    }

    /**
     * Get exchange rate from USD to target currency
     *
     * @param string $targetCurrency
     * @return float
     */
    public function getExchangeRate(string $targetCurrency = 'USD'): float
    {
        // Return 1 if target is USD (base currency)
        if ($targetCurrency === 'USD') {
            return 1.0;
        }

        // Return fallback rate directly to avoid API issues
        return $this->fallbackRates[$targetCurrency] ?? 1.0;

        /* Commented out API call due to issues
        // Try to get from cache first
        return $this->cache->get('rate_' . $targetCurrency, function (ItemInterface $item) use ($targetCurrency) {
            $item->expiresAfter(self::CACHE_EXPIRATION);

            try {
                // Try the new API endpoint format
                $response = $this->httpClient->request('GET', self::API_URL . 'latest', [
                    'query' => [
                        'base' => 'USD',
                        'symbols' => $targetCurrency
                    ]
                ]);

                $data = $response->toArray();

                if (isset($data['rates'][$targetCurrency])) {
                    return (float) $data['rates'][$targetCurrency];
                }

                // If API call fails, use fallback rates
                return $this->fallbackRates[$targetCurrency] ?? 1.0;
            } catch (\Exception $e) {
                // Log error or handle exception
                return $this->fallbackRates[$targetCurrency] ?? 1.0;
            }
        });
        */
    }

    /**
     * Convert price from USD to target currency
     *
     * @param float $price
     * @param string $targetCurrency
     * @return float
     */
    public function convertPrice(float $price, string $targetCurrency = 'USD'): float
    {
        $rate = $this->getExchangeRate($targetCurrency);
        return round($price * $rate, 2);
    }
}
