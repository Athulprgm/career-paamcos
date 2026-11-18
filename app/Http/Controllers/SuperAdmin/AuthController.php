<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
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

        return view('superadmin.auth.login', self::resolveSocietyBranding());
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) ($request->input('login') ?? $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $loginField = $request->has('login') ? 'login' : 'email';

        if ($loginInput === '') {
            $errors = [
                $loginField => 'Please provide your email address or user ID.',
                'email' => 'Please provide your email address or user ID.',
            ];

            if ($password === '') {
                $errors['password'] = 'The password field is required.';
            }

            return back()
                ->withInput($request->only(['login', 'email', 'remember']))
                ->withErrors($errors);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = null;
        $matchedBranch = null;
        $loginType = 'email';

        // 1. Direct email match on User (case-insensitive)
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($loginInput)])->first();

        // 2. Username shortcut for Super Admin
        if (! $user && in_array(strtolower($loginInput), ['admin', 'superadmin', 'super_admin'])) {
            $candidates = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->get();
            foreach ($candidates as $candidate) {
                if ($this->verifyPassword($password, $candidate->password)) {
                    $user = $candidate;
                    break;
                }
            }
            if (! $user) {
                $user = $candidates->first();
            }
            $loginType = 'username';
        }

        // 3. Username shortcut for Branch Admin
        if (! $user && in_array(strtolower($loginInput), ['branchadmin', 'branch_admin'])) {
            $candidates = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->whereNotNull('branch_id')->get();
            foreach ($candidates as $candidate) {
                if ($this->verifyPassword($password, $candidate->password)) {
                    $user = $candidate;
                    break;
                }
            }
            if (! $user) {
                $user = $candidates->first();
            }
            $loginType = 'username';
        }

        // 4. Branch Code lookup (case-insensitive) for assigned branch admin
        if (! $user) {
            $matchedBranch = Branch::whereRaw('LOWER(code) = ?', [strtolower($loginInput)])->first();
            if ($matchedBranch) {
                $loginType = 'branch_code';
                $user = $matchedBranch->admins()->first()
                    ?? $matchedBranch->users()->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first();

                if (! $user && ! empty($matchedBranch->email)) {
                    $user = User::whereRaw('LOWER(email) = ?', [strtolower($matchedBranch->email)])->first();
                }
            }
        }

        // 5. Staff Code / Employee ID lookup
        if (! $user) {
            $staff = Staff::whereRaw('LOWER(staff_code) = ?', [strtolower($loginInput)])
                ->orWhereRaw('LOWER(employee_id) = ?', [strtolower($loginInput)])
                ->first();
            if ($staff && $staff->user) {
                $user = $staff->user;
                $loginType = 'staff_code';
            }
        }

        $matchedAgent = null;

        // 6. Agent Member Code / Agent ID lookup
        if (! $user) {
            $matchedAgent = Agent::whereRaw('LOWER(member_code) = ?', [strtolower($loginInput)])
                ->orWhereRaw('LOWER(agent_id) = ?', [strtolower($loginInput)])
                ->first();
            if ($matchedAgent) {
                $user = $matchedAgent->user ?? User::whereRaw('LOWER(email) = ?', [strtolower($matchedAgent->email)])->first();
                if ($user) {
                    $loginType = 'agent_code';
                    if (! $matchedAgent->user_id) {
                        $matchedAgent->updateQuietly(['user_id' => $user->id]);
                    }
                }
            }
        }

        $passwordMatches = false;

        if ($user) {
            if ($this->verifyPassword($password, $user->password)) {
                $passwordMatches = true;

                // Transparently upgrade legacy bcrypt hashes (or outdated cost
                // factors) to the currently configured algorithm (argon2id).
                // Hash::needsRehash() asks PHP whether the stored hash uses the
                // current driver's algorithm and parameters — returns true for any
                // bcrypt hash when the driver is now argon2id.
                if (Hash::needsRehash($user->password)) {
                    $user->update(['password' => Hash::make($password)]);
                }
            } elseif ($user->isBranchAdmin() && $user->branch && ! empty($user->branch->password) && $this->verifyPassword($password, $user->branch->password)) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            } elseif ($matchedAgent && ! empty($matchedAgent->password) && $this->verifyPassword($password, $matchedAgent->password)) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            } elseif ($user->isAgent() && ! empty($user->agentProfile?->password) && $this->verifyPassword($password, $user->agentProfile->password)) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            } elseif ($matchedBranch && ! empty($matchedBranch->password) && $this->verifyPassword($password, $matchedBranch->password)) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            }
        }

        if (! $passwordMatches || ! $user) {
            AuditLog::record(
                event: 'login_failed',
                description: "Failed login attempt for [{$loginInput}].",
                oldValues: null,
                newValues: ['identifier' => $loginInput, 'login_type' => $loginType]
            );

            return $this->failedLoginResponse($request, $loginField, 'These credentials do not match our records.');
        }

        // Check if branch is deactivated (unless Super Admin)
        if (! $user->isSuperAdmin()) {
            if ($matchedBranch && ! $matchedBranch->is_active) {
                AuditLog::record(
                    event: 'login_failed',
                    description: "Login rejected for [{$loginInput}]: Branch [{$matchedBranch->code}] is inactive.",
                    auditable: $matchedBranch
                );

                return $this->failedLoginResponse(
                    $request,
                    $loginField,
                    "Branch [{$matchedBranch->code}] is currently inactive. Please contact the administrator."
                );
            }

            if ($user->branch && ! $user->branch->is_active) {
                AuditLog::record(
                    event: 'login_failed',
                    description: "Login rejected for user [{$user->email}]: Branch [{$user->branch->code}] is inactive.",
                    auditable: $user
                );

                return $this->failedLoginResponse(
                    $request,
                    $loginField,
                    'Your branch account is currently inactive. Please contact the administrator.'
                );
            }
        }

        if ($user->isStaff() && $user->staffProfile && $user->staffProfile->status === 'inactive') {
            AuditLog::record(
                event: 'login_failed',
                description: "Login rejected for user [{$user->email}]: Staff account is inactive.",
                auditable: $user
            );

            return $this->failedLoginResponse(
                $request,
                $loginField,
                'Your staff account is currently inactive. Please contact the administrator.'
            );
        }

        if ($user->isAgent() && $user->agentProfile) {
            $agentStatus = strtolower((string) $user->agentProfile->status);
            if ($agentStatus === 'pending') {
                AuditLog::record(
                    event: 'login_failed',
                    description: "Login rejected for user [{$user->email}]: Agent account is pending verification and acceptance by Super Admin.",
                    auditable: $user
                );

                return $this->failedLoginResponse(
                    $request,
                    $loginField,
                    'Your agent account is currently pending verification and acceptance by the Super Admin.'
                );
            }

            if ($agentStatus === 'inactive' || $agentStatus === 'rejected') {
                AuditLog::record(
                    event: 'login_failed',
                    description: "Login rejected for user [{$user->email}]: Agent account is {$agentStatus}.",
                    auditable: $user
                );

                return $this->failedLoginResponse(
                    $request,
                    $loginField,
                    "Your agent account is currently {$agentStatus}. Please contact the administrator."
                );
            }
        }

        if ($user->hasRole('customer')) {
            AuditLog::record(
                event: 'login_failed',
                description: "Login rejected for user [{$user->email}]: Customer portal login is disabled.",
                auditable: $user
            );

            return $this->failedLoginResponse($request, $loginField, 'Customer login access is not available.');
        }

        if (! ($user->isAdmin() || $user->isStaff() || $user->isAgent())) {
            AuditLog::record(
                event: 'login_failed',
                description: "Login rejected for user [{$user->email}]: No administrative, staff, or agent role assigned.",
                auditable: $user
            );

            return $this->failedLoginResponse(
                $request,
                $loginField,
                'You do not have administrative, staff, or agent access.'
            );
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $description = "User [{$user->name}] ({$user->email}) logged in successfully";
        if ($matchedBranch) {
            $description .= " via Branch Code [{$matchedBranch->code}]";
        } elseif ($loginType !== 'email') {
            $description .= ' via '.str_replace('_', ' ', $loginType)." [{$loginInput}]";
        }
        $description .= '.';

        AuditLog::record(
            event: 'login',
            description: $description,
            auditable: $user,
            user: $user,
            newValues: ['identifier' => $loginInput, 'login_type' => $loginType]
        );

        if ($user->isSuperAdmin()) {
            $targetRoute = route('superadmin.dashboard');
        } elseif ($user->isBranchAdmin()) {
            $targetRoute = route('branch-admin.dashboard');
        } elseif ($user->isAgent()) {
            $targetRoute = route('agent.dashboard');
        } elseif ($user->isStaff()) {
            $targetRoute = route('staff.dashboard');
        } else {
            $targetRoute = route('superadmin.login');
        }

        $intendedUrl = session()->get('url.intended');
        if (! $user->isAdmin() && $intendedUrl) {
            if (
                (str_contains($intendedUrl, '/admin/staff') && ! $user->hasPermission('view-employees')) ||
                str_contains($intendedUrl, '/admin/agents') ||
                str_contains($intendedUrl, '/superadmin/dashboard') ||
                str_contains($intendedUrl, '/branch-admin/dashboard')
            ) {
                session()->forget('url.intended');

                return redirect()->to($targetRoute)
                    ->with('success', 'Welcome back, '.$user->name.'!');
            }
        }

        return redirect()->intended($targetRoute)
            ->with('success', 'Welcome back, '.$user->name.'!');
    }

    /**
     * Handle a password change request for the authenticated user.
     *
     * Flow:
     *  1. Validate: current password + new password (min 8, confirmed).
     *  2. Re-verify current password via verifyPassword() — algorithm-agnostic,
     *     works whether the stored hash is bcrypt or argon2id.
     *  3. Hash::make() produces a new argon2id hash (or bcrypt fallback).
     *  4. Sync the new hash to any legacy agent/staff profile password columns.
     *  5. Record an audit log entry.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Use algorithm-agnostic verification so this works during the
        // migration window when the user's stored hash may still be bcrypt.
        if (! $this->verifyPassword($request->string('current_password')->value(), $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is incorrect.',
            ]);
        }

        // Hash::make() uses the driver resolved by config/hashing.php:
        //   argon2id (OWASP 16 MB / 2 iterations / 1 thread) on supported hosts,
        //   bcrypt (cost 12) as the automatic fallback.
        $newHash = Hash::make($request->string('password')->value());

        $user->update(['password' => $newHash]);

        // Keep profile password columns in sync (legacy schema support).
        if ($user->agentProfile) {
            $user->agentProfile->update(['password' => $newHash]);
        }

        if ($user->staffProfile) {
            $user->staffProfile->update(['password' => $newHash]);
        }

        AuditLog::record(
            event: 'password_changed',
            description: "User [{$user->name}] ({$user->email}) changed their password.",
            auditable: $user,
            user: $user
        );

        return back()->with('success', 'Your password has been updated successfully.');
    }

    /**
     * Verify a plain-text password against a stored hash in an
     * algorithm-agnostic way.
     *
     * We deliberately bypass Hash::check() here because Laravel 13's
     * Argon2IdHasher::check() throws a RuntimeException when
     * config('hashing.verify') is true and the stored hash was created with
     * a different algorithm (e.g. bcrypt hashes that pre-date the argon2id
     * migration). PHP's own password_verify() handles bcrypt, argon2i, and
     * argon2id natively without any algorithm guard.
     *
     * Hash::make() and Hash::needsRehash() are still used for writing new
     * hashes, so every successful login silently upgrades stale hashes to
     * the currently configured driver.
     */
    private function verifyPassword(string $plain, string $storedHash): bool
    {
        if ($storedHash === '') {
            return false;
        }

        return password_verify($plain, $storedHash);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::record(
                event: 'logout',
                description: "User [{$user->name}] ({$user->email}) logged out.",
                auditable: $user,
                user: $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Build a consistent failed login redirect preserving identifier and remember selection.
     */
    private function failedLoginResponse(Request $request, string $loginField, string $message): RedirectResponse
    {
        return back()
            ->withInput($request->only(['login', 'email', 'remember']))
            ->withErrors([
                $loginField => $message,
                'email' => $message,
            ]);
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
    public static function resolveSocietyBranding(): array
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
