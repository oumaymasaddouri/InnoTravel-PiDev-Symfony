<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\User;
use App\Repository\HotelRepository;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/hotel')]
final class UserHotelController extends AbstractController
{
    #[Route('/', name: 'user_hotel_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, CurrencyService $currencyService, SessionInterface $session): Response
    {
        // Get user from session
        $user = $this->getUserFromSession($session, $entityManager);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        /** @var HotelRepository $hotelRepository */
        $hotelRepository = $entityManager->getRepository(Hotel::class);

        // Get pagination parameters
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 3; // Number of hotels per page

        // Get selected currency (default to USD)
        $selectedCurrency = $request->query->get('currency', 'USD');

        // Get filter parameters from request
        $filterCriteria = [
            'name' => $request->query->get('name'),
            'location' => $request->query->get('city') ?: $request->query->get('location'),
            'min_rating' => $request->query->get('min_rating'),
            'min_price' => $request->query->get('minPrice'),
            'max_price' => $request->query->get('maxPrice') ?: $request->query->get('max_price'),
            'eco_certified' => $request->query->has('eco_certified') ? true : false,
            'sort_by' => $request->query->get('sortBy') ?: $request->query->get('sort_by'),
        ];

        // Get all locations for the filter dropdown
        $locations = $hotelRepository->createQueryBuilder('h')
            ->select('DISTINCT h.location')
            ->orderBy('h.location', 'ASC')
            ->getQuery()
            ->getResult();

        // Format locations array
        $locationOptions = [];
        foreach ($locations as $item) {
            $locationOptions[] = $item['location'];
        }

        // Apply filters if any
        $hasFilters = false;
        foreach ($filterCriteria as $key => $value) {
            if (!empty($value) || $value === true) {
                $hasFilters = true;
                break;
            }
        }

        // Get paginated results
        if ($hasFilters) {
            $result = $hotelRepository->findByFilterPaginated($filterCriteria, $page, $limit);
        } else {
            $result = $hotelRepository->findAllPaginated($page, $limit);
        }

        // Extract hotels and pagination data
        $hotels = $result['hotels'];
        $pagination = $result['pagination'];

        // Get currency data
        $currencies = $currencyService->getAvailableCurrencies();
        $currencySymbols = $currencyService->getCurrencySymbols();
        $exchangeRate = $currencyService->getExchangeRate($selectedCurrency);

        return $this->render('hotel/user_index.html.twig', [
            'user' => $user,
            'hotels' => $hotels,
            'locations' => $locationOptions,
            'filters' => $filterCriteria,
            'pagination' => $pagination,
            'currencies' => $currencies,
            'selectedCurrency' => $selectedCurrency,
            'currencySymbol' => $currencySymbols[$selectedCurrency] ?? '$',
            'exchangeRate' => $exchangeRate,
        ]);
    }

    #[Route('/{slug}', name: 'user_hotel_show', methods: ['GET'])]
    public function show(Request $request, Hotel $hotel, CurrencyService $currencyService, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        // Get user from session
        $user = $this->getUserFromSession($session, $entityManager);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Get selected currency (default to USD)
        $selectedCurrency = $request->query->get('currency', 'USD');

        // Get currency data
        $currencies = $currencyService->getAvailableCurrencies();
        $currencySymbols = $currencyService->getCurrencySymbols();
        $exchangeRate = $currencyService->getExchangeRate($selectedCurrency);

        return $this->render('hotel/user_show.html.twig', [
            'user' => $user,
            'hotel' => $hotel,
            'currencies' => $currencies,
            'selectedCurrency' => $selectedCurrency,
            'currencySymbol' => $currencySymbols[$selectedCurrency] ?? '$',
            'exchangeRate' => $exchangeRate,
        ]);
    }

    private function getUserFromSession(SessionInterface $session, EntityManagerInterface $em): ?User
    {
        return $session->get('user_id') ? $em->getRepository(User::class)->find($session->get('user_id')) : null;
    }
}
