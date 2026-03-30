<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Source;
use App\Services\Embeddings\OpenAiEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SystemStatusController extends Controller
{
    public function __invoke(Request $request, OpenAiEmbeddingService $embeddingService): JsonResponse
    {
        $tablesReady = Schema::hasTable('sources')
            && Schema::hasTable('documents')
            && Schema::hasTable('document_chunks');
        $user = $request->user();

        $indexedChunks = $tablesReady
            ? DocumentChunk::query()
                ->whereNotNull('vector_id')
                ->whereHas('document', fn ($query) => $query->where('user_id', $user->id))
                ->count()
            : 0;

        return response()->json([
            'name' => config('assistant.brand.name'),
            'phase' => 'semantic_retrieval',
            'stack' => [
                'broadcasting' => config('broadcasting.default'),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'vector_store' => config('vector-store.default'),
            ],
            'services' => [
                'openai_configured' => filled(config('openai.api_key')),
                'embedding_model' => $embeddingService->model(),
                'weaviate_url' => config('vector-store.stores.weaviate.url'),
                'weaviate_collection' => config('vector-store.stores.weaviate.collection'),
                'reverb_host' => config('reverb.apps.apps.0.options.host'),
                'reverb_port' => config('reverb.apps.apps.0.options.port'),
            ],
            'ingestion' => [
                'tables_ready' => $tablesReady,
                'sources' => $tablesReady ? Source::query()->ownedBy($user)->count() : 0,
                'crawlable_sources' => $tablesReady ? Source::query()->ownedBy($user)->crawlable()->count() : 0,
                'documents' => $tablesReady ? Document::query()->ownedBy($user)->count() : 0,
                'chunks' => $tablesReady ? DocumentChunk::query()->whereHas('document', fn ($query) => $query->where('user_id', $user->id))->count() : 0,
                'indexed_chunks' => $indexedChunks,
                'pending_chunks' => $tablesReady
                    ? max(0, DocumentChunk::query()->whereHas('document', fn ($query) => $query->where('user_id', $user->id))->count() - $indexedChunks)
                    : 0,
            ],
            'routes' => [
                'home' => route('home'),
                'chat' => route('chat'),
                'ingestion' => route('filament.admin.pages.knowledge-ingestion'),
                'assistant_settings' => route('filament.admin.pages.assistant-settings'),
                'super_admin' => $user->isSuperAdmin() ? route('filament.superadmin.pages.dashboard') : null,
                'search' => route('knowledge.search'),
            ],
        ]);
    }
}
