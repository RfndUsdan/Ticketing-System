<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_berhasil_login_dengan_credentials_benar()
    {
        User::factory()->create([
            'email' => 'rifandi@unipma.ac.id',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rifandi@unipma.ac.id',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'user' => ['id', 'name', 'email'] 
                     ]
                 ]);
    }

    public function test_login_gagal_jika_password_salah()
    {
        User::factory()->create([
            'email' => 'rifandi@unipma.ac.id',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rifandi@unipma.ac.id',
            'password' => 'salahpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthorized',
                     'data' => null
                 ]);
    }

    public function test_user_berhasil_logout_dan_token_dihapus()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logout berhasil'
                 ]);
    }
}