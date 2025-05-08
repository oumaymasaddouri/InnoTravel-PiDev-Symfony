<?php

namespace App\Controller;
use App\Service\GeminiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    #[Route('/api/travel-chat', name: 'api_travel_chat', methods: ['GET', 'POST'])]
    public function travelChat(Request $request, GeminiChatService $geminiChatService): JsonResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/data/questions.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Questions file not found.'], 404);
        }

        $jsonContent = file_get_contents($filePath);
        $questions = json_decode($jsonContent, true);

        // Get user message from request if available
        $userMessage = $request->query->get('message') ?? $request->request->get('message');

        $question = '';

        if ($userMessage) {
            // If user provided a message, use it
            $geminiResponse = $geminiChatService->sendMessage($userMessage);
            $question = $userMessage;
        } else {
            // Otherwise use a random question
            $randomQuestion = $questions[array_rand($questions)]['question'];
            $geminiResponse = $geminiChatService->sendMessage($randomQuestion);
            $question = $randomQuestion;
        }

        return new JsonResponse([
            'question' => $question,
            'response' => json_decode($geminiResponse, true) ?? $geminiResponse
        ]);
    }
}
