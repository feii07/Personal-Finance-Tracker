<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $userId;
    protected $startDate;
    protected $endDate;
    protected $type;
    protected $categoryId;

    public function __construct($userId, $startDate, $endDate, $type = null, $categoryId = null)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->categoryId = $categoryId;
    }

    public function collection()
    {
        $query = Transaction::forUser($this->userId)
            ->inDateRange($this->startDate, $this->endDate)
            ->with(['category'])
            ->orderBy('transaction_date', 'desc');

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->categoryId) {
            $query->inCategory($this->categoryId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Category',
            'Description',
            'Amount',
            'Formatted Amount'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date->format('Y-m-d'),
            ucfirst($transaction->type),
            $transaction->category->name ?? 'Uncategorized',
            $transaction->description,
            $transaction->amount,
            $transaction->formatted_amount
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Transactions Report';
    }
}