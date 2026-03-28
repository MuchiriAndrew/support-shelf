<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('assistant_name')->nullable()->after('name');
            $table->text('assistant_instructions')->nullable()->after('assistant_name');
        });

        Schema::table('sources', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('sources_url_unique');
            $table->unique(['user_id', 'url']);
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('support_conversations', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('uuid')->constrained()->cascadeOnDelete();
        });

        if ($defaultUserId = DB::table('users')->orderBy('id')->value('id')) {
            DB::table('sources')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
            DB::table('documents')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
            DB::table('support_conversations')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        }
    }

    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('sources', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'url']);
            $table->unique('url');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['assistant_name', 'assistant_instructions']);
        });
    }
};
