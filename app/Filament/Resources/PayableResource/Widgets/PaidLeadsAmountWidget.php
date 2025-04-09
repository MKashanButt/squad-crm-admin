<?php

namespace App\Filament\Widgets;

use App\Models\Leads;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaidLeadsAmountWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $paidLeadsCount = Leads::where('status', 'paid')->count();
        $totalAmount = $paidLeadsCount * 1000; // Multiply by 1000 PKR

        return [
            Stat::make('Total Paid Leads', $paidLeadsCount)
                ->description('Number of successfully paid leads')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Amount (PKR)', number_format($totalAmount) . ' PKR')
                ->description('Calculated at 1000 PKR per lead')
                ->descriptionIcon('heroicon-o-currency-rupee')
                ->color('primary')
                ->chart([7, 3, 5, 10, 15, 4, 17]) // Optional: Add trend data
                ->extraAttributes([
                    'class' => 'font-bold text-lg', // Styling
                ]),
        ];
    }
}
