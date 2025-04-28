<?php
// src/Repository/ReservationRepository.php

namespace App\Repository;

use App\Entity\Reservation;
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
        ?string $status = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('r.pickup_address LIKE :searchTerm OR r.destination_address LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        return $qb;
    }

    public function searchAndFilter(string $searchTerm = null, string $status = null)
{
    $qb = $this->createQueryBuilder('r')
        ->orderBy('r.createdAt', 'DESC');

    if ($searchTerm) {
        $qb->andWhere('r.pickup_address LIKE :searchTerm OR r.destination_address LIKE :searchTerm')
           ->setParameter('searchTerm', '%'.$searchTerm.'%');
    }

    if ($status) {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', $status);
    }

    return $qb->getQuery();
}

    public function getCountByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }
}