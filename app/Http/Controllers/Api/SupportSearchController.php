<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Retrieval\SupportRetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SupportSearchController extends Controller
{
    public function __invoke(Request $request, SupportRetrievalService $retrievalService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        if (! $retrievalService->isConfigured()) {
            return response()->json([
                'message' => 'Semantic retrieval is not configured yet.',
            ], 503);
        }

        try {
            $results = $retrievalService->search(
                $validated['q'],
                isset($validated['limit']) ? (int) $validated['limit'] : null,
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'query' => $validated['q'],
            'count' => $results->count(),
            'matches' => $results,
        ]);
    }
}
