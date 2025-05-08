<?php

namespace App\Repository;

use App\Entity\Itineraire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Itineraire>
 *
 * @method Itineraire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Itineraire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Itineraire[]    findAll()
 * @method Itineraire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItineraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Itineraire::class);
    }

    public function save(Itineraire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Itineraire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
