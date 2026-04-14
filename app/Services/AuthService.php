<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private GoogleTokenVerifier $googleVerifier
    ) {}

    public function loginWithGoogle(string $idToken): array
    {
        $payload = $this->googleVerifier->verify($idToken);

        if (! $payload) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid Google ID token.'],
            ]);
        }

        $user = User::where('email', $payload['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['This email is not authorized to sign in. Ask an administrator to add you.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been disabled.'],
            ]);
        }

        if (! $user->google_id && $payload['sub']) {
            $user->update(['google_id' => $payload['sub']]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
