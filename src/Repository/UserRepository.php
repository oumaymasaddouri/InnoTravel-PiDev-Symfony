<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepository extends ServiceEntityRepository
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ManagerRegistry $registry, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
        $this->passwordHasher = $passwordHasher;
    }

    public function emailExists(string $email): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function updatePassword(string $email, string $plainPassword): void
    {
        $user = $this->getUserByEmail($email);
        if ($user) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $this->getEntityManager()->flush();
        }
    }

    public function authenticate(string $email, string $password): bool
    {
        $user = $this->getUserByEmail($email);
        if (!$user) return false;

        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}
