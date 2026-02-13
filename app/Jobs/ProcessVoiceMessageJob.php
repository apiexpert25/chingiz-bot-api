<?php

namespace App\Jobs;

use App\Entities\ChatStateEntity;
use App\Entities\SurveyEntity;
use App\Entities\VoiceMessagesEntity;
use App\Http\Service\ElevenlabsService;
use App\Http\Service\PromptService;
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


    private VoiceMessagesEntity $voice;


    public function __construct(VoiceMessagesEntity $voice)
    {
        $this->voice = $voice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tgId = $this->voice->getTelegramId();

        $answersList = SurveyEntity::getAnswersByTelegramId($tgId)->getItems();

        $promptService = new PromptService();

        $prompt = $promptService->generatePrompt($answersList);

        try {
            $service = new ElevenlabsService();

            $audioContent = $service->textToSpeech($prompt);

            if (!$audioContent) {
                throw new \Exception('Не удалось получить аудиоконтент от ElevenLabs.');
            }

            $tempPath = storage_path("app/voices/voice_{$this->voice->getTelegramId()}_" . time() . ".mp3");


            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $audioContent);
            @unlink($tempPath);

            $this->voice->updateInCompletedStatus();

        } catch (\Throwable $e) {

            Log::error('Ошибка при генерации голосового сообщения: ' . $e->getMessage(), [
                'chat_id' => $this->voice->getTelegramId(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
