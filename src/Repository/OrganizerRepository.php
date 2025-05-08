<?php

namespace App\Repository;

use App\Entity\Organizer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Organizer>
 */
class OrganizerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organizer::class);
    }

    public function save(Organizer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Organizer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all organizers with pagination and optional search
     */
    public function findAllWithPagination(int $page = 1, int $limit = 10, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.name', 'ASC');

        if ($search) {
            $qb->andWhere('o.name LIKE :search OR o.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $firstResult = ($page - 1) * $limit;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery();

        $paginator = new Paginator($query, true);
        
        return [
            'organizers' => $paginator,
            'totalItems' => count($paginator),
            'totalPages' => ceil(count($paginator) / $limit)
        ];
    }

    /**
     * Find verified organizers
     */
    public function findVerifiedOrganizers(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
