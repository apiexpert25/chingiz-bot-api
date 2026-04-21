<?php

namespace App\Jobs;

use App\Entities\SurveyEntity;
use App\Entities\VoiceMessagesEntity;
use App\Http\Service\ElevenlabsService;
use App\Http\Service\PromptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
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
            $prompt = $promptService->buildApiPrompt($answersList);
            $prompt = $promptService->askAI($prompt);

            $service = new ElevenlabsService();
            $audioContent = $service->textToSpeech($prompt);

            if (!$audioContent) {
                throw new \Exception('Не удалось получить аудиоконтент от ElevenLabs.');
            }

            $baseName = 'voice_' . $this->voice->getTelegramId() . '_' . time();
            $tempDir = storage_path('app/public/voices/tmp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $mp3Path = $tempDir . '/' . $baseName . '.mp3';
            $oggPath = $tempDir . '/' . $baseName . '.ogg';

            file_put_contents($mp3Path, $audioContent);

            $result = Process::timeout(300)->run([
                'ffmpeg',
                '-y',
                '-i', $mp3Path,
                '-c:a', 'libopus',
                '-b:a', '48k',
                '-vbr', 'on',
                '-application', 'voip',
                $oggPath,
            ]);

            if ($result->failed()) {
                throw new \Exception(
                    'Не удалось конвертировать mp3 в ogg: ' . $result->errorOutput()
                );
            }

            $oggContent = file_get_contents($oggPath);

            if ($oggContent === false) {
                throw new \Exception('Не удалось прочитать итоговый ogg-файл.');
            }

            $fileName = 'voices/' . $baseName . '.ogg';
            Storage::disk('public')->put($fileName, $oggContent);

            $fileUrl = Storage::disk('public')->url($fileName);

            $this->voice->setCompletedState($fileUrl);

            @unlink($mp3Path);
            @unlink($oggPath);

        } catch (\Throwable $e) {

            $this->voice->setStatusError($e->getMessage());
            Log::error('Ошибка при генерации голосового сообщения: ' . $e->getMessage(), [
                'chat_id' => $this->voice->getTelegramId(),
                'trace' => $e->getTraceAsString(),
            ]);

        }
    }
}
