<?php

namespace App\Filament\Widgets;

use App\Models\Family;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * COUNT/metadata only (zero-knowledge) - tidak ada SUM nominal uang.
 */
class OperationsStatsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

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
            Stat::make('Transaksi Hari Ini', $transactionsToday)
                ->icon('heroicon-o-arrows-right-left')
                ->color('info'),
            Stat::make('User Aktif (30 hari)', $activeUsers30d)
                ->description('Punya minimal 1 transaksi')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Wallet Aktif', $activeWallets)
                ->icon('heroicon-o-wallet')
                ->color('success'),
            Stat::make('Total Family', $totalFamilies)
                ->icon('heroicon-o-user-group')
                ->color('gray'),
        ];
    }
}
