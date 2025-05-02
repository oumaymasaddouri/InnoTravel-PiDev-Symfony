<?php

// src/Controller/UserController.php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/users/search', name: 'users_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $repo): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen($q) < 1) {
            return new JsonResponse([]);
        }

        $users = $repo->createQueryBuilder('u')
            ->where('u.firstName LIKE :term')
            ->setParameter('term', $q.'%')
            ->setMaxResults(10)
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn($u) => [
            'id'        => $u->getId(),
            'firstName' => $u->getFirstName(),
        ], $users);

        return new JsonResponse($data);
    }
}
