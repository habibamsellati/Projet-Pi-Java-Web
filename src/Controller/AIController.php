<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Service\NLPCategoryDetector;
use App\Service\PropositionAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ai')]
class AIController extends AbstractController
{
    #[Route('/generate-image', name: 'app_ai_generate_image', methods: ['POST'])]
    public function generateImage(Request $request, \App\Service\AIService $aiService): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $description = $data['description'] ?? '';

        if (empty($description)) {
            return new JsonResponse(['error' => 'La description est vide.'], 400);
        }

        try {
            $aiData = $aiService->generateAndSaveImage($description);

            return new JsonResponse([
                'image_url'    => $aiData['local'],
                'preview_url'  => $aiData['preview'] ?? $aiData['external'],
                'preview_data' => $aiData['preview_data'] ?? null,
                'external_url' => $aiData['external'] ?? null,
                'status'       => 'success',
                'is_fallback'  => $aiData['is_fallback'] ?? false,
                'provider'     => $aiData['provider'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/detect-category', name: 'app_ai_detect_category', methods: ['POST'])]
    public function detectCategory(Request $request, NLPCategoryDetector $nlpDetector): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty(trim($text))) {
            return new JsonResponse([
                'status' => 'error',
                'error'  => 'Le texte est vide.',
            ], 400);
        }

        try {
            $result = $nlpDetector->analyze($text);

            return new JsonResponse([
                'status'     => 'success',
                'category'   => $result['category'],
                'type'       => $result['type'],
                'state'      => $result['state'],
                'objects'    => $result['detected_objects'],
                'quantity'   => $result['quantity'],
                'origin'     => $result['origin'],
                'impact'     => $result['impact'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/estimate-price', name: 'app_ai_estimate_price', methods: ['POST'])]
    public function estimatePrice(Request $request, PropositionAIService $aiService): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $category = $data['category'] ?? '';
        $state    = $data['state'] ?? 'Moyen';
        $type     = $data['type'] ?? 'Réutilisable';
        $quantity = (int) ($data['quantity'] ?? 1);

        if (empty($category)) {
            return new JsonResponse(['status' => 'error', 'error' => 'La catégorie est requise.'], 400);
        }

        try {
            $estimation = $aiService->estimatePriceFromParams($category, $state, $type, $quantity);

            return new JsonResponse([
                'status'     => 'success',
                'estimation' => $estimation,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/recommend-artisans', name: 'app_ai_recommend_artisans', methods: ['POST'])]
    public function recommendArtisans(Request $request, PropositionAIService $aiService): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $category = $data['category'] ?? '';
        $limit    = (int) ($data['limit'] ?? 5);

        if (empty($category)) {
            return new JsonResponse(['status' => 'error', 'error' => 'La catégorie est requise.'], 400);
        }

        try {
            $artisans = $aiService->recommendArtisansFromParams($category, $limit);

            return new JsonResponse([
                'status'   => 'success',
                'artisans' => $artisans,
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }
}
