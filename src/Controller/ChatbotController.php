<?php

namespace App\Controller;

use App\Service\GeminiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
 /*   #[Route('/chatbot', name: 'app_chatbot', options: ['expose' => true], methods: ['GET', 'POST'])]
    public function chatbot(Request $request, GeminiChatService $geminiChatService): JsonResponse
    {
        $userMessage = $request->query->get('message') ?? $request->request->get('message');

        if (!$userMessage) {
            return new JsonResponse(['error' => 'No message provided'], 400);
        }

        $botReply = $geminiChatService->sendMessage($userMessage);

        return new JsonResponse([
            'userMessage' => $userMessage,
            'botReply' => $botReply,
        ]);
    }*/

    #[Route('/api/travel-chat', name: 'api_travel_chat', options: ['expose' => true], methods: ['GET', 'POST'])]
    public function travelChat(GeminiChatService $geminiChatService): JsonResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/data/questions.json';

        if (!file_exists($filePath)) {
            return new JsonResponse(['error' => 'Questions file not found.'], 404);
        }

        $jsonContent = file_get_contents($filePath);
        $questions = json_decode($jsonContent, true);

        $randomQuestion = $questions[array_rand($questions)]['question'];

        $geminiResponse = $geminiChatService->sendMessage($randomQuestion);

        return new JsonResponse([
            'question' => $randomQuestion,
            'response' => json_decode($geminiResponse, true) ?? $geminiResponse
        ]);
    }
}