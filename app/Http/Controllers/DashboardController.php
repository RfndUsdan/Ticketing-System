<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStatistic()
    {
        $startDate = Carbon::now()->subDays(30); 
        $endDate = Carbon::now();

        $totalTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'resolved')
            ->count();

        $resolvedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'resolved')
            ->count();

        $avgReslutionTime = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'resolved')
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time'))
            ->value('avg_time') ?? 0;

        $statusDistribution = [
            'open' => Ticket::whereBetween('created_at', [$startDate, $endDate])->where('status', 'open')->count(),
            'in_progress' => Ticket::whereBetween('created_at', [$startDate, $endDate])->where('status', 'in_progress')->count(),
            'resolved' => Ticket::whereBetween('created_at', [$startDate, $endDate])->where('status', 'resolved')->count(),
            'rejected' => Ticket::whereBetween('created_at', [$startDate, $endDate])->where('status', 'rejected')->count(),
        ];

        $dashboardData = [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTickets,
            'avg_resolution_time' => round($avgReslutionTime, 1),
            'status_distribution' => $statusDistribution,
        ];

        return response()->json([
            'message' => 'Dashboard statistic fetched successfully',
            'data' => new DashboardResource($dashboardData)
        ]);
    }
}
