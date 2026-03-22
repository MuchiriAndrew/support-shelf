<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_a_text_support_document(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent(
            'returns-policy.txt',
            "Returns Policy\n\nCustomers can return unopened accessories within 30 days of delivery. Opened accessories are eligible only if they are defective."
        );

        $response = $this->post(route('admin.ingestion.documents.store'), [
            'file' => $file,
            'title' => 'Returns Policy',
            'document_type' => 'return_policy',
            'source_name' => 'Store policies',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('ingestion_success');

        $document = Document::query()->first();

        $this->assertNotNull($document);
        $this->assertSame('Returns Policy', $document->title);
        $this->assertSame('return_policy', $document->document_type);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertTrue(Storage::disk('local')->exists($document->storage_path));
    }
}
