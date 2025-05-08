<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function findOneByUser(User $user): ?Feedback
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function userHasFeedback(User $user): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('count(f.id)')
            ->andWhere('f.userId = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllSortedByDateDesc(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
