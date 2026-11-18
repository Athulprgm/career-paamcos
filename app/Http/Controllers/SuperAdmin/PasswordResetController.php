<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }
            if ($user->isBranchAdmin()) {
                return redirect()->route('branch-admin.dashboard');
            }
            if ($user->isAgent()) {
                return redirect()->route('agent.dashboard');
            }
            if ($user->isStaff()) {
                return redirect()->route('staff.dashboard');
            }
        }

        return view('superadmin.auth.forgot-password', $this->resolveSocietyBranding());
    }

    /**
     * Send a reset link to the given user via email.
     */
    public function store(Request $request): RedirectResponse
    {
        $input = trim((string) ($request->input('email') ?? $request->input('login', '')));

        if ($input === '') {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Please provide your registered email address or account ID.']);
        }

        $user = $this->resolveUserByIdentifier($input);

        if ($user && ! empty($user->email)) {
            $token = Password::broker()->createToken($user);
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $token, $resetUrl));

                AuditLog::record(
                    event: 'password_reset_requested',
                    description: "Password reset link sent to [{$user->email}] for identifier [{$input}].",
                    auditable: $user,
                    user: $user
                );
            } catch (\Throwable $e) {
                report($e);

                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Unable to send password reset email at this moment. Please try again later.']);
            }
        } else {
            AuditLog::record(
                event: 'password_reset_requested_unknown',
                description: "Password reset requested for unrecognized identifier [{$input}]."
            );
        }

        return back()->with('status', 'If an account matches that email or ID, we have sent a password reset link to its registered address. Please check your inbox.');
    }

    /**
     * Display the password reset view for the given token.
     */
    public function edit(Request $request, string $token): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('login');
        }

        $email = (string) $request->query('email', '');

        return view('superadmin.auth.reset-password', array_merge(
            $this->resolveSocietyBranding(),
            [
                'token' => $token,
                'email' => $email,
            ]
        ));
    }

    /**
     * Reset the user's password using the submitted token.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = trim(strtolower((string) $request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not find an account with that email address.']);
        }

        if (! Password::broker()->tokenExists($user, (string) $request->input('token'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This password reset link is invalid or has expired. Please request a new link.']);
        }

        $newHash = Hash::make((string) $request->input('password'));

        $user->forceFill([
            'password' => $newHash,
            'remember_token' => Str::random(60),
        ])->save();

        if ($user->agentProfile) {
            $user->agentProfile->update(['password' => $newHash]);
        }

        if ($user->staffProfile) {
            $user->staffProfile->update(['password' => $newHash]);
        }

        Password::broker()->deleteToken($user);

        AuditLog::record(
            event: 'password_reset_completed',
            description: "User [{$user->name}] ({$user->email}) successfully reset their password via email link.",
            auditable: $user,
            user: $user
        );

        return redirect()->route('login')
            ->with('status', 'Your password has been reset successfully! You can now log in with your new password.');
    }

    /**
     * Resolve a user by email, username, staff code, agent code, or branch code.
     */
    private function resolveUserByIdentifier(string $identifier): ?User
    {
        $normalized = strtolower(trim($identifier));

        // 1. Direct email lookup
        $user = User::whereRaw('LOWER(email) = ?', [$normalized])->first();
        if ($user) {
            return $user;
        }

        // 2. Super admin username shortcut
        if (in_array($normalized, ['admin', 'superadmin', 'super_admin'], true)) {
            return User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();
        }

        // 3. Branch admin username shortcut
        if (in_array($normalized, ['branchadmin', 'branch_admin'], true)) {
            return User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->whereNotNull('branch_id')->first();
        }

        // 4. Branch code lookup
        $branch = Branch::whereRaw('LOWER(code) = ?', [$normalized])->first();
        if ($branch) {
            $branchAdmin = $branch->admins()->first()
                ?? $branch->users()->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first();

            if ($branchAdmin) {
                return $branchAdmin;
            }

            if (! empty($branch->email)) {
                return User::whereRaw('LOWER(email) = ?', [strtolower($branch->email)])->first();
            }
        }

        // 5. Staff code / Employee ID lookup
        $staff = Staff::whereRaw('LOWER(staff_code) = ?', [$normalized])
            ->orWhereRaw('LOWER(employee_id) = ?', [$normalized])
            ->first();
        if ($staff && $staff->user) {
            return $staff->user;
        }

        // 6. Agent code / Agent ID lookup
        $agent = Agent::whereRaw('LOWER(member_code) = ?', [$normalized])
            ->orWhereRaw('LOWER(agent_id) = ?', [$normalized])
            ->first();
        if ($agent) {
            return $agent->user ?? User::whereRaw('LOWER(email) = ?', [strtolower((string) $agent->email)])->first();
        }

        return null;
    }

    /**
     * Resolve society branding values safely from system settings.
     *
     * @return array{
     *     societyName: string,
     *     societyShortName: string,
     *     societyRegistrationNumber: ?string,
     *     societyEmail: ?string,
     *     societyPhone: ?string,
     *     societyAddress: ?string
     * }
     */
    private function resolveSocietyBranding(): array
    {
        $defaults = [
            'societyName' => 'Pristine Agro & Allied Multi State Co-operative Society Ltd.',
            'societyShortName' => (string) config('app.name', 'PAAMCO'),
            'societyRegistrationNumber' => null,
            'societyEmail' => null,
            'societyPhone' => null,
            'societyAddress' => null,
        ];

        try {
            if (! Schema::hasTable('settings')) {
                return $defaults;
            }

            $societyName = (string) Setting::get('society.name', $defaults['societyName']);
            $shortName = Setting::get('society.short_name');

            return [
                'societyName' => $societyName !== '' ? $societyName : $defaults['societyName'],
                'societyShortName' => is_string($shortName) && trim($shortName) !== ''
                    ? trim($shortName)
                    : $defaults['societyShortName'],
                'societyRegistrationNumber' => Setting::get('society.registration_number') ?: null,
                'societyEmail' => Setting::get('society.email') ?: null,
                'societyPhone' => Setting::get('society.phone') ?: null,
                'societyAddress' => Setting::get('society.address') ?: null,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }
}
