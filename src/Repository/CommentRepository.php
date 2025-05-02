<?php
// src/Repository/CommentRepository.php
namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    // Exemple de méthode pour récupérer les comments d'un post
    public function findByPost(int $postId)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
}