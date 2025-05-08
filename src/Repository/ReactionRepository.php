<?php

namespace App\Repository;

use App\Entity\Reaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    /**
     * Find reactions by post and emoji
     */
    public function findByPostAndEmoji(int $postId, string $emoji): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.post = :postId')
            ->andWhere('r.emoji = :emoji')
            ->setParameter('postId', $postId)
            ->setParameter('emoji', $emoji)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get counts of reactions by type for a post
     */
    public function getCountsByPost(int $postId): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.typeIndex, COUNT(r.id) as count')
            ->andWhere('r.post = :postId')
            ->setParameter('postId', $postId)
            ->groupBy('r.typeIndex')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['typeIndex']] = $row['count'];
        }
        
        return $counts;
    }
}
