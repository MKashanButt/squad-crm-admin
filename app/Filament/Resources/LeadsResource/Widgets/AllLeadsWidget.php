<?php

namespace App\Filament\Resources\LeadsResource\Widgets;

use App\Models\Leads;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AllLeadsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('hr')) {
            return $this->getAdminStats();
        }

        if ($user->hasRole('manager')) {
            return $this->getManagerStats($user);
        }

        return $this->getAgentStats($user);
    }

    protected function getAdminStats(): array
    {
        $paidLeadsCount = Leads::where('status', 'paid')->count();
        $totalAmount = $paidLeadsCount * 1000;

        return [
            Stat::make('Total Leads', Leads::count())
                ->description('All leads in the system')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Paid Leads', $paidLeadsCount)
                ->description('Successfully paid')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Total Amount', number_format($totalAmount) . ' PKR')
                ->description('Value of paid leads (1000 PKR each)')
                ->descriptionIcon('heroicon-o-currency-rupee')
                ->color('primary')
                ->chart($this->getAmountTrendData()),
        ];
    }

    protected function getManagerStats(User $manager): array
    {
        $teamName = strtolower($manager->name);

        $teamLeadsCount = Leads::whereHas('user', function ($query) use ($teamName) {
            $query->where('team', $teamName);
        })
            ->count();

        $billedLeadsCount = Leads::whereHas('user', function ($query) use ($teamName) {
            $query->where('team', $teamName);
        })
            ->where('status', 'billed')
            ->count();

        $totalCommission = $billedLeadsCount * 500;

        return [
            Stat::make('Team Leads', $teamLeadsCount)
                ->description('Total leads in your team')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary')
                ->chart($this->getTeamLeadsChartData($teamName)),

            Stat::make('Billed Leads', $billedLeadsCount)
                ->description('Billed leads in your team')
                ->descriptionIcon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('Your Commission', number_format($totalCommission) . ' PKR')
                ->description('500 PKR per billed lead')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Billing Rate', $this->getBillingRate($teamName))
                ->description('Team lead to billed conversion')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),
        ];
    }

    protected function getAgentStats(User $agent): array
    {
        $paidLeadsCount = Leads::where('user_id', $agent->id)
            ->where('status', 'billable')
            ->count();

        $totalAmount = $paidLeadsCount * 1000;

        return [
            Stat::make('Your Leads', Leads::where('user_id', $agent->id)->count())
                ->description('All leads assigned to you')
                ->descriptionIcon('heroicon-o-user-circle')
                ->color('primary')
                ->chart($this->getAgentLeadsChartData($agent)),

            Stat::make('Paid Leads', $paidLeadsCount)
                ->description('Your successfully paid leads')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Your Earnings', number_format($totalAmount) . ' PKR')
                ->description('Your commission (1000 PKR per lead)')
                ->descriptionIcon('heroicon-o-currency-rupee')
                ->color('primary'),

            Stat::make('Conversion Rate', $this->getConversionRate($agent))
                ->description('Your lead to paid conversion')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),
        ];
    }

    // New method for manager team leads chart
    protected function getTeamLeadsChartData(string $teamName): array
    {
        return Leads::whereHas('user', function ($query) use ($teamName) {
            $query->where('team', $teamName);
        })
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    // New method for manager billing rate
    protected function getBillingRate(string $teamName): string
    {
        $total = Leads::whereHas('user', function ($query) use ($teamName) {
            $query->where('team', $teamName);
        })->count();

        $billed = Leads::whereHas('user', function ($query) use ($teamName) {
            $query->where('team', $teamName);
        })
            ->where('status', 'billed')
            ->count();

        return $total > 0
            ? number_format(($billed / $total) * 100, 2) . '%'
            : '0%';
    }

    // New method for admin amount trend data
    protected function getAmountTrendData(): array
    {
        return Leads::where('status', 'paid')
            ->selectRaw('DATE(created_at) as date, COUNT(*)*1000 as amount')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('amount')
            ->toArray();
    }

    protected function getAgentLeadsChartData(User $agent): array
    {
        return Leads::where('user_id', $agent->id)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getConversionRate(User $agent): string
    {
        $total = Leads::where('user_id', $agent->id)->count();
        $paid = Leads::where('user_id', $agent->id)->where('status', 'billable')->count();

        return $total > 0
            ? number_format(($paid / $total) * 100, 2) . '%'
            : '0%';
    }
}
