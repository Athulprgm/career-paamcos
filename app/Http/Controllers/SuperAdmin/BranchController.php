<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\AccountWelcomeMail;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $branches = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('superadmin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('superadmin.branches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rawPassword = $request->filled('password')
            ? (string) $request->input('password')
            : PasswordGeneratorService::generate(10);

        $validated['password'] = Hash::make($rawPassword);
        $validated['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($validated);

        $adminUser = $this->syncBranchAdminUser($branch, $rawPassword);

        // Dispatch Welcome Credentials Email
        $emailDispatched = false;
        $recipientEmail = ! empty($branch->email) ? $branch->email : ($adminUser->email ?? null);
        if ($request->boolean('send_welcome_email', true) && ! empty($recipientEmail) && ! str_ends_with($recipientEmail, '@branch.multicred.com')) {
            try {
                Mail::to($recipientEmail)->send(new AccountWelcomeMail(
                    recipientName: $branch->name.' Administration',
                    accountType: 'Branch Admin',
                    loginEmail: $adminUser->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $branch->code,
                    branchName: $branch->name,
                    roleOrRank: 'Branch Administrator',
                    customMessage: "Your branch administration portal has been initialized for [{$branch->name}] ({$branch->code}). You can sign in using either your email address or branch code."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch branch credentials email to [{$recipientEmail}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'branch_created',
            description: "Created branch [{$branch->code}] '{$branch->name}'.",
            auditable: $branch,
            newValues: $branch->only(['code', 'name', 'phone', 'email', 'is_active'])
        );

        $flashMessage = 'Branch created successfully.';
        if ($emailDispatched) {
            $flashMessage .= " Login credentials have been emailed to {$recipientEmail}.";
        }

        return redirect()->route('superadmin.branches.index')
            ->with('success', $flashMessage);
    }

    public function edit(Branch $branch)
    {
        return view('superadmin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:branches,code,'.$branch->id],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rawPassword = $request->filled('password') ? (string) $request->input('password') : null;
        if ($rawPassword !== null) {
            $validated['password'] = Hash::make($rawPassword);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $branch->only(['code', 'name', 'phone', 'email', 'is_active']);
        $branch->update($validated);

        $emailDispatched = false;
        if ($rawPassword !== null) {
            $adminUser = $this->syncBranchAdminUser($branch, $rawPassword);

            // Also keep all existing branch admins' passwords synchronized with the branch password
            User::where('branch_id', $branch->id)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->update(['password' => Hash::make($rawPassword)]);

            $recipientEmail = ! empty($branch->email) ? $branch->email : ($adminUser->email ?? null);
            if ($request->boolean('send_welcome_email') && ! empty($recipientEmail) && ! str_ends_with($recipientEmail, '@branch.multicred.com')) {
                try {
                    Mail::to($recipientEmail)->send(new AccountWelcomeMail(
                        recipientName: $branch->name.' Administration',
                        accountType: 'Branch Admin',
                        loginEmail: $adminUser->email,
                        rawPassword: $rawPassword,
                        loginUrl: route('superadmin.login'),
                        identifier: $branch->code,
                        branchName: $branch->name,
                        roleOrRank: 'Branch Administrator',
                        customMessage: "The password for your branch administration portal [{$branch->name}] ({$branch->code}) has been updated."
                    ));
                    $emailDispatched = true;
                } catch (\Throwable $e) {
                    Log::warning("Could not dispatch updated branch credentials email to [{$recipientEmail}]: ".$e->getMessage());
                }
            }
        } elseif (! empty($branch->email) && $oldValues['email'] !== $branch->email) {
            // If email changed and a branch admin exists with old email, sync email
            $adminUser = $branch->admins()->first();
            if ($adminUser && $adminUser->email === $oldValues['email']) {
                $adminUser->update(['email' => $branch->email]);
            }
        }

        AuditLog::record(
            event: 'branch_updated',
            description: "Updated branch [{$branch->code}] '{$branch->name}'.",
            auditable: $branch,
            oldValues: $oldValues,
            newValues: $branch->only(['code', 'name', 'phone', 'email', 'is_active'])
        );

        $flashMessage = 'Branch updated successfully.';
        if ($emailDispatched) {
            $flashMessage .= ' Updated credentials email has been dispatched.';
        }

        return redirect()->route('superadmin.branches.index')
            ->with('success', $flashMessage);
    }

    /**
     * Ensure a branch admin user account is provisioned or updated for the branch.
     */
    protected function syncBranchAdminUser(Branch $branch, string $password): User
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);

        $user = $branch->admins()->first();

        if (! $user && ! empty($branch->email)) {
            $user = User::where('email', $branch->email)->first();
        }

        if ($user) {
            $user->update([
                'password' => Hash::make($password),
                'branch_id' => $branch->id,
            ]);
            $user->roles()->syncWithoutDetaching([$adminRole->id]);

            return $user;
        }

        $adminEmail = ! empty($branch->email)
            ? $branch->email
            : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $branch->code)).'@branch.multicred.com';

        $existingByEmail = User::where('email', $adminEmail)->first();
        if ($existingByEmail) {
            $existingByEmail->update([
                'password' => Hash::make($password),
                'branch_id' => $branch->id,
            ]);
            $existingByEmail->roles()->syncWithoutDetaching([$adminRole->id]);

            return $existingByEmail;
        }

        $newUser = User::create([
            'name' => $branch->name.' Admin',
            'email' => $adminEmail,
            'password' => Hash::make($password),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);

        $newUser->roles()->syncWithoutDetaching([$adminRole->id]);

        return $newUser;
    }

    public function destroy(Branch $branch)
    {
        if ($branch->collections()->exists() || $branch->deposits()->exists()) {
            return redirect()->route('superadmin.branches.index')
                ->with('error', "Cannot delete branch [{$branch->code}] because it has existing financial records. You can deactivate it instead.");
        }

        try {
            $code = $branch->code;
            $name = $branch->name;

            // Nullify branch references for users associated with this branch
            User::where('branch_id', $branch->id)->update(['branch_id' => null]);

            AuditLog::record(
                event: 'branch_deleted',
                description: "Deleted branch [{$code}] '{$name}'.",
                auditable: $branch,
                oldValues: $branch->only(['code', 'name', 'phone', 'email', 'is_active'])
            );

            $branch->delete();

            return redirect()->route('superadmin.branches.index')
                ->with('success', "Branch [{$code}] '{$name}' deleted successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.branches.index')
                ->with('error', "Could not delete branch [{$branch->code}]: {$e->getMessage()}");
        }
    }

    public function toggleStatus(Branch $branch)
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        $status = $branch->is_active ? 'activated' : 'deactivated';

        AuditLog::record(
            event: 'branch_status_toggled',
            description: "Branch [{$branch->code}] {$status}.",
            auditable: $branch,
            newValues: ['is_active' => $branch->is_active]
        );

        return redirect()->back()->with('success', "Branch {$status} successfully.");
    }
}
