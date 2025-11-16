<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends BaseController
{
    public function login(LoginRequest $request, AuthService $service): JsonResponse
    {
        $token = $service->login($request->validated());

        if (!$token instanceof NewAccessToken) {
            return response()->json($token, 401);
        }

        return response()->json([
            'token' => $token->plainTextToken
        ]);
    }

    public function logout(Request $request, AuthService $service): JsonResponse
    {
        $service->logout($request);

        return response()->json(['message' => 'Logged out successfully']);
    }
}
