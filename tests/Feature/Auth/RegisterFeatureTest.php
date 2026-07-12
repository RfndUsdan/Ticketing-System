<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class RegisterFeatureTest extends TestCase
{
    use RefreshDatabase; 

    public function test_user_berhasil_register_dengan_data_valid()
    {
        $payload = [
            'name' => 'Mahasiswa Baru',
            'email' => 'mahasiswa@unipma.ac.id',
            'password' => 'rahasia123',
            'nim' => '20260001'
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'user'
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa@unipma.ac.id',
            'nim' => '20260001'
        ]);
    }

    public function test_register_gagal_jika_nim_atau_email_sudah_terdaftar()
    {
        User::factory()->create([
            'email' => 'lama@unipma.ac.id',
            'nim' => '20250001'
        ]);

        $payload = [
            'name' => 'Kloning User',
            'email' => 'lama@unipma.ac.id',
            'password' => 'rahasia123',
            'nim' => '20250001'
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'nim']);
    }

    public function test_register_berhasil_dengan_upload_avatar()
    {
        Storage::fake('public');

        $payload = [
            'name' => 'User Berfoto',
            'email' => 'foto@unipma.ac.id',
            'password' => 'rahasia123',
            'nim' => '20260002',
            'avatar' => UploadedFile::fake()->image('profil.jpg', 200, 200) // Foto palsu
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);

        $user = User::where('email', 'foto@unipma.ac.id')->first();
        Storage::disk('public')->assertExists($user->avatar);
    }
}