<?php

namespace App\Repository;

use App\Entity\RelationTripItineraire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RelationTripItineraire>
 *
 * @method RelationTripItineraire|null find($id, $lockMode = null, $lockVersion = null)
 * @method RelationTripItineraire|null findOneBy(array $criteria, array $orderBy = null)
 * @method RelationTripItineraire[]    findAll()
 * @method RelationTripItineraire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelationTripItineraireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RelationTripItineraire::class);
    }

    public function save(RelationTripItineraire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RelationTripItineraire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
