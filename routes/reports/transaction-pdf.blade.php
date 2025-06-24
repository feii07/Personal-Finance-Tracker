<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            text-align: center;
            flex: 1;
        }
        .summary-item h3 {
            margin: 0;
            color: #333;
            font-size: 14px;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: bold;
        }
        .income { color: #28a745; }
        .expense { color: #dc3545; }
        .balance { color: #007bff; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .amount {
            text-align: right;
        }
        .income-row {
            background-color: #f8fff8;
        }
        .expense-row {
            background-color: #fff8f8;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Report</h1>
        <p><strong>{{ $user->name }}</strong></p>
        <p>Period: {{ date('d M Y', strtotime($period['start_date'])) }} - {{ date('d M Y', strtotime($period['end_date'])) }}</p>
        <p>Generated on: {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h3>Total Income</h3>
            <p class="income">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</p>
        </div>
        <div class="summary-item">
            <h3>Total Expense</h3>
            <p class="expense">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</p>
        </div>
        <div class="summary-item">
            <h3>Balance</h3>
            <p class="balance">Rp {{ number_format($summary['balance'], 0, ',', '.') }}</p>
        </div>
        <div class="summary-item">
            <h3>Transactions</h3>
            <p>{{ $summary['transaction_count'] }}</p>
        </div>
    </div>

    @if($transactions->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr class="{{ $transaction->type }}-row">
                <td>{{ $transaction->transaction_date->format('d/m/y') }} </td>
                <td>{{ $transaction->type }} </td>
                <td>{{ $transaction->category->name }} </td>
                <td>{{ $transaction->description }} </td>