<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseAgentApiController
{
    /**
     * Authenticate field agent and return Sanctum Bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['identifier']);
        $password = $validated['password'];

        $user = null;
        $matchedAgent = null;

        // 1. Locate User by email (case-insensitive)
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();

        // 2. Locate Agent by agent_id, member_code, or email (case-insensitive)
        $matchedAgent = Agent::whereRaw('LOWER(agent_id) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(member_code) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($identifier)])
            ->first();

        if ($matchedAgent) {
            if (! $user) {
                $user = $matchedAgent->user ?? User::whereRaw('LOWER(email) = ?', [strtolower($matchedAgent->email)])->first();
            }

            if ($user && ! $matchedAgent->user_id) {
                $matchedAgent->updateQuietly(['user_id' => $user->id]);
            }
        }

        if ($user && ! $matchedAgent) {
            $matchedAgent = $user->agentProfile;
        }

        // 3. Password Verification (matches web logic: checks User password, Agent password, and linked profile password)
        $passwordMatches = false;

        if ($user) {
            if (! empty($user->password) && (password_verify($password, $user->password) || Hash::check($password, $user->password))) {
                $passwordMatches = true;
                if (Hash::needsRehash($user->password)) {
                    $user->update(['password' => Hash::make($password)]);
                }
            } elseif ($matchedAgent && ! empty($matchedAgent->password) && (password_verify($password, $matchedAgent->password) || Hash::check($password, $matchedAgent->password))) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            } elseif ($user->agentProfile && ! empty($user->agentProfile->password) && (password_verify($password, $user->agentProfile->password) || Hash::check($password, $user->agentProfile->password))) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($password)]);
            }
        } elseif ($matchedAgent && ! empty($matchedAgent->password) && (password_verify($password, $matchedAgent->password) || Hash::check($password, $matchedAgent->password))) {
            // If Agent exists in database without User row, find or create User account
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($matchedAgent->email)])->first();
            if (! $user && ! empty($matchedAgent->email)) {
                $user = User::create([
                    'name' => $matchedAgent->name,
                    'email' => $matchedAgent->email,
                    'password' => Hash::make($password),
                    'branch_id' => $matchedAgent->branch_id,
                    'profile_image' => $matchedAgent->profile_image,
                    'email_verified_at' => now(),
                ]);
            }
            if ($user) {
                $passwordMatches = true;
                $matchedAgent->updateQuietly(['user_id' => $user->id]);
            }
        }

        if (! $passwordMatches || ! $user) {
            throw ValidationException::withMessages([
                'identifier' => ['Invalid credentials provided. Please check your Agent ID / Email and password.'],
            ]);
        }

        // 2. Fetch linked Agent profile
        $agent = $user->agentProfile;
        if (! $agent) {
            return response()->json([
                'status' => 'inactive',
                'message' => 'Unauthorized: This login is not registered as a field agent.',
            ], 403);
        }

        // 3. Status gate check
        $status = strtolower((string) ($agent->status ?? 'inactive'));
        if ($status !== Agent::STATUS_ACTIVE && $status !== 'active') {
            return response()->json([
                'status' => $status,
                'message' => "Your agent account status is currently '".ucfirst($status)."'.",
                'rejection_reason' => $agent->rejection_reason ?? null,
            ], 403);
        }

        // 4. Generate Sanctum token
        $token = $user->createToken('pristine-agent-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'agent' => $this->formatAgentUser($agent),
        ]);
    }

    /**
     * Return authenticated agent profile.
     */
    public function me(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        return response()->json([
            'agent' => $this->formatAgentUser($agent),
        ]);
    }

    /**
     * Revoke current mobile device token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
