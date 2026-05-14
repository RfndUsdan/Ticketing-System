<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase; 

    public function test_unauthenticated_user_cannot_create_ticket()
    {
        $response = $this->postJson('/api/ticket', [
            'title' => 'Test Tiket',
            'description' => 'Deskripsi tiket',
            'priority' => 'high',
        ]);

        $response->assertStatus(401);
    }

    public function test_validation_fails_when_required_fields_are_missing()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/ticket', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title', 'description', 'priority']);
    }

    public function test_validation_fails_when_priority_is_invalid()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/ticket', [
                             'title' => 'Judul Error',
                             'description' => 'Deskripsi Error',
                             'priority' => 'urgent', 
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['priority']);
    }

    public function test_user_can_create_ticket_successfully_without_image()
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Aplikasi Crash',
            'description' => 'Aplikasi tertutup sendiri saat membuka menu laporan.',
            'priority' => 'high',
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/ticket', $payload);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Ticket berhasil ditambahkan',
                 ]);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'title' => 'Aplikasi Crash',
            'priority' => 'high',
            'status' => 'open', 
        ]);
    }

    public function test_user_can_create_ticket_successfully_with_image()
    {
        Storage::fake('public'); 

        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->image('bukti_error.jpg');

        $payload = [
            'title' => 'Tampilan Rusak',
            'description' => 'CSS tidak termuat dengan benar.',
            'priority' => 'medium',
            'image' => $file,
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/ticket', $payload);

        $response->assertStatus(201);

        $ticket = Ticket::first();

        $this->assertNotNull($ticket->image);

        Storage::disk('public')->assertExists($ticket->image);
    }
}