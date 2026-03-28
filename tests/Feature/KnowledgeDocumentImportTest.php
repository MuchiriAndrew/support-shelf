<?php

namespace Tests\Feature;

use App\Jobs\SyncDocumentVectorsJob;
use App\Livewire\Admin\IngestionWorkspace;
use App\Models\Document;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_a_text_document_from_the_filament_workspace(): void
    {
        Bus::fake([SyncDocumentVectorsJob::class]);
        Storage::fake('local');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->createWithContent(
            'returns-policy.txt',
            "Returns Policy\n\nCustomers can return unopened accessories within 30 days of delivery. Opened accessories are eligible only if they are defective."
        );

        Livewire::test(IngestionWorkspace::class)
            ->set('documentData.title', 'Returns Policy')
            ->set('documentData.file', $file)
            ->call('importDocument');

        $document = Document::query()->first();

        $this->assertNotNull($document);
        $this->assertSame($user->id, $document->user_id);
        $this->assertSame('Returns Policy', $document->title);
        $this->assertSame('text_document', $document->document_type);
        $this->assertGreaterThan(0, $document->chunks()->count());
        $this->assertTrue(Storage::disk('local')->exists($document->storage_path));
        $this->assertNull($document->source);
    }

    public function test_the_uploaded_scope_only_returns_user_uploaded_documents(): void
    {
        $user = User::factory()->create();

        $websiteSource = Source::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Docs',
            'source_type' => 'website',
            'url' => 'https://example.com/docs',
            'domain' => 'example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $uploadedCollection = Source::query()->create([
            'user_id' => $user->id,
            'name' => 'Private Files',
            'source_type' => 'uploaded_collection',
            'crawl_enabled' => false,
            'status' => 'active',
        ]);

        $directUpload = Document::query()->create([
            'user_id' => $user->id,
            'title' => 'Direct Upload',
            'document_type' => 'text_document',
            'content_text' => 'Direct upload content.',
            'checksum' => sha1('direct-upload'),
            'token_estimate' => 4,
            'status' => 'ready',
        ]);

        $groupedUpload = Document::query()->create([
            'user_id' => $user->id,
            'source_id' => $uploadedCollection->id,
            'title' => 'Grouped Upload',
            'document_type' => 'markdown_document',
            'content_text' => 'Grouped upload content.',
            'checksum' => sha1('grouped-upload'),
            'token_estimate' => 4,
            'status' => 'ready',
        ]);

        Document::query()->create([
            'user_id' => $user->id,
            'source_id' => $websiteSource->id,
            'title' => 'Crawled Page',
            'document_type' => 'web_page',
            'canonical_url' => 'https://example.com/docs/page',
            'content_text' => 'Crawled page content.',
            'checksum' => sha1('crawled-page'),
            'token_estimate' => 4,
            'status' => 'ready',
        ]);

        $documentIds = Document::query()
            ->ownedBy($user)
            ->uploaded()
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$directUpload->id, $groupedUpload->id], $documentIds);
    }
}
