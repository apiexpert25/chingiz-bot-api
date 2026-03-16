<?php

namespace App\Http\Webhooks;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Stringable;
use App\Http\Service\PromptService;
use App\Http\Service\ElevenlabsService;
use Illuminate\Support\Facades\Log;

class MainWebhookHandler extends WebhookHandler
{
    /**
     * Handle the /start command
     */
    public function start(): void
    {
        Log::info('Telegraph start command received', ['chat_id' => $this->chat->chat_id]);

        $this->chat->message('Обрабатываю запрос... Ожидайте голосового сообщения.')->send();

        try {
            /** @var PromptService $promptService */
            $promptService = app(PromptService::class);

            $prompt = $promptService->getLatestPrompt();

            if (!$prompt) {
                $this->chat->message('Промпт не найден в базе данных.')->send();
                return;
            }

            $llmResponse = $promptService->askAI($prompt);

            /** @var ElevenlabsService $elevenlabsService */
            $elevenlabsService = app(ElevenlabsService::class);

            if (!$llmResponse) {
                $this->chat->message('Не удалось получить ответ от LLM.')->send();
                return;
            }

            Log::info('LLM response', ['response' => $llmResponse]);


            $audioContent = $elevenlabsService->textToSpeech($llmResponse);

            if (!$audioContent) {
                $this->chat->message('Не удалось сгенерировать голосовое сообщение.')->send();
                return;
            }

            // Save audio to a temporary file in storage
            $tempFileName = 'voice_' . time() . '.mp3';
            $tempFilePath = storage_path('app/public/' . $tempFileName);

            // Ensure the directory exists
            if (!file_exists(storage_path('app/public'))) {
                mkdir(storage_path('app/public'), 0755, true);
            }

            file_put_contents($tempFilePath, $audioContent);

            $this->chat->voice($tempFilePath)->send();

            // Delete the file after sending to keep storage clean
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

        } catch (\Exception $e) {
            Log::error('Error in Telegraph start command', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->chat->message('Произошла ошибка при обработке: ' . current(explode("\n", $e->getMessage())))->send();
        }
    }
}
