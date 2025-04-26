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
}