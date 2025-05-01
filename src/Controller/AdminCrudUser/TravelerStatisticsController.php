<?php

namespace App\Controller\AdminCrudUser;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Doctrine\ORM\EntityManagerInterface;

class TravelerStatisticsController extends AbstractDashboardController
{
    #[Route('/admin/travelers/statistics', name: 'admin_travelers_statistics', methods: ['GET'])]
    public function statistics(EntityManagerInterface $em, ChartBuilderInterface $chartBuilder): Response
    {
        $users = $em->getRepository(User::class)->findAll();

        // === Country Statistics ===
        $countryCounts = [];
        foreach ($users as $user) {
            $country = $user->getCountry() ?? 'Unknown';
            $countryCounts[$country] = ($countryCounts[$country] ?? 0) + 1;
        }

        $countryChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $countryChart->setData([
            'labels' => array_keys($countryCounts),
            'datasets' => [[
                'label' => 'Travelers by Country',
                'data' => array_values($countryCounts),
            ]],
        ]);
        $countryChart->setOptions([
            'responsive' => true,
            'plugins' => ['legend' => ['position' => 'bottom']]
        ]);

        // === Age Categories Statistics ===
        $ageGroups = ['15-30' => 0, '31-45' => 0, '45+' => 0];
        $now = new \DateTime();
        foreach ($users as $user) {
            if ($dob = $user->getDateOfBirth()) {
                $age = $dob->diff($now)->y;
                if ($age >= 15 && $age <= 30) {
                    $ageGroups['15-30']++;
                } elseif ($age >= 31 && $age <= 45) {
                    $ageGroups['31-45']++;
                } elseif ($age > 45) {
                    $ageGroups['45+']++;
                }
            }
        }

        $ageChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $ageChart->setData([
            'labels' => array_keys($ageGroups),
            'datasets' => [[
                'label' => 'Travelers by Age Group',
                'data' => array_values($ageGroups),
            ]],
        ]);
        $ageChart->setOptions([
            'responsive' => true,
            'plugins' => ['legend' => ['position' => 'bottom']]
        ]);

        return $this->render('Admin/CrudUser/statistics.html.twig', [
            'countryChart' => $countryChart,
            'ageChart' => $ageChart,
        ]);        
    }
}
