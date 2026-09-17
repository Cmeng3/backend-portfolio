<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request): array
    {
        return ['token' => $request->session()->token()];
    }

    public function login(LoginRequest $request): array
    {
        if (! Auth::attempt([...$request->validated(), 'is_admin' => true])) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }
        $request->session()->regenerate();

        return ['data' => ['name' => $request->user()->name, 'email' => $request->user()->email]];
    }

    public function me(Request $request): array
    {
        return ['data' => ['name' => $request->user()->name, 'email' => $request->user()->email]];
    }

    public function logout(Request $request): mixed
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
