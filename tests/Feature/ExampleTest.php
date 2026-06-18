<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_root_to_login(): void
    {
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_the_login_page_loads_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_authenticated_admin_redirects_to_admin_panel(): void
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/login');
        $response->assertRedirect('/admin');

        $response = $this->actingAs($admin)->get('/');
        $response->assertRedirect('/admin');
    }

    public function test_authenticated_agent_redirects_to_agent_panel(): void
    {
        $agent = User::create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $response = $this->actingAs($agent)->get('/login');
        $response->assertRedirect('/agent');

        $response = $this->actingAs($agent)->get('/');
        $response->assertRedirect('/agent');
    }
}
