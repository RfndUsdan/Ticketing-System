<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase; 

    public function test_user_unauthenticated_gagal_mengakses_ticket()
    {
        $response = $this->getJson('/api/ticket');

        $response->assertStatus(401);
    }

    public function test_user_berhasil_membuat_ticket_tanpa_gambar()
    {
        $user = User::factory()->create(['nim' => '20260001']);

        $payload = [
            'title' => 'AC Kelas Rusak',
            'description' => 'AC di ruang 302 mati total, kelas panas.',
            'priority' => 'normal',
            'category' => 'Fasilitas'
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/ticket', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id',
                         'code',
                         'title',
                         'status'
                     ]
                 ]);

        $this->assertDatabaseHas('tickets', [
            'title' => 'AC Kelas Rusak',
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_user_berhasil_membuat_ticket_dengan_upload_gambar()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $payload = [
            'title' => 'Kabel Proyektor Putus',
            'description' => 'Kabel HDMI terputus di ujung proyektor.',
            'priority' => 'high',
            'category' => 'Fasilitas',
            'image' => UploadedFile::fake()->image('bukti_kerusakan.jpg', 600, 600)
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/ticket', $payload);

        $response->assertStatus(201);

        $ticket = Ticket::where('title', 'Kabel Proyektor Putus')->first();
        $this->assertTrue(Storage::disk('public')->exists($ticket->image));
    }

    public function test_membuat_ticket_gagal_jika_kategori_dan_prioritas_tidak_valid()
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'Laporan Salah',
            'description' => 'Deskripsi laporan...',
            'priority' => 'super_urgent', 
            'category' => 'Kategori_Ngawur' 
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/ticket', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['priority', 'category']);
    }

    public function test_user_hanya_bisa_melihat_ticket_miliknya_sendiri_di_daftar()
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        Ticket::create([
            'user_id' => $userA->id,
            'code' => 'TICS11111',
            'title' => 'Tiket Milik User A',
            'description' => 'Deskripsi A',
            'priority' => 'low',
            'category' => 'Fasilitas',
            'status' => 'open'
        ]);

        Ticket::create([
            'user_id' => $userB->id,
            'code' => 'TICS22222',
            'title' => 'Tiket Milik User B',
            'description' => 'Deskripsi B',
            'priority' => 'low',
            'category' => 'Fasilitas',
            'status' => 'open'
        ]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/ticket');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Tiket Milik User A', $response->json('data.0.title'));
    }

    public function test_user_gagal_melihat_detail_ticket_milik_orang_lain()
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $ticketB = Ticket::create([
            'user_id' => $userB->id,
            'code' => 'TICS99999',
            'title' => 'Rahasia User B',
            'description' => 'Deskripsi...',
            'priority' => 'normal',
            'category' => 'Lainnya',
            'status' => 'open'
        ]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/ticket/' . $ticketB->code);

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Anda tidak memiliki akses ke ticket ini'
                 ]);
    }
}