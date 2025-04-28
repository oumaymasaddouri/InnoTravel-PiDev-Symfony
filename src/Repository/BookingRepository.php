<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Find bookings for a user with pagination
     *
     * @param Users $user The user to find bookings for
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @return array Returns an array with bookings and pagination data
     */
    public function findByUserPaginated(Users $user, int $page = 1, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('b')
                   ->andWhere('b.userId = :user')
                   ->setParameter('user', $user)
                   ->orderBy('b.startdate', 'DESC');

        // Count total items for pagination
        $countQb = clone $qb;
        $totalItems = count($countQb->select('b.id')->getQuery()->getResult());

        // Calculate pagination values
        $totalPages = max(1, ceil($totalItems / $limit));
        $page = max(1, min($page, $totalPages)); // Ensure page is between 1 and totalPages
        $offset = ($page - 1) * $limit;

        // Add pagination to query
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        // Get paginated results
        $bookings = $qb->getQuery()->getResult();

        return [
            'bookings' => $bookings,
            'pagination' => [
                'totalItems' => $totalItems,
                'itemsPerPage' => $limit,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'offset' => $offset
            ]
        ];
    }

    /**
     * Get bookings grouped by month for the current year
     *
     * @return array Returns an array with months as keys and booking counts as values
     */
    public function getBookingsByMonth(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT
                MONTH(b.startdate) as month,
                COUNT(b.id) as count
            FROM booking b
            WHERE YEAR(b.startdate) = YEAR(CURRENT_DATE())
            GROUP BY MONTH(b.startdate)
            ORDER BY month ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        // Initialize all months with 0 count
        $bookingsByMonth = array_fill(1, 12, 0);

        // Fill in actual counts
        foreach ($result as $row) {
            $bookingsByMonth[$row['month']] = (int)$row['count'];
        }

        return $bookingsByMonth;
    }

    /**
     * Get booking counts by status
     *
     * @return array Returns an array with status counts
     */
    public function getBookingsByStatus(): array
    {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.status, COUNT(b.id) as count')
                   ->groupBy('b.status');

        $result = $qb->getQuery()->getResult();

        // Initialize status counts
        $statusCounts = [
            'pending' => 0,
            'confirmed' => 0,
            'cancelled' => 0
        ];

        // Fill in actual counts
        foreach ($result as $row) {
            $statusCounts[$row['status']] = (int)$row['count'];
        }

        return $statusCounts;
    }
}