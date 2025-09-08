<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserTransactionStatsWidget extends BaseWidget
{
    public ?Model $record = null;
    
    protected function getStats(): array
    {
        if (!$this->record instanceof User) {
            return [];
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get transaction count for current month
        $currentMonthCount = Transaction::where('users', $this->record->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Get previous month for comparison
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();
        
        $previousMonthCount = Transaction::where('users', $this->record->id)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        // Calculate difference
        $difference = $currentMonthCount - $previousMonthCount;
        $trend = null;
        if ($previousMonthCount > 0) {
            $percentChange = (($difference / $previousMonthCount) * 100);
            if ($percentChange > 0) {
                $trend = 'increase';
            } elseif ($percentChange < 0) {
                $trend = 'decrease';
            }
        }

        // Get total transaction count
        $totalCount = Transaction::where('users', $this->record->id)->count();

        return [
            Stat::make('Transactions This Month', $currentMonthCount)
                ->description($difference > 0 ? "+{$difference} from last month" : ($difference < 0 ? "{$difference} from last month" : 'Same as last month'))
                ->descriptionIcon($trend === 'increase' ? 'heroicon-m-arrow-trending-up' : ($trend === 'decrease' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($trend === 'increase' ? 'success' : ($trend === 'decrease' ? 'danger' : 'gray')),

            Stat::make('Total Transactions', $totalCount)
                ->description('All time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}