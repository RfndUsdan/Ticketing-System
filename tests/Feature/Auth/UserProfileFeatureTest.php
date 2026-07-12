<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class UserProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_berhasil_update_profil_tanpa_ganti_foto()
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@unipma.ac.id',
            'nim' => '20260001'
        ]);

        $payload = [
            'name' => 'Nama Baru Update',
            'email' => 'baru@unipma.ac.id',
            'nim' => '20260002'
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/profile', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Profil berhasil diperbarui'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Update',
            'email' => 'baru@unipma.ac.id',
            'nim' => '20260002'
        ]);
    }

    public function test_user_berhasil_update_profil_sekaligus_upload_avatar_baru()
    {
        Storage::fake('public');
        
        $user = User::factory()->create([
            'nim' => '20260099' 
        ]);

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'nim' => '20260099', 
            'avatar' => UploadedFile::fake()->image('foto_profil_baru.png', 300, 300)
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/profile', $payload);

        $response->assertStatus(200);

        $user->refresh(); 
        
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar));
    }

    public function test_user_berhasil_menghapus_foto_avatar()
    {
        Storage::fake('public');
        
        $filePalsu = UploadedFile::fake()->image('avatar_lama.jpg');
        $path = $filePalsu->store('avatars', 'public');

        $user = User::factory()->create([
            'avatar' => $path
        ]);

        $this->assertTrue(Storage::disk('public')->exists($path));

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/profile/avatar');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Foto profil berhasil dihapus'
                 ]);

        $user->refresh();
        $this->assertNull($user->avatar);
        
        $this->assertFalse(Storage::disk('public')->exists($path)); 
    }
}