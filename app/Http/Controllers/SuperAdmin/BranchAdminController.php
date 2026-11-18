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

class BranchAdminController extends Controller
{
    private function adminRoleId(): int
    {
        return Role::where('slug', 'admin')->value('id');
    }

    public function index(Request $request)
    {
        $adminRoleId = $this->adminRoleId();

        $query = User::whereHas('roles', fn ($q) => $q->where('role_id', $adminRoleId))
            ->where(function ($q) {
                $q->whereDoesntHave('branch', function ($bq) {
                    $bq->whereColumn('branches.email', 'users.email')
                        ->orWhereRaw('LOWER(users.name) = LOWER(CONCAT(branches.name, " Admin"))');
                })
                    ->where('users.email', 'not like', '%@branch.multicred.com');
            })
            ->with('branch');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            // Users don't have a status column; we filter by email_verified_at proxy
            // actual status will be managed via branch_id nullability
        }

        $admins = $query->orderBy('name')->paginate(15)->withQueryString();
        $branches = Branch::active()->orderBy('name')->get();

        return view('superadmin.branch-admins.index', compact('admins', 'branches'));
    }

    public function create()
    {
        $branches = Branch::active()->orderBy('name')->get();

        return view('superadmin.branch-admins.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['required', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);

        if ($request->filled('password')) {
            $userPassword = Hash::make((string) $request->input('password'));
            $rawPasswordForMail = (string) $request->input('password');
        } else {
            $userPassword = $branch->password;
            if (empty($userPassword)) {
                $rawGenerated = PasswordGeneratorService::generate(10);
                $userPassword = Hash::make($rawGenerated);
                $branch->update(['password' => $userPassword]);
            }
            $rawPasswordForMail = 'Branch Login Password ('.$branch->code.')';
        }

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'email_verified_at' => now(),
        ]);
        $user->setRawAttributes(array_merge($user->getAttributes(), ['password' => $userPassword]));
        $user->save();

        $adminRoleId = $this->adminRoleId();
        $user->roles()->attach($adminRoleId);

        // Keep branch password in sync if branch doesn't have one configured
        if ($branch && empty($branch->password)) {
            $branch->update(['password' => $userPassword]);
        }

        // Dispatch Welcome Credentials Email
        $emailDispatched = false;
        if ($request->boolean('send_welcome_email', true) && ! empty($user->email)) {
            try {
                Mail::to($user->email)->send(new AccountWelcomeMail(
                    recipientName: $user->name,
                    accountType: 'Branch Admin',
                    loginEmail: $user->email,
                    rawPassword: $rawPasswordForMail,
                    loginUrl: route('superadmin.login'),
                    identifier: $branch?->code,
                    branchName: $branch?->name,
                    roleOrRank: 'Branch Administrator',
                    customMessage: "You have been assigned as the Branch Administrator for [{$branch?->name}] ({$branch?->code}). You can sign in using your email address and your branch's login password, or sign in directly using Branch Code [{$branch?->code}]."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch Branch Admin welcome credentials email to [{$user->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'branch_admin_created',
            description: "Created Branch Admin [{$user->name}] for branch #{$user->branch_id}.",
            auditable: $user,
            newValues: $user->only(['name', 'email', 'branch_id'])
        );

        $flashMessage = 'Branch Admin created successfully.';
        if ($emailDispatched) {
            $flashMessage .= " Welcome email with login details has been sent to {$user->email}.";
        }

        return redirect()->route('superadmin.branch-admins.index')
            ->with('success', $flashMessage);
    }

    public function edit(User $branchAdmin)
    {
        $branches = Branch::active()->orderBy('name')->get();

        return view('superadmin.branch-admins.edit', compact('branchAdmin', 'branches'));
    }

    public function update(Request $request, User $branchAdmin)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$branchAdmin->id],
            'mobile' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['required', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $oldValues = $branchAdmin->only(['name', 'email', 'branch_id']);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
        ];

        $rawPassword = $request->filled('password') ? (string) $request->input('password') : null;
        if ($rawPassword !== null) {
            $data['password'] = Hash::make($rawPassword);
        } elseif ((int) $branchAdmin->branch_id !== (int) $validated['branch_id']) {
            $newBranch = Branch::find($validated['branch_id']);
            if ($newBranch && ! empty($newBranch->password)) {
                $branchAdmin->setRawAttributes(array_merge($branchAdmin->getAttributes(), ['password' => $newBranch->password]));
            }
        }

        $branchAdmin->update($data);

        $emailDispatched = false;
        if ($rawPassword !== null && $request->boolean('send_welcome_email')) {
            try {
                $branch = Branch::find($validated['branch_id']);
                Mail::to($branchAdmin->email)->send(new AccountWelcomeMail(
                    recipientName: $branchAdmin->name,
                    accountType: 'Branch Admin',
                    loginEmail: $branchAdmin->email,
                    rawPassword: $rawPassword,
                    loginUrl: route('superadmin.login'),
                    identifier: $branch?->code,
                    branchName: $branch?->name,
                    roleOrRank: 'Branch Administrator',
                    customMessage: "Your Branch Administrator credentials for [{$branch?->name}] ({$branch?->code}) have been updated."
                ));
                $emailDispatched = true;
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch updated Branch Admin email to [{$branchAdmin->email}]: ".$e->getMessage());
            }
        }

        AuditLog::record(
            event: 'branch_admin_updated',
            description: "Updated Branch Admin [{$branchAdmin->name}].",
            auditable: $branchAdmin,
            oldValues: $oldValues,
            newValues: $branchAdmin->only(['name', 'email', 'branch_id'])
        );

        $flashMessage = 'Branch Admin updated successfully.';
        if ($emailDispatched) {
            $flashMessage .= ' Updated credentials email has been dispatched.';
        }

        return redirect()->route('superadmin.branch-admins.index')
            ->with('success', $flashMessage);
    }

    public function destroy(User $branchAdmin)
    {
        $oldBranch = $branchAdmin->branch_id;
        // Detach all roles then nullify branch — soft deactivation
        $branchAdmin->update(['branch_id' => null]);
        $branchAdmin->roles()->detach();

        AuditLog::record(
            event: 'branch_admin_removed',
            description: "Deactivated Branch Admin [{$branchAdmin->name}] from branch #{$oldBranch}.",
            auditable: $branchAdmin,
            oldValues: ['branch_id' => $oldBranch],
            newValues: ['branch_id' => null]
        );

        return redirect()->route('superadmin.branch-admins.index')
            ->with('success', 'Branch Admin removed successfully.');
    }
}
