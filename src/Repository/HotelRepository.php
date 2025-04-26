<?php

namespace App\Repository;

use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HotelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotel::class);
    }

    /**
     * Find hotels by filter criteria with pagination
     *
     * @param array $criteria Filter criteria
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @return array Returns an array with hotels and pagination data
     */
    public function findByFilterPaginated(array $criteria, int $page = 1, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('h');

        // Filter by name
        if (!empty($criteria['name'])) {
            $qb->andWhere('h.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        // Filter by location
        if (!empty($criteria['location'])) {
            $qb->andWhere('h.location LIKE :location')
               ->setParameter('location', '%' . $criteria['location'] . '%');
        }

        // Filter by minimum rating
        if (!empty($criteria['min_rating'])) {
            $qb->andWhere('h.rating >= :min_rating')
               ->setParameter('min_rating', $criteria['min_rating']);
        }

        // Filter by minimum price
        if (!empty($criteria['min_price'])) {
            $qb->andWhere('h.pricepernight >= :min_price')
               ->setParameter('min_price', $criteria['min_price']);
        }

        // Filter by maximum price
        if (!empty($criteria['max_price'])) {
            $qb->andWhere('h.pricepernight <= :max_price')
               ->setParameter('max_price', $criteria['max_price']);
        }

        // Filter by eco certification
        if (!empty($criteria['eco_certified'])) {
            $qb->andWhere('h.ecocertification = :eco')
               ->setParameter('eco', true);
        }

        // Sort by price (asc/desc)
        if (!empty($criteria['sort_by']) && $criteria['sort_by'] === 'price_asc') {
            $qb->orderBy('h.pricepernight', 'ASC');
        } elseif (!empty($criteria['sort_by']) && $criteria['sort_by'] === 'price_desc') {
            $qb->orderBy('h.pricepernight', 'DESC');
        } elseif (!empty($criteria['sort_by']) && $criteria['sort_by'] === 'rating_desc') {
            $qb->orderBy('h.rating', 'DESC');
        } else {
            // Default sorting by name
            $qb->orderBy('h.name', 'ASC');
        }

        // Count total items for pagination
        $countQb = clone $qb;
        $totalItems = count($countQb->select('h.id')->getQuery()->getResult());

        // Calculate pagination values
        $totalPages = ceil($totalItems / $limit);
        $page = max(1, min($page, $totalPages)); // Ensure page is between 1 and totalPages
        $offset = ($page - 1) * $limit;

        // Add pagination to query
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        // Get paginated results
        $hotels = $qb->getQuery()->getResult();

        return [
            'hotels' => $hotels,
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
     * Find all hotels with pagination
     *
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @return array Returns an array with hotels and pagination data
     */
    public function findAllPaginated(int $page = 1, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('h')
                   ->orderBy('h.name', 'ASC');

        // Count total items for pagination
        $countQb = clone $qb;
        $totalItems = count($countQb->select('h.id')->getQuery()->getResult());

        // Calculate pagination values
        $totalPages = max(1, ceil($totalItems / $limit));
        $page = max(1, min($page, $totalPages)); // Ensure page is between 1 and totalPages
        $offset = ($page - 1) * $limit;

        // Add pagination to query
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        // Get paginated results
        $hotels = $qb->getQuery()->getResult();

        return [
            'hotels' => $hotels,
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