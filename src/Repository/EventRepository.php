<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active events with pagination and optional filters
     */
    public function findActiveEvents(
        int $page = 1, 
        int $limit = 10, 
        ?string $search = null, 
        ?string $category = null, 
        ?\DateTime $startDate = null, 
        ?\DateTime $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->andWhere('e.startDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($search) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search OR e.location LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('e.category = :category')
               ->setParameter('category', $category);
        }

        if ($startDate) {
            $qb->andWhere('e.startDate >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.startDate <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $firstResult = ($page - 1) * $limit;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery();

        $paginator = new Paginator($query, true);
        
        return [
            'events' => $paginator,
            'totalItems' => count($paginator),
            'totalPages' => ceil(count($paginator) / $limit)
        ];
    }

    /**
     * Find all events with pagination and optional filters for admin
     */
    public function findAllEventsForAdmin(
        int $page = 1, 
        int $limit = 10, 
        ?string $search = null, 
        ?string $category = null, 
        ?bool $isActive = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'DESC');

        if ($search) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search OR e.location LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('e.category = :category')
               ->setParameter('category', $category);
        }

        if ($isActive !== null) {
            $qb->andWhere('e.isActive = :active')
               ->setParameter('active', $isActive);
        }

        $firstResult = ($page - 1) * $limit;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery();

        $paginator = new Paginator($query, true);
        
        return [
            'events' => $paginator,
            'totalItems' => count($paginator),
            'totalPages' => ceil(count($paginator) / $limit)
        ];
    }

    /**
     * Find upcoming events
     */
    public function findUpcomingEvents(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->andWhere('e.startDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all event categories
     */
    public function findAllCategories(): array
    {
        $result = $this->createQueryBuilder('e')
            ->select('e.category')
            ->distinct(true)
            ->getQuery()
            ->getResult();
        
        return array_map(fn($item) => $item['category'], $result);
    }
}
