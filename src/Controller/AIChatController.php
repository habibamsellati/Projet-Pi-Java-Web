<?php

namespace App\Controller;

use App\Service\HuggingFaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AIChatController extends AbstractController
{
    #[Route('/api/ai/chat', name: 'app_ai_chat', methods: ['POST'])]
    public function chat(Request $request, HuggingFaceService $hfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $response = $hfService->getChatResponse($userMessage);

        return new JsonResponse([
            'response' => $response
        ]);
    }

    #[Route('/api/ai/copilot', name: 'app_ai_copilot', methods: ['POST'])]
    public function copilot(Request $request, HuggingFaceService $hfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';

        if ($action === 'generate_description') {
            $title = $data['title'] ?? '';
            $type = $data['typeArt'] ?? '';
            $theme = $data['theme'] ?? '';
            $response = $hfService->generateMagicDescription($title, $type, $theme);
            return new JsonResponse(['response' => $response]);
        }

        if ($action === 'suggest_pricing') {
            $type = $data['typeArt'] ?? '';
            $theme = $data['theme'] ?? '';
            $config = $hfService->suggestMagicPricing($type, $theme);
            return new JsonResponse($config);
        }

        return new JsonResponse(['error' => 'Action non reconnue'], 400);
    }
}
