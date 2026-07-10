<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

/**
 * Distribusi COUNT transaksi per tipe (zero-knowledge) - bukan jumlah uang.
 */
class TransactionTypeDistributionChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->can('View:TransactionTypeDistributionChart');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('staffsus/dashboard.transaction_type_distribution.heading');
    }

    protected function getData(): array
    {
        // Query builder langsung (bukan model Transaction) supaya tidak bentrok
        // dengan $defaultSelect model saat join + groupBy.
        $rows = DB::table('transactions')
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type')
            ->where('transactions.created_at', '>=', now()->subDays(30))
            ->selectRaw('transaction_types.name as name, count(*) as total')
            ->groupBy('transaction_types.name')
            ->pluck('total', 'name');

        return [
            'datasets' => [
                [
                    'label' => __('staffsus/dashboard.transaction_type_distribution.series'),
                    'data' => $rows->values()->all(),
                    'backgroundColor' => ['#3b82f6', '#ef4444', '#f59e0b', '#10b981'],
                ],
            ],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
