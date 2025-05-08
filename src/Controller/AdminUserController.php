<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/users/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}/ban', name: 'admin_users_ban', methods: ['POST'])]
    public function banUser(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot ban yourself.');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setIsBanned(!$user->isBanned());
        $this->entityManager->flush();

        $status = $user->isBanned() ? 'banned' : 'unbanned';
        $this->addFlash('success', "User has been {$status} successfully.");

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete yourself.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User has been deleted successfully.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/statistics', name: 'admin_users_statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        // Get total users count
        $totalUsers = $this->userRepository->count([]);
        
        // Get banned users count
        $bannedUsers = $this->userRepository->count(['isBanned' => true]);
        
        // Get users by role
        $adminUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
        
        $adminCount = count($adminUsers);
        $regularUserCount = $totalUsers - $adminCount;
        
        // Get users by gender
        $maleUsers = $this->userRepository->count(['gender' => 'Male']);
        $femaleUsers = $this->userRepository->count(['gender' => 'Female']);
        $otherGenderUsers = $totalUsers - $maleUsers - $femaleUsers;
        
        // Get users by country (top 5)
        $countries = $this->userRepository->createQueryBuilder('u')
            ->select('u.country, COUNT(u.id) as userCount')
            ->where('u.country IS NOT NULL')
            ->groupBy('u.country')
            ->orderBy('userCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
            
        // Get new users in the last 30 days
        $thirtyDaysAgo = new \DateTime('-30 days');
        $newUsers = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.updatedAt >= :date')
            ->setParameter('date', $thirtyDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/users/statistics.html.twig', [
            'totalUsers' => $totalUsers,
            'bannedUsers' => $bannedUsers,
            'adminCount' => $adminCount,
            'regularUserCount' => $regularUserCount,
            'maleUsers' => $maleUsers,
            'femaleUsers' => $femaleUsers,
            'otherGenderUsers' => $otherGenderUsers,
            'countries' => $countries,
            'newUsers' => $newUsers,
        ]);
    }
}
