<?php

namespace App\Controller\Admin;

use App\Repository\FeedbackRepository;
use App\Service\FeedbackAnalyzerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Feedback;

class FeedbackAnalyticsController extends AbstractController
{
    private FeedbackRepository $feedbackRepository;
    private FeedbackAnalyzerService $analyzerService;

    public function __construct(
        FeedbackRepository $feedbackRepository,
        FeedbackAnalyzerService $analyzerService
    ) {
        $this->feedbackRepository = $feedbackRepository;
        $this->analyzerService = $analyzerService;
    }

    #[Route('/admin/feedback/analytics', name: 'admin_feedback_analytics')]
    public function index(): Response
    {
        // Get all feedback
        $feedbacks = $this->feedbackRepository->findBy([], ['date' => 'DESC']);
        
        // Initialize analytics data
        $sentimentDistribution = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
        $topicCounts = [];
        $urgentCount = 0;
        $analyzedFeedbacks = [];
        
        // For trend analysis
        $feedbacksByDate = [];
        
        foreach ($feedbacks as $feedback) {
            $analysis = $this->analyzerService->analyzeFeedback($feedback);
            $feedback->analysis = $analysis;
            $analyzedFeedbacks[] = $feedback;
            
            // Count sentiments
            $sentiment = $analysis['sentiment'] ?? 'neutral';
            $sentimentDistribution[$sentiment]++;
            
            // Count topics
            foreach ($analysis['topics'] as $topic) {
                $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
            }
            
            // Count urgent feedbacks
            if (($analysis['urgency'] ?? 'low') === 'high') {
                $urgentCount++;
            }
            
            // Group by date for trend analysis
            $dateKey = $feedback->getDate()->format('Y-m-d');
            if (!isset($feedbacksByDate[$dateKey])) {
                $feedbacksByDate[$dateKey] = [
                    'positive' => 0,
                    'negative' => 0,
                    'neutral' => 0
                ];
            }
            $feedbacksByDate[$dateKey][$sentiment]++;
        }
        
        // Sort topics by count and get top 5
        arsort($topicCounts);
        $topTopics = array_slice($topicCounts, 0, min(5, count($topicCounts)), true);
        
        // Prepare trend data (last 7 days)
        $today = new \DateTime();
        $trendDates = [];
        $trendPositive = [];
        $trendNegative = [];
        
        // Generate last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-$i days");
            $dateKey = $date->format('Y-m-d');
            
            $trendDates[] = $dateKey;
            $trendPositive[] = $feedbacksByDate[$dateKey]['positive'] ?? 0;
            $trendNegative[] = $feedbacksByDate[$dateKey]['negative'] ?? 0;
        }
        
        // Prepare analytics data
        $analyticsData = [
            'recentFeedbacks' => $analyzedFeedbacks,
            'sentimentDistribution' => $sentimentDistribution,
            'topTopics' => $topTopics,
            'urgentCount' => $urgentCount,
            'trendDates' => $trendDates,
            'trendPositive' => $trendPositive,
            'trendNegative' => $trendNegative
        ];
        
        return $this->render('admin/feedback/analytics.html.twig', [
            'analytics' => $analyticsData
        ]);
    }
    
    private function getSentimentDistribution(array $feedbacks): array
    {
        $distribution = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
        
        foreach ($feedbacks as $feedback) {
            $analysis = $this->analyzerService->analyzeFeedback($feedback);
            $distribution[$analysis['sentiment']]++;
        }
        
        return $distribution;
    }
    
    private function getTopTopics(array $feedbacks): array
    {
        $allTopics = [];
        
        foreach ($feedbacks as $feedback) {
            $analysis = $this->analyzerService->analyzeFeedback($feedback);
            foreach ($analysis['topics'] as $topic) {
                $allTopics[$topic] = ($allTopics[$topic] ?? 0) + 1;
            }
        }
        
        arsort($allTopics);
        return array_slice($allTopics, 0, 5, true); // Top 5 topics
    }
    
    private function getUrgentFeedbacks(array $feedbacks): array
    {
        $urgentFeedbacks = [];
        
        foreach ($feedbacks as $feedback) {
            $analysis = $this->analyzerService->analyzeFeedback($feedback);
            if ($analysis['urgency'] === 'high') {
                $urgentFeedbacks[] = [
                    'feedback' => $feedback,
                    'analysis' => $analysis
                ];
            }
        }
        
        return $urgentFeedbacks;
    }

    #[Route('/admin/feedback/detail/{id}', name: 'admin_feedback_detail')]
    public function detail(Feedback $feedback): Response
    {
        $analysis = $this->analyzerService->analyzeFeedback($feedback);
        
        return $this->render('admin/feedback/detail.html.twig', [
            'feedback' => $feedback,
            'analysis' => $analysis
        ]);
    }
}








