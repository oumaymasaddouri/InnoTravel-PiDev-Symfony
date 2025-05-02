<?php

namespace App\Repository;

use App\Entity\Reaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reaction>
 */
class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    /**
     * Exemple : trouver les réactions par post et emoji
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
    public function getCountsByPost(int $postId): array
{
    $qb = $this->createQueryBuilder('r')
        ->select('r.typeIndex AS idx, COUNT(r.id) AS cnt')
        ->andWhere('r.post = :postId')
        ->setParameter('postId', $postId)
        ->groupBy('r.typeIndex');

    // retourne un tableau de tableaux: [ ['idx'=>1,'cnt'=>5], ... ]
    $rows = $qb->getQuery()->getScalarResult();

    // on transforme en [1=>5, 2=>3, ...]
    $counts = [];
    foreach ($rows as $r) {
        $counts[(int)$r['idx']] = (int)$r['cnt'];
    }
    return $counts;
}

}
