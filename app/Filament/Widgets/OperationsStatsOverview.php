<?php

namespace App\Filament\Widgets;

use App\Models\Family;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * COUNT/metadata only (zero-knowledge) - tidak ada SUM nominal uang.
 */
class OperationsStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->can('View:OperationsStatsOverview');
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $transactionsToday = Transaction::whereDate('created_at', $today)->count();
        $activeUsers30d = DB::table('transactions')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->distinct()
            ->count('users');
        $activeWallets = Wallet::where('status', 'active')->count();
        $totalFamilies = Family::count();

        return [
            Stat::make(__('staffsus/dashboard.operations_stats.transactions_today'), $transactionsToday)
                ->icon('heroicon-o-arrows-right-left')
                ->color('info'),
            Stat::make(__('staffsus/dashboard.operations_stats.active_users_30d'), $activeUsers30d)
                ->description(__('staffsus/dashboard.operations_stats.active_users_30d_description'))
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make(__('staffsus/dashboard.operations_stats.active_wallets'), $activeWallets)
                ->icon('heroicon-o-wallet')
                ->color('success'),
            Stat::make(__('staffsus/dashboard.operations_stats.total_families'), $totalFamilies)
                ->icon('heroicon-o-user-group')
                ->color('gray'),
        ];
    }
}
