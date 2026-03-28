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
            ->assertSee('Bring your private context into the assistant')
            ->assertSee('Back to website')
            ->assertSee('Register a website source')
            ->assertSee('Import a knowledge document');
    }

    public function test_the_filament_assistant_settings_page_renders_for_authenticated_users(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('filament.admin.pages.assistant-settings'));

        $response
            ->assertOk()
            ->assertSee('Shape how your assistant presents itself')
            ->assertSee('Save assistant settings');
    }
}
