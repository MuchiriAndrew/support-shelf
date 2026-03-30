<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('hero')->nullable();
            $table->json('metrics')->nullable();
            $table->json('pillars')->nullable();
            $table->json('workflow')->nullable();
            $table->json('showcases')->nullable();
            $table->json('proof_points')->nullable();
            $table->json('cta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_contents');
    }
};
