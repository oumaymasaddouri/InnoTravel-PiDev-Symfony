<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function save(Participation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Participation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find participations by user with pagination
     */
    public function findByUserWithPagination(User $user, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.registrationDate', 'DESC');

        $firstResult = ($page - 1) * $limit;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery();

        $paginator = new Paginator($query, true);
        
        return [
            'participations' => $paginator,
            'totalItems' => count($paginator),
            'totalPages' => ceil(count($paginator) / $limit)
        ];
    }

    /**
     * Find participations by event with pagination
     */
    public function findByEventWithPagination(Event $event, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.event = :event')
            ->setParameter('event', $event)
            ->orderBy('p.registrationDate', 'DESC');

        $firstResult = ($page - 1) * $limit;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($limit)
                    ->getQuery();

        $paginator = new Paginator($query, true);
        
        return [
            'participations' => $paginator,
            'totalItems' => count($paginator),
            'totalPages' => ceil(count($paginator) / $limit)
        ];
    }

    /**
     * Find participation by ticket code
     */
    public function findByTicketCode(string $ticketCode): ?Participation
    {
        return $this->createQueryBuilder('p')
            ->where('p.ticketCode = :ticketCode')
            ->setParameter('ticketCode', $ticketCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count total participants for an event
     */
    public function countTotalParticipants(Event $event): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.numberOfPersons) as total')
            ->where('p.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }

    /**
     * Count attended participants for an event
     */
    public function countAttendedParticipants(Event $event): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.numberOfPersons) as total')
            ->where('p.event = :event')
            ->andWhere('p.attended = :attended')
            ->setParameter('event', $event)
            ->setParameter('attended', true)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }
}
