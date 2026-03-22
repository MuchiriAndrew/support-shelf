<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SupportDocumentController extends Controller
{
    /**
     * Import an uploaded support document into the ingestion pipeline.
     */
    public function store(Request $request, DocumentImportService $documentImportService): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,txt,md,markdown,html,htm', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:80'],
            'source_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $documentImportService->importUploadedFile($request->file('file'), [
                'title' => $validated['title'] ?? null,
                'document_type' => $validated['document_type'] ?? null,
                'source_name' => $validated['source_name'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return back()->with('ingestion_error', "Import failed: {$exception->getMessage()}");
        }

        $document = $result['document'];

        return back()->with(
            'ingestion_success',
            "Imported {$document->title} with {$result['chunks_count']} chunks."
        );
    }
}
