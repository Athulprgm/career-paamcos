<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\DepositScheme;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Get branch restriction for current user.
     * Null means user is Super Admin (unrestricted).
     */
    protected function getRestrictedBranchId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return null;
        }
        if ($user && $user->isBranchAdmin()) {
            return $user->branch_id;
        }
        if ($user && $user->isAgent()) {
            return $user->agentProfile?->branch_id ?? $user->branch_id;
        }

        return $user ? ($user->branch_id ?? $user->staffProfile?->branch_id) : null;
    }

    /**
     * Get agent restriction for current user (null for non-agent).
     */
    protected function getRestrictedAgentId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            $agent = Agent::where('user_id', $user->id)->first();

            return $agent?->id ?? -1;
        }

        return null;
    }

    /**
     * Helper to load branches and agents accessible to current user for filter dropdowns.
     */
    protected function getFilterOptions(): array
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $branches = $restrictedBranchId === null
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : Branch::where('id', $restrictedBranchId)->get();

        $agentQuery = Agent::whereIn('status', ['active', 'ACTIVE']);
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        }
        if ($restrictedAgentId !== null) {
            $agentQuery->where('id', $restrictedAgentId);
        }
        $agents = $agentQuery->orderBy('name')->get();

        $schemes = DepositScheme::where('status', 'ACTIVE')->orderBy('name')->get();
        $careerLevels = CareerLevel::ordered()->get();

        return compact('branches', 'agents', 'schemes', 'careerLevels', 'restrictedBranchId', 'restrictedAgentId');
    }

    /**
     * 1. Overview Dashboard
     */
    public function overview(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getOverviewData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.overview', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    /**
     * 2. Deposit Report
     */
    public function deposits(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'customer_id', 'deposit_scheme_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getDepositReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.deposits', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    public function exportDeposits(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'customer_id', 'deposit_scheme_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getDepositReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'deposit-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Deposit Number',
                'Customer Number',
                'Customer Name',
                'Branch',
                'Agent',
                'Scheme',
                'Amount (INR)',
                'Interest Rate (%)',
                'Start Date',
                'Maturity Date',
                'Status',
            ]);

            foreach ($records as $d) {
                fputcsv($handle, [
                    $d->deposit_number,
                    $d->customer?->customer_number ?? '-',
                    $d->customer?->name ?? '-',
                    $d->branch?->name ?? '-',
                    $d->agent?->name ?? 'Direct',
                    $d->scheme?->name ?? '-',
                    number_format((float) $d->amount, 2, '.', ''),
                    $d->interest_rate ?? '0',
                    $d->start_date ? Carbon::parse($d->start_date)->format('Y-m-d') : '-',
                    $d->maturity_date ? Carbon::parse($d->maturity_date)->format('Y-m-d') : '-',
                    $d->status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * 3. Commission Report
     */
    public function commissions(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'period', 'branch_id', 'agent_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCommissionReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.commissions', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    public function exportCommissions(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'period', 'branch_id', 'agent_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCommissionReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'commission-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Commission Number',
                'Agent Code',
                'Agent Name',
                'Customer Name',
                'Deposit Number',
                'Payout Year',
                'Collection Number',
                'Base Amount (INR)',
                'Rate (%)',
                'Commission Amount (INR)',
                'Period',
                'Status',
                'Created Date',
            ]);

            foreach ($records as $com) {
                fputcsv($handle, [
                    $com->commission_number ?: $com->commission_id,
                    $com->agent?->agent_code ?? '-',
                    $com->agent?->name ?? '-',
                    $com->customer?->name ?? '-',
                    $com->deposit?->deposit_number ?? '-',
                    $com->payout_year ? 'Year '.$com->payout_year : ($com->collection_id ? 'N/A' : 'Year 1'),
                    $com->collection?->collection_number ?? '-',
                    number_format((float) $com->base_amount, 2, '.', ''),
                    number_format((float) ($com->commission_rate ?? $com->rate), 2, '.', ''),
                    number_format((float) $com->commission_amount, 2, '.', ''),
                    $com->period ?? '-',
                    $com->status,
                    $com->created_at ? $com->created_at->format('Y-m-d') : '-',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * 5. Agent Performance Report
     */
    public function agentPerformance(Request $request)
    {
        $filters = $request->only(['period', 'branch_id', 'agent_id', 'career_level_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getAgentPerformanceReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.agents', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    public function exportAgentPerformance(Request $request): StreamedResponse
    {
        $filters = $request->only(['period', 'branch_id', 'agent_id', 'career_level_id', 'status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getAgentPerformanceReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'agent-performance-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Agent Code',
                'Agent Name',
                'Branch',
                'Career Level',
                'Active Customers',
                'Total Customers',
                'Deposit Count',
                'Deposit Value (INR)',
                'Collection Count',
                'Collection Value (INR)',
                'Commissions Paid (INR)',
                'Performance Score',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row['agent']->agent_code,
                    $row['agent']->name,
                    $row['agent']->branch?->name ?? '-',
                    $row['career_level'],
                    $row['active_customers'],
                    $row['total_customers'],
                    $row['deposit_count'],
                    number_format((float) $row['deposit_amount'], 2, '.', ''),
                    $row['collection_count'],
                    number_format((float) $row['collection_amount'], 2, '.', ''),
                    number_format((float) $row['commission_amount'], 2, '.', ''),
                    number_format((float) $row['performance_score'], 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * 6. Customer Report
     */
    public function customers(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'status', 'kyc_status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCustomerReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.customers', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    public function exportCustomers(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'status', 'kyc_status', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCustomerReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'customer-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Customer Number',
                'Customer Name',
                'Mobile',
                'Email',
                'Branch',
                'Agent',
                'Status',
                'Active Deposits',
                'Total Deposit Value (INR)',
                'Registered Date',
            ]);

            foreach ($records as $cust) {
                fputcsv($handle, [
                    $cust->customer_number,
                    $cust->name,
                    $cust->mobile ?? '-',
                    $cust->email ?? '-',
                    $cust->branch?->name ?? '-',
                    $cust->agent?->name ?? 'Direct',
                    $cust->status,
                    $cust->active_deposits_count ?? 0,
                    number_format((float) ($cust->total_deposits_sum ?? 0), 2, '.', ''),
                    $cust->created_at ? $cust->created_at->format('Y-m-d') : '-',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * 7. Career Report
     */
    public function career(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'status']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCareerReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        return view('admin.reports.career', array_merge($data, $options, [
            'filters' => $filters,
        ]));
    }

    public function exportCareer(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'agent_id', 'status']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getCareerReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'career-promotion-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Request Number',
                'Agent Code',
                'Agent Name',
                'Branch',
                'Current Level',
                'Proposed Level',
                'Period',
                'Status',
                'Request Date',
                'Reviewed Date',
                'Requested By',
                'Reviewed By',
            ]);

            foreach ($records as $req) {
                fputcsv($handle, [
                    $req->request_number,
                    $req->agent?->agent_code ?? '-',
                    $req->agent?->name ?? '-',
                    $req->branch?->name ?? '-',
                    $req->currentLevel?->name ?? '-',
                    $req->proposedLevel?->name ?? '-',
                    $req->performance_period ?? '-',
                    $req->status,
                    $req->request_date ? Carbon::parse($req->request_date)->format('Y-m-d') : '-',
                    $req->reviewed_at ? Carbon::parse($req->reviewed_at)->format('Y-m-d') : '-',
                    $req->requester?->name ?? '-',
                    $req->reviewer?->name ?? '-',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * 8. Audit Report
     */
    public function audit(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id', 'event', 'auditable_type', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getAuditReportData($filters, $restrictedBranchId, $restrictedAgentId);
        $options = $this->getFilterOptions();

        $users = User::orderBy('name')->get();

        return view('admin.reports.audit', array_merge($data, $options, [
            'filters' => $filters,
            'allUsers' => $users,
        ]));
    }

    public function exportAudit(Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id', 'event', 'auditable_type', 'search']);
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $data = $this->reportService->getAuditReportData($filters, $restrictedBranchId, $restrictedAgentId, 0, true);
        $records = $data['records'];

        $filename = 'audit-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Timestamp',
                'User',
                'Event',
                'Target Entity',
                'Entity ID',
                'Description',
                'Financial Amount (INR)',
                'IP Address',
            ]);

            foreach ($records as $log) {
                fputcsv($handle, [
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                    $log->user_name ?? ($log->user?->name ?? 'System'),
                    $log->event,
                    class_basename($log->auditable_type ?? 'N/A'),
                    $log->auditable_id ?? '-',
                    $log->description,
                    $log->financial_amount !== null ? number_format((float) $log->financial_amount, 2, '.', '') : '-',
                    $log->ip_address ?? '-',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
