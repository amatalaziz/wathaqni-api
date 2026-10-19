<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(AuthRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request): JsonResponse {
                // Verification fields are never accepted from the client.
                $user = User::create($request->safe()->only(['name', 'phone', 'password']));

                return $this->tokenResponse($user, 201);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! User::where('phone', $request->validated('phone'))->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['phone' => ['The phone has already been taken.']]);
        }
    }

    public function login(AuthRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['phone', 'password']);
        $guard = Auth::guard('web');
        // Laravel's timeboxed credential validation does not create a session.
        if (! $guard->validate($credentials)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $user = $guard->getLastAttempted();
        $guard->getProvider()->rehashPasswordIfRequired($user, $credentials);

        return $this->tokenResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response()->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            throw new AuthenticationException('A bearer token is required.');
        }

        // Revoke only the current token, retaining its record.
        $token->forceFill(['expires_at' => now()])->save();

        return response()->json(['data' => null, 'message' => 'Logged out.'])
            ->header('Cache-Control', 'no-store');
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $expiresAt = now()->addDays(30);
        $token = $user->createToken('api-v1', ['profile:read'], $expiresAt);

        return (new UserResource($user))->additional([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ])->response()->setStatusCode($status)->header('Cache-Control', 'no-store');
    }
}
