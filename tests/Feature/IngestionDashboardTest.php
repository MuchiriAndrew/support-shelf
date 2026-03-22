<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ingestion_dashboard_renders_successfully(): void
    {
        $response = $this->get(route('admin.ingestion'));

        $response
            ->assertOk()
            ->assertSee('Ingestion dashboard')
            ->assertSee('Register a support site')
            ->assertSee('Upload a manual or policy file');
    }
}
