<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk()->assertSee('Daftar');
    }

    public function test_landing_page_links_to_user_registration(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Daftar sebagai user')
            ->assertSee(route('register'));
    }

    public function test_new_users_register_as_pending_and_are_not_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Pendaftaran berhasil. Akun Anda sedang menunggu persetujuan admin atau superadmin.');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
            'is_approved' => false,
        ]);
    }
}
