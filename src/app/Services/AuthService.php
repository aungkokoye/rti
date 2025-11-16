<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function login(array $loginData): array | NewAccessToken
    {
        $user = User::where('email', $loginData['email'])->first();

        if (!$user) {
            return ['message' => 'Invalid credentials'];
        }

        if (!password_verify($loginData['password'], $user->password)) {
            return ['message' => 'Invalid credentials'];
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration', 120));

        return $user->createToken('api-token', ['*'], $expiresAt);
    }

    public function logout(Request $request): void
    {
        $request->user()->tokens()->delete();
    }

}
