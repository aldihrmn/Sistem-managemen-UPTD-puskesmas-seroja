<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'is_active' => true], $extra));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/pegawai/dashboard')->assertRedirect('/login');
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $u = $this->user('admin');
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($u);
    }

    public function test_pegawai_login_redirects_to_pegawai_dashboard(): void
    {
        $u = $this->user('pegawai');
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/pegawai/dashboard');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $u = $this->user('admin');
        $this->from('/login')->post('/login', ['email' => $u->email, 'password' => 'salah'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $u = $this->user('pegawai', ['is_active' => false]);
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pegawai_cannot_access_admin_pages(): void
    {
        $this->actingAs($this->user('pegawai'))->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_cannot_access_pegawai_pages(): void
    {
        $this->actingAs($this->user('admin'))->get('/pegawai/dashboard')->assertForbidden();
    }

    public function test_logout_invalidates_session(): void
    {
        $this->actingAs($this->user('admin'))->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_root_redirects_by_role(): void
    {
        $this->actingAs($this->user('admin'))->get('/')->assertRedirect('/admin/dashboard');
    }
}
