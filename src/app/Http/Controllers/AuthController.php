<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class AuthController extends BaseController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        $user = User::where('email', $loginData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!password_verify($loginData['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration', 120));
        $token = $user->createToken('api-token', ['*'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
