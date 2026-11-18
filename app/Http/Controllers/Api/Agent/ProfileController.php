<?php

namespace App\Http\Controllers\Api\Agent;

use App\Services\ProfileImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseAgentApiController
{
    /**
     * Return agent profile.
     */
    public function show(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        return response()->json($this->formatAgentUser($agent));
    }

    /**
     * Update agent profile details and optional photo.
     */
    public function update(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $user = auth()->user();

        $validated = $request->validate([
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'email', 'max:255', "unique:users,email,{$user->id}", "unique:agents,email,{$agent->id}"],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'profile_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('profile_image')) {
            $imagePath = app(ProfileImageService::class)->upload(
                $request->file('profile_image'),
                'agents'
            );
            $agent->profile_image = $imagePath;
            $user->profile_image = $imagePath;
        }

        if (isset($validated['phone'])) {
            $agent->phone = $validated['phone'];
        }
        if (isset($validated['email'])) {
            $agent->email = $validated['email'];
            $user->email = $validated['email'];
        }
        if (array_key_exists('place', $validated)) {
            $agent->place = $validated['place'];
        }
        if (array_key_exists('address', $validated)) {
            $agent->address = $validated['address'];
        }

        $agent->save();
        $user->save();

        return response()->json($this->formatAgentUser($agent));
    }

    /**
     * Change account password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match our records.'],
            ]);
        }

        $newHashed = Hash::make($validated['new_password']);
        $user->password = $newHashed;
        $user->save();

        $agent->password = $newHashed;
        $agent->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
