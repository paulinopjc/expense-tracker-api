<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleTokenVerifier
{
    public function verify(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return null;
        }

        $response = Http::timeout(5)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (($payload['aud'] ?? null) !== $clientId) {
            return null;
        }

        $iss = $payload['iss'] ?? null;
        if ($iss !== 'accounts.google.com' && $iss !== 'https://accounts.google.com') {
            return null;
        }

        if (($payload['email_verified'] ?? 'false') !== 'true' && ($payload['email_verified'] ?? false) !== true) {
            return null;
        }

        if (! isset($payload['email'])) {
            return null;
        }

        return [
            'email' => $payload['email'],
            'sub' => $payload['sub'] ?? null,
            'name' => $payload['name'] ?? $payload['email'],
        ];
    }
}
