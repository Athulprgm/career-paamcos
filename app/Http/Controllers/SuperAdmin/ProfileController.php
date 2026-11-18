<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if ($user) {
            // Free worker agents must have no branch assignment
            if ($user->isAgent() && $user->branch_id !== null) {
                $user->update(['branch_id' => null]);
            }
            $user->loadMissing([
                'roles',
                'branch',
                'agentProfile.rank',
                'agentProfile.parentAgent',
                'agentProfile.reportingStaff',
                'staffProfile.position',
            ]);
        }

        return view('superadmin.profile.show', ['user' => $user]);
    }

    public function update(Request $request, ProfileImageService $profileImageService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'profile_image' => ['nullable', 'string'],
            'profile_image_file' => ['nullable', 'image', 'max:5120'],
            'remove_profile_image' => ['nullable'],
        ]);

        if ($user->isAgent()) {
            $validated['branch_id'] = null;
        }

        $profileImagePath = $user->profile_image;

        if ($request->boolean('remove_profile_image')) {
            if ($user->profile_image && ! str_contains($user->profile_image, 'logo')) {
                $profileImageService->deleteImage($user->profile_image);
            }
            $profileImagePath = $user->isSuperAdmin() ? 'logo.png' : null;
        } elseif ($request->hasFile('profile_image_file')) {
            $oldImage = ($user->profile_image && ! str_contains($user->profile_image, 'logo')) ? $user->profile_image : null;
            $profileImagePath = $profileImageService->saveImage($request->file('profile_image_file'), $oldImage, 'profiles');
        } elseif ($request->filled('profile_image') && str_starts_with($request->input('profile_image'), 'data:image/')) {
            $oldImage = ($user->profile_image && ! str_contains($user->profile_image, 'logo')) ? $user->profile_image : null;
            $profileImagePath = $profileImageService->saveImage($request->input('profile_image'), $oldImage, 'profiles');
        }

        $validated['profile_image'] = $profileImagePath;
        unset($validated['profile_image_file'], $validated['remove_profile_image']);

        $user->update($validated);

        if ($user->agentProfile) {
            $user->agentProfile->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'branch_id' => null,
                'profile_image' => $profileImagePath,
            ]);
        }

        if ($user->staffProfile) {
            $user->staffProfile->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()->route('superadmin.profile')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! password_verify($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $newHashedPassword = Hash::make($request->password);
        $user->update(['password' => $newHashedPassword]);

        if ($user->agentProfile) {
            $user->agentProfile->update(['password' => $newHashedPassword]);
        }

        return redirect()->route('superadmin.profile')
            ->with('success', 'Password changed successfully.');
    }
}
