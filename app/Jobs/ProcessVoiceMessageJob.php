<?php

namespace App\Jobs;

use App\Entities\ChatStateEntity;
use App\Http\Service\ElevenlabsService;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVoiceMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    protected int $chatId;
    protected string $text;

    public function __construct(int $chatId, string $text)
    {
        $this->chatId = $chatId;
        $this->text = $text;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chatUser = ChatStateEntity::findByChatId($this->chatId);

        if (!$chatUser) {
            return;
        }

        try {
            $service = new ElevenlabsService();

            $audioContent = $service->textToSpeech($this->text);

            if (!$audioContent) {
                throw new \Exception('Не удалось получить аудиоконтент от ElevenLabs.');
            }

            $tempPath = storage_path("app/voices/voice_{$this->chatId}_" . time() . ".mp3");


            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $audioContent);



            Telegraph::chat($this->chatId)
                ->voice($tempPath)
                ->send();

            @unlink($tempPath);

            $chatUser->updateInCompletedStatus();

            $message = "✅ Готово!\nЕсли хочешь озвучить ещё один текст — жми кнопку ниже 👇";

            $keyboard = Keyboard::make()
                ->button('💫 Озвучить текст')
                ->action('voiceMessage');

            Telegraph::chat($this->chatId)
                ->message($message)
                ->keyboard($keyboard)
                ->send();

        } catch (\Throwable $e) {

            Log::error('Ошибка при генерации голосового сообщения: ' . $e->getMessage(), [
                'chat_id' => $this->chatId,
                'text' => $this->text,
                'trace' => $e->getTraceAsString(),
            ]);

            $chatUser->updateInStartStatus();


            Telegraph::chat($this->chatId)
                ->message('Произошла ошибка при генерации голосового сообщения. Попробуйте снова, пожалуйста.')
                ->send();
        }
    }
}
