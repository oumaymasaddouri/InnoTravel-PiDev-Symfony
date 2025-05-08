<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\Itineraire;
use App\Entity\RelationTripItineraire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistics')]
#[IsGranted('ROLE_ADMIN')]
class AdminTripStatisticsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/trips', name: 'admin_trip_statistics')]
    public function tripStatistics(): Response
    {
        // Get total trips count
        $totalTrips = $this->entityManager->getRepository(Trip::class)->count([]);
        
        // Get trips by status
        $tripsByStatus = $this->getTripsByStatus();
        
        // Get trips created in the last 30 days
        $recentTrips = $this->getRecentTrips();
        
        // Get average budget
        $averageBudget = $this->getAverageBudget();
        
        // Get most popular itineraries
        $popularItineraries = $this->getMostPopularItineraries();
        
        // Get trips by month for the current year
        $tripsByMonth = $this->getTripsByMonth();
        
        // Get total itineraries count
        $totalItineraries = $this->entityManager->getRepository(Itineraire::class)->count([]);
        
        return $this->render('admin/trips/statistics.html.twig', [
            'totalTrips' => $totalTrips,
            'tripsByStatus' => $tripsByStatus,
            'recentTrips' => $recentTrips,
            'averageBudget' => $averageBudget,
            'popularItineraries' => $popularItineraries,
            'tripsByMonth' => $tripsByMonth,
            'totalItineraries' => $totalItineraries,
        ]);
    }
    
    private function getTripsByStatus(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT 
                status, 
                COUNT(id) as count 
            FROM trip 
            GROUP BY status
        ';
        
        $result = $conn->executeQuery($sql)->fetchAllAssociative();
        
        // Format the result as needed for the chart
        $formattedResult = [];
        foreach ($result as $row) {
            $formattedResult[$row['status']] = (int)$row['count'];
        }
        
        return $formattedResult;
    }
    
    private function getRecentTrips(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
           ->from(Trip::class, 't')
           ->where('t.departure >= :date')
           ->setParameter('date', $thirtyDaysAgo);
           
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
    
    private function getAverageBudget(): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('AVG(t.budget)')
           ->from(Trip::class, 't');
           
        $result = $qb->getQuery()->getSingleScalarResult();
        
        return $result ? round((float)$result, 2) : 0;
    }
    
    private function getMostPopularItineraries(int $limit = 5): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('i.name', 'COUNT(r.id) as count')
           ->from(RelationTripItineraire::class, 'r')
           ->join('r.itineraire', 'i')
           ->groupBy('i.id')
           ->orderBy('count', 'DESC')
           ->setMaxResults($limit);
           
        return $qb->getQuery()->getResult();
    }
    
    private function getTripsByMonth(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT
                MONTH(departure) as month,
                COUNT(id) as count
            FROM trip
            WHERE YEAR(departure) = YEAR(CURRENT_DATE())
            GROUP BY MONTH(departure)
            ORDER BY month ASC
        ';

        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        // Initialize all months with 0 count
        $tripsByMonth = array_fill(1, 12, 0);

        // Fill in actual counts
        foreach ($result as $row) {
            $tripsByMonth[(int)$row['month']] = (int)$row['count'];
        }

        return $tripsByMonth;
    }
}
