<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatTokenController extends Controller
{
    use ApiResponse;

    /**
     * Mint a short-lived HMAC token binding the caller to users.ai_uuid
     * for Dispute AI /chat and session routes.
     */
    public function mint(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->ai_uuid) {
            return $this->sendError('AI identity missing for user', 422);
        }

        $secret = config('services.ai.chat_token_secret');
        if (!$secret) {
            Log::warning('AI_CHAT_TOKEN_SECRET unset — issuing unsigned local token');
            $secret = 'local-dev-insecure-secret';
        }

        $ttl = (int) config('services.ai.chat_token_ttl_seconds', 3600);
        $exp = time() + max(60, $ttl);
        $payload = [
            'sub' => $user->ai_uuid,
            'uid' => $user->id,
            'exp' => $exp,
        ];
        $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $body, $secret);

        return $this->sendSuccess([
            'token' => $body . '.' . $sig,
            'user_id' => $user->ai_uuid,
            'expires_at' => $exp,
        ], 'Dispute chat token minted');
    }
}
