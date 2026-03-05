<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PocketbaseService
{
    private string $baseUrl;
    private ?string $adminEmail;
    private ?string $adminPassword;

    public function __construct()
    {
        $this->baseUrl = config('services.pocketbase.url', 'http://127.0.0.1:8090');
        $this->adminEmail = config('services.pocketbase.email');
        $this->adminPassword = config('services.pocketbase.password');
    }

    private function getAuthToken(): ?string
    {
        if (!$this->adminEmail || !$this->adminPassword) {
            return null; // Assume public access if no credentials
        }

        $response = Http::post("{$this->baseUrl}/api/admins/auth-with-password", [
            'identity' => $this->adminEmail,
            'password' => $this->adminPassword,
        ]);

        if ($response->successful()) {
            return $response->json('token');
        }

        Log::error('Pocketbase Auth Error', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    public function getLatestPrompt(): ?string
    {
        $request = Http::timeout(10);

        $token = $this->getAuthToken();
        if ($token) {
            $request->withToken($token);
        }

        // Get the latest record from 'chingiz_prompts' collection, sorted by created descending
        $response = $request->get("{$this->baseUrl}/api/collections/chingiz_prompts/records", [
            'sort' => '-created',
            'perPage' => 1,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $records = $data['items'] ?? [];
            if (count($records) > 0) {
                return $records[0]['prompt_text'] ?? null;
            }
        } else {
            Log::error('Pocketbase Fetch Prompt Error', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return null;
    }
}
