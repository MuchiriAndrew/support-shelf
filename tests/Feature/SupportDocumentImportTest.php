<?php

namespace Tests\Feature;

use App\Jobs\SyncDocumentVectorsJob;
use App\Livewire\Admin\IngestionWorkspace;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_a_text_support_document(): void
    {
        Bus::fake([SyncDocumentVectorsJob::class]);
        Storage::fake('local');

        $this->actingAs(User::factory()->create());

        $file = UploadedFile::fake()->createWithContent(
            'returns-policy.txt',
            "Returns Policy\n\nCustomers can return unopened accessories within 30 days of delivery. Opened accessories are eligible only if they are defective."
        );

        Livewire::test(IngestionWorkspace::class)
            ->set('documentData.title', 'Returns Policy')
            ->set('documentData.document_type', 'return_policy')
            ->set('documentData.source_name', 'Store policies')
            ->set('documentData.file', $file)
            ->call('importDocument');

        $document = Document::query()->first();

        $this->assertNotNull($document);
        $this->assertSame('Returns Policy', $document->title);
        $this->assertSame('return_policy', $document->document_type);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertTrue(Storage::disk('local')->exists($document->storage_path));
    }
}
