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
use Illuminate\Support\Facades\Storage;

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
        try {
            $promptService = new PromptService();

            $prompt = $promptService->generatePrompt($answersList);

            $service = new ElevenlabsService();

            $audioContent = $service->textToSpeech($prompt);

            if (!$audioContent) {
                throw new \Exception('Не удалось получить аудиоконтент от ElevenLabs.');
            }

            $fileName = "voices/voice_{$this->voice->getTelegramId()}_" . time() . ".mp3";
            Storage::disk('public')->put($fileName, $audioContent);

            $fileUrl = url(Storage::url($fileName));

            $this->voice->setCompletedState($fileUrl);

        } catch (\Throwable $e) {

            $this->voice->setStatusError($e->getMessage());
            Log::error('Ошибка при генерации голосового сообщения: ' . $e->getMessage(), [
                'chat_id' => $this->voice->getTelegramId(),
                'trace' => $e->getTraceAsString(),
            ]);

        }
    }
}
