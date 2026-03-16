<?php

namespace App\Http\Webhooks;

use App\Jobs\ProcessStartCommandJob;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MainWebhookHandler extends WebhookHandler
{
    /**
     * Handle the /start command
     */
    public function start(): void
    {
        $chatId = $this->chat->chat_id;
        $lockKey = "processing_chat_{$chatId}";

        Log::info('Telegraph start command received', ['chat_id' => $chatId]);

        // Check if already processing
        if (Cache::has($lockKey)) {
            $this->chat->message('Я уже обрабатываю ваш запрос. Пожалуйста, подождите завершения.')->send();
            return;
        }

        // Set lock for 5 minutes (timeout for LLM/TTS)
        Cache::put($lockKey, true, now()->addMinutes(5));

        $this->chat->message('Обрабатываю запрос... Ожидайте голосового сообщения.')->send();

        // Dispatch Job
        ProcessStartCommandJob::dispatch($this->chat);
    }
}
