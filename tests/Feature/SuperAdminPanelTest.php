<?php

namespace Tests\Feature;

use App\Models\LandingPageContent;
use App\Models\User;
use App\Support\LandingPageDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_user_becomes_super_admin_and_later_users_default_to_customer(): void
    {
        $superAdmin = User::factory()->create();
        $customer = User::factory()->create();

        $this->assertTrue($superAdmin->fresh()->hasRole(User::ROLE_SUPER_ADMIN));
        $this->assertFalse($superAdmin->fresh()->hasRole(User::ROLE_CUSTOMER));
        $this->assertTrue($customer->fresh()->hasRole(User::ROLE_CUSTOMER));
    }

    public function test_only_super_admins_can_access_the_super_admin_panel(): void
    {
        $superAdmin = User::factory()->create();
        $customer = User::factory()->create();

        $this->actingAs($superAdmin)
            ->get(route('filament.superadmin.pages.dashboard'))
            ->assertOk()
            ->assertSee('Landing Page');

        $this->actingAs($customer)
            ->get(route('filament.superadmin.pages.dashboard'))
            ->assertForbidden();
    }

    public function test_the_homepage_uses_cms_content_when_available(): void
    {
        LandingPageContent::query()->updateOrCreate(
            ['slug' => 'home'],
            array_replace(LandingPageDefaults::content(), [
                'hero' => [
                    'kicker' => 'CMS managed hero',
                    'title' => 'This landing page now comes from the CMS',
                    'description' => 'The super admin panel controls what the public website says.',
                ],
            ]),
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('CMS managed hero')
            ->assertSee('This landing page now comes from the CMS')
            ->assertSee('The super admin panel controls what the public website says.');
    }
}
