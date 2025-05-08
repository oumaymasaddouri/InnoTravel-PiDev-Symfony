<?php

namespace App\Service;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ProcessFeedbackMessage;

class FeedbackAnalyzerService
{
    private FeedbackRepository $feedbackRepository;
    private MessageBusInterface $messageBus;
    
    public function __construct(
        FeedbackRepository $feedbackRepository,
        MessageBusInterface $messageBus
    ) {
        $this->feedbackRepository = $feedbackRepository;
        $this->messageBus = $messageBus;
    }
    
    public function analyzeFeedback(Feedback $feedback): array
    {
        // Queue the feedback for async processing
        $this->messageBus->dispatch(new ProcessFeedbackMessage($feedback->getId()));
        
        // Perform immediate basic analysis
        return $this->performBasicAnalysis($feedback->getContent());
    }
    
    private function performBasicAnalysis(string $content): array
    {
        // Extract sentiment (positive/negative/neutral)
        $sentiment = $this->detectSentiment($content);
        
        // Extract key topics mentioned
        $topics = $this->extractTopics($content);
        
        return [
            'sentiment' => $sentiment,
            'topics' => $topics,
            'urgency' => $this->determineUrgency($sentiment, $content)
        ];
    }
    
    private function detectSentiment(string $text): string
    {
        // Simple sentiment analysis based on keyword matching
        $positiveWords = ['great', 'excellent', 'good', 'amazing', 'love', 'helpful'];
        $negativeWords = ['bad', 'poor', 'terrible', 'awful', 'hate', 'issue', 'problem'];
        
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        if ($positiveCount > $negativeCount) return 'positive';
        if ($negativeCount > $positiveCount) return 'negative';
        return 'neutral';
    }
    
    private function extractTopics(string $text): array
    {
        // Topic categories relevant to travel platform
        $topicKeywords = [
            'booking' => ['book', 'reservation', 'cancel', 'refund'],
            'interface' => ['app', 'website', 'interface', 'design', 'navigation'],
            'customer_service' => ['support', 'help', 'service', 'contact', 'response'],
            'travel_experience' => ['trip', 'hotel', 'flight', 'tour', 'guide'],
            'payment' => ['payment', 'price', 'cost', 'money', 'expensive', 'cheap']
        ];
        
        $text = strtolower($text);
        $foundTopics = [];
        
        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $foundTopics[$topic] = ($foundTopics[$topic] ?? 0) + 1;
                    break;
                }
            }
        }
        
        // Return topics sorted by relevance
        arsort($foundTopics);
        return array_keys($foundTopics);
    }
    
    private function determineUrgency(string $sentiment, string $text): string
    {
        $urgentKeywords = ['immediately', 'urgent', 'emergency', 'asap', 'critical'];
        
        foreach ($urgentKeywords as $keyword) {
            if (strpos(strtolower($text), $keyword) !== false) {
                return 'high';
            }
        }
        
        return ($sentiment === 'negative') ? 'medium' : 'low';
    }
}