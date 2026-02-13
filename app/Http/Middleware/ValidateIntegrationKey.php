<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateIntegrationKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->input('integration_key');
        $validKey = env('INTEGRATION_KEY');

        // Check if key exists and matches (cast to string to avoid type mismatch)
        if (empty($providedKey) || (string) $providedKey !== (string) $validKey) {
            return response()->json([
                'error' => 'Invalid or missing integration key'
            ], 401);
        }

        return $next($request);
    }
}
