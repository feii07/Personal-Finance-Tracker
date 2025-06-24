<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaction::forUser($request->user()->id)
            ->with(['category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->inMonth($request->year, $request->month);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'data' => TransactionResource::collection($transactions->items()),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    public function store(StoreTransactionRequest $request)
    {
        try {
            $transaction = Transaction::create(array_merge(
                $request->validated(),
                ['user_id' => $request->user()->id]
            ));

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => new TransactionResource($transaction->load('category'))
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Transaction store failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create transaction',
                'error' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);
        
        return response()->json([
            'data' => new TransactionResource($transaction->load('category'))
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        
        $transaction->update($request->validated());

        return response()->json([
            'message' => 'Transaction updated successfully',
            'data' => new TransactionResource($transaction->load('category'))
        ]);
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        
        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully'
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
}
