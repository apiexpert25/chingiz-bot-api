<?php

namespace App\Jobs;

use App\Http\Service\ElevenlabsService;
use App\Http\Service\PromptService;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessStartCommandJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes

    public function __construct(protected TelegraphChat $chat) {
    }

    public function handle(): void
    {
        $chatId = $this->chat->chat_id;
        $lockKey = "processing_chat_{$chatId}";

        try {
            /** @var PromptService $promptService */
            $promptService = app(PromptService::class);

            $prompt = $promptService->getLatestPrompt();

            if (!$prompt) {
                $this->chat->html('Промпт не найден в базе данных.')->send();
                return;
            }

            $llmResponse = $promptService->askAI($prompt);

            /** @var ElevenlabsService $elevenlabsService */
            $elevenlabsService = app(ElevenlabsService::class);

            if (!$llmResponse) {
                $this->chat->html('Не удалось получить ответ от LLM.')->send();
                return;
            }

            Log::info('LLM response', ['response' => $llmResponse]);

            $audioContent = $elevenlabsService->textToSpeech($llmResponse);

            if (!$audioContent) {
                $this->chat->html('Не удалось сгенерировать голосовое сообщение.')->send();
                return;
            }

            // Save audio to a temporary file in storage
            $tempFileName = 'voice_' . $chatId . '_' . time() . '.mp3';
            $tempFilePath = storage_path('app/public/' . $tempFileName);

            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0755, true);
            }

            file_put_contents($tempFilePath, $audioContent);

            $this->chat->voice($tempFilePath)->send();

            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

        } catch (\Exception $e) {
            Log::error('Error in ProcessStartCommandJob', [
                'chat_id' => $chatId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->chat->html('Произошла ошибка при асинхронной обработке: ' . current(explode("\n", $e->getMessage())))->send();
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::forget("processing_chat_{$this->chat->chat_id}");
    }
}
