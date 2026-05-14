<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyStoreRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;
use Exception;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $query = Ticket::with(['replies.user', 'user']);

            $query->orderBy('created_at', 'desc');

            if ($request->search) {
                $query->where('code', 'like', '%' . $request->search . '%')
                      ->orWhere('title', 'like', '%' . $request->search . '%');
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($user->role == 'user') {
                $query->where('user_id', $user->id);
            }

            $tickets = $query->get();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Daftar Ticket berhasil ditampilkan',
            'data' => TicketResource::collection($tickets)
        ], 200);
    }

    public function show($code)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $ticket = Ticket::with(['replies.user', 'user'])
                        ->where('code', $code)
                        ->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket tidak ditemukan',
                ], 404);
            }

            // Gunakan $user agar lebih bersih
            if ($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke ticket ini',
                ], 403);
            }

            return response()->json([
                'message' => 'Ticket berhasil ditampilkan',
                'data' => new TicketResource($ticket)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data' => null
            ], 500);
        }
    }

    public function store(TicketStoreRequest $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = new Ticket;
            $ticket->user_id = $user->id; // Menggunakan variabel
            $ticket->code = 'TICS' . rand(10000, 99999);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->status = 'open';
            
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('tickets', 'public');
                $ticket->image = $path;
            }

            $ticket->save();

            DB::commit();

            $ticket->load('user');

            return response()->json([
                'message' => 'Ticket berhasil ditambahkan',
                'data' => new TicketResource($ticket)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data' => null
            ], 500);
        }
    }

    public function storeReply(TicketReplyStoreRequest $request, $code)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                return response()->json([
                    'message'=>'Tiket tidak ditemukan',
                ], 404);
            }

            // Ganti di sini
            if ($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json([
                    'message'=>'Anda tidak bisa membalas tiket ini',
                ], 403);
            }

            $ticketReply = new TicketReply();
            $ticketReply->ticket_id = $ticket->id;
            $ticketReply->user_id = $user->id; // Dan di sini
            $ticketReply->content = $data['content'];
            $ticketReply->save();

            // Serta di sini
            if ($user->role == 'admin') {
                $ticket->status = $data['status'];
                if ($data['status'] == 'resolved') {
                    $ticket->completed_at = now();
                }
                $ticket->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Balasan tiket berhasil ditambahkan',
                'data' => new TicketReplyResource($ticketReply)
            ], 201);
            
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($code)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $ticket = Ticket::where('code', $code)->first();

            if (!$ticket) {
                return response()->json(['message' => 'Ticket tidak ditemukan'], 404);
            }

            // Ganti di sini
            if ($user->role == 'user' && $ticket->user_id != $user->id) {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $ticket->delete();

            return response()->json(['message' => 'Ticket berhasil dihapus'], 200);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Gagal menghapus ticket'], 500);
        }
    }
}