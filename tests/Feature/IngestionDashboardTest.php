<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_filament_login_screen(): void
    {
        $response = $this->get(route('filament.admin.pages.knowledge-ingestion'));

        $response->assertRedirect('/admin/login');
    }

    public function test_the_filament_ingestion_page_renders_for_authenticated_users(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('filament.admin.pages.knowledge-ingestion'));

        $response
            ->assertOk()
            ->assertSee('Bring support content into the assistant')
            ->assertSee('Register a support site')
            ->assertSee('Import a support document');
    }
}
