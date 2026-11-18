<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the minimalist Finora SaaS Admin Dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $adminName = $user?->name
            ? Str::before($user->name, ' ')
            : 'David';

        $kpis = [
            [
                'key' => 'balance',
                'label' => 'Current Balance',
                'value' => '$5,847',
                'change' => '35%',
                'trend' => 'up',
                'icon' => 'wallet',
                'badge_bg' => 'bg-[#ECFDF5] border border-[#A7F3D0]',
                'badge_text' => 'text-[#14532D]',
            ],
            [
                'key' => 'savings',
                'label' => 'Savings',
                'value' => '$3,657',
                'change' => '21%',
                'trend' => 'up',
                'icon' => 'piggy',
                'badge_bg' => 'bg-emerald-50 border border-emerald-200',
                'badge_text' => 'text-[#059669]',
            ],
            [
                'key' => 'income',
                'label' => 'Income',
                'value' => '$12,645',
                'change' => '12%',
                'trend' => 'up',
                'icon' => 'bag',
                'badge_bg' => 'bg-[#FFF7ED] border border-[#FED7AA]',
                'badge_text' => 'text-[#C2410C]',
            ],
            [
                'key' => 'expenses',
                'label' => 'Expenses',
                'value' => '$10,633',
                'change' => '15%',
                'trend' => 'down',
                'icon' => 'receipt',
                'badge_bg' => 'bg-[#FEF2F2] border border-[#FECACA]',
                'badge_text' => 'text-[#B91C1C]',
            ],
        ];

        $cashFlow = [
            'monthly' => [
                'income' => '$12,645',
                'expense' => '$10,633',
                'savings' => '$3,657',
            ],
            'yearly' => [
                'income' => '$148,920',
                'expense' => '$96,410',
                'savings' => '$52,510',
            ],
        ];

        $budget = [
            'used_percent' => 64,
            'spent' => '$7,800',
            'min_label' => '$0',
            'max_label' => '$10,000',
            'remaining' => '$2,500',
            'helper_text' => 'Keep your spending below 80% to maintain a healthy and sustainable budget',
        ];

        $expensesBreakdown = [
            'monthly' => [
                ['label' => 'Operations', 'percent' => 48, 'color' => '#D1DDD6'],
                ['label' => 'Payroll & Payouts', 'percent' => 36, 'color' => '#059669'],
                ['label' => 'Infrastructure', 'percent' => 16, 'color' => '#14532D'],
            ],
            'yearly' => [
                ['label' => 'Operations', 'percent' => 52, 'color' => '#D1DDD6'],
                ['label' => 'Payroll & Payouts', 'percent' => 31, 'color' => '#059669'],
                ['label' => 'Infrastructure', 'percent' => 17, 'color' => '#14532D'],
            ],
        ];

        $cardDetails = [
            'holder_name' => 'John',
            'balance' => '$53,760.99',
            'team_avatars' => [
                ['initials' => 'JS', 'bg' => 'bg-[#ECFDF5] text-[#14532D]'],
                ['initials' => 'AM', 'bg' => 'bg-emerald-100 text-[#065F46]'],
                ['initials' => 'KL', 'bg' => 'bg-[#FFF7ED] text-[#C2410C]'],
                ['initials' => 'DR', 'bg' => 'bg-slate-100 text-[#0F172A]'],
            ],
            'last_transaction' => [
                'merchant' => 'Freepik Premium',
                'date' => 'Feb 24, 2026',
                'amount' => '$60.00',
            ],
        ];

        $upcomingBills = [
            [
                'id' => 'spotify',
                'title' => 'Spotify Subscription',
                'due_date' => 'Feb 24, 2026',
                'amount' => '$60.00',
                'brand' => 'spotify',
            ],
            [
                'id' => 'netflix',
                'title' => 'Netflix',
                'due_date' => 'Feb 24, 2026',
                'amount' => '$60.00',
                'brand' => 'netflix',
            ],
            [
                'id' => 'apple-one',
                'title' => 'Apple One',
                'due_date' => 'Feb 24, 2026',
                'amount' => '$60.00',
                'brand' => 'apple',
            ],
            [
                'id' => 'adobe-cc',
                'title' => 'Adobe Creative Cloud',
                'due_date' => 'Feb 24, 2026',
                'amount' => '$60.00',
                'brand' => 'adobe',
            ],
        ];

        return view('admin.dashboard', [
            'adminName' => $adminName,
            'kpis' => $kpis,
            'cashFlow' => $cashFlow,
            'budget' => $budget,
            'expensesBreakdown' => $expensesBreakdown,
            'cardDetails' => $cardDetails,
            'upcomingBills' => $upcomingBills,
        ]);
    }
}
