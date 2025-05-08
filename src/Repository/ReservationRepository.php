<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function createSearchQueryBuilder(
        ?string $searchTerm = null, 
        ?string $status = null,
        ?User $user = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.transport', 't')
            ->orderBy('r.createdAt', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('r.pickup_address LIKE :searchTerm OR r.destination_address LIKE :searchTerm OR t.carModel LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        if ($user) {
            $qb->andWhere('r.user = :user')
               ->setParameter('user', $user);
        }

        return $qb;
    }

    public function searchAndFilter(string $searchTerm = null, string $status = null, User $user = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.transport', 't')
            ->orderBy('r.createdAt', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('r.pickup_address LIKE :searchTerm OR r.destination_address LIKE :searchTerm OR t.carModel LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        if ($user) {
            $qb->andWhere('r.user = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery();
    }

    public function getCountByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function getReservationStatistics(): array
    {
        $stats = [];
        
        // Get counts by status
        $statusCounts = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();
            
        $stats['statusCounts'] = [];
        foreach ($statusCounts as $row) {
            $stats['statusCounts'][$row['status']] = (int) $row['count'];
        }
        
        // Get recent reservations count (last 30 days)
        $thirtyDaysAgo = new \DateTime('-30 days');
        $stats['recentCount'] = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :date')
            ->setParameter('date', $thirtyDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();
            
        // Get average price
        $avgPrice = $this->createQueryBuilder('r')
            ->select('AVG(r.price) as avgPrice')
            ->where('r.price IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
            
        $stats['averagePrice'] = $avgPrice ? round((float) $avgPrice, 2) : 0;
        
        return $stats;
    }

    public function findUserReservations(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.transport', 't')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
