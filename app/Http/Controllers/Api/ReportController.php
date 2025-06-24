<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function charts(Request $request)
    {
        $user = $request->user();
        
        // Monthly trend data
        $monthlyTrend = Transaction::forUser($user->id)
            ->selectRaw('
                DATE_FORMAT(transaction_date, "%Y-%m") as month,
                type,
                SUM(amount) as total
            ')
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        // Category breakdown
        $categoryBreakdown = Transaction::forUser($user->id)
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'total' => $item->total,
                    'color' => $item->category->color,
                ];
            });

        return response()->json([
            'monthly_trend' => $monthlyTrend,
            'category_breakdown' => $categoryBreakdown,
        ]);
    }
}
