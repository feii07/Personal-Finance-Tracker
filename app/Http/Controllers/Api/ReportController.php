<?php

namespace App\Http\Controllers\Api;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        
        $totalIncome = Transaction::forUser($userId)
            ->income()
            ->sum('amount');
            
        $totalExpense = Transaction::forUser($userId)
            ->expense()
            ->sum('amount');

        $balance = $totalIncome - $totalExpense;

        // Monthly summary
        $monthlyData = Transaction::forUser($userId)
            ->selectRaw('
                YEAR(transaction_date) as year,
                MONTH(transaction_date) as month,
                type,
                SUM(amount) as total
            ')
            ->groupBy('year', 'month', 'type')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(['year', 'month']);

        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance,
            'monthly_data' => $monthlyData,
        ]);
    }

    public function exportPdf(Request $request)
    {
        // Check if user is premium
        // if (!auth()->user()->is_premium) {
        //     return response()->json([
        //         'message' => 'Premium subscription required to export reports'
        //     ], 403);
        // }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $query = Transaction::forUser(auth()->id())
            ->inDateRange($request->start_date, $request->end_date)
            ->with(['category'])
            ->orderBy('transaction_date', 'desc');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->category_id) {
            $query->inCategory($request->category_id);
        }

        $transactions = $query->get();
        
        // Calculate totals
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $data = [
            'transactions' => $transactions,
            'period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'transaction_count' => $transactions->count()
            ],
            'user' => auth()->user(),
            'generated_at' => now()->format('Y-m-d H:i:s')
        ];

        $pdf = Pdf::loadView('reports.transaction-pdf', $data);
        
        $filename = 'transactions-report-' . $request->start_date . '-to-' . $request->end_date . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        // Check if user is premium
        if (!auth()->user()->is_premium) {
            return response()->json([
                'message' => 'Premium subscription required to export reports'
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $filename = 'transactions-report-' . $request->start_date . '-to-' . $request->end_date . '.xlsx';

        return Excel::download(
            new TransactionsExport(
                auth()->id(),
                $request->start_date,
                $request->end_date,
                $request->type,
                $request->category_id
            ),
            $filename
        );
    }
}
