<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_type')->default('website');
            $table->string('domain')->nullable()->index();
            $table->string('url')->nullable()->unique();
            $table->string('content_selector')->nullable();
            $table->boolean('crawl_enabled')->default(true);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('triggered_by')->default('manual');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('pages_discovered')->default(0);
            $table->unsignedInteger('pages_processed')->default(0);
            $table->unsignedInteger('documents_upserted')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('document_type')->default('web_page');
            $table->string('language', 8)->default('en');
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->longText('content_text');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->string('status')->default('ready');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_id', 'canonical_url']);
            $table->index(['storage_disk', 'storage_path']);
            $table->index(['source_id', 'checksum']);
        });

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->string('vector_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('crawl_runs');
        Schema::dropIfExists('sources');
    }
};
