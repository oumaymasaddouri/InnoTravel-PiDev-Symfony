<?php

namespace App\Repository;

use App\Entity\Transport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TransportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transport::class);
    }

    public function createSearchQueryBuilder(
        ?string $searchTerm = null,
        ?string $status = null,
        ?string $vehicleType = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('t.carModel LIKE :searchTerm OR t.licensePlate LIKE :searchTerm OR t.carColor LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($vehicleType) {
            $qb->andWhere('t.vehicleType = :vehicleType')
               ->setParameter('vehicleType', $vehicleType);
        }

        return $qb;
    }

    public function createPaginatedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');
    }

    public function getCountByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function getAverageLuggageCapacity(): ?float
    {
        $result = $this->createQueryBuilder('t')
            ->select('AVG(t.maxLuggage) as avgLuggage')
            ->where('t.maxLuggage IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    public function getVehicleTypeDistribution(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.vehicleType, COUNT(t.id) as count')
            ->groupBy('t.vehicleType')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($result as $row) {
            $distribution[$row['vehicleType']] = (int) $row['count'];
        }

        return $distribution;
    }

    public function getColorDistribution(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.carColor, COUNT(t.id) as count')
            ->groupBy('t.carColor')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($result as $row) {
            $distribution[$row['carColor']] = (int) $row['count'];
        }

        return $distribution;
    }

    public function findActiveVehicles(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', 'Active')
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
