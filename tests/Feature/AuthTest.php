<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_dapat_diakses(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akun Anda');
    }

    public function test_pengguna_aktif_dapat_masuk_dan_riwayat_dicatat(): void
    {
        $user = User::factory()->create([
            'username' => 'pengguna.aktif',
            'password' => Hash::make('Rahasia#123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'pengguna.aktif',
            'password' => 'Rahasia#123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'success' => true,
        ]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_kata_sandi_salah_ditolak_dan_dicatat(): void
    {
        User::factory()->create([
            'username' => 'pengguna.gagal',
            'password' => Hash::make('Benar#123'),
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'pengguna.gagal',
            'password' => 'Salah#123',
        ])->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('login_histories', [
            'username_attempt' => 'pengguna.gagal',
            'success' => false,
            'failure_reason' => 'invalid_credentials',
        ]);
    }

    public function test_akun_nonaktif_tidak_dapat_masuk(): void
    {
        User::factory()->create([
            'username' => 'pengguna.nonaktif',
            'password' => Hash::make('Rahasia#123'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'pengguna.nonaktif',
            'password' => 'Rahasia#123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseHas('login_histories', [
            'username_attempt' => 'pengguna.nonaktif',
            'success' => false,
            'failure_reason' => 'inactive',
        ]);
    }

    public function test_pengguna_wajib_mengganti_sandi_diarahkan_ke_halaman_ubah_sandi(): void
    {
        $user = User::factory()->create([
            'username' => 'wajib.ganti',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('password.change'));
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
