<?php

// src/Repository/transportRepository.php

namespace App\Repository;

use App\Entity\transport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class transportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, transport::class);
    }

    public function createSearchQueryBuilder(
        ?string $searchTerm = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.id');

        if ($searchTerm) {
            $qb->andWhere('r.carModel LIKE :searchTerm ')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        return $qb;
    }

    public function createPaginatedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.id', 'ASC');
    }

    public function getCountByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }
}