<?php

namespace App\Http\Controllers;

use App\Entities\SurveyEntity;
use App\Entities\VoiceMessagesEntity;
use App\Http\Requests\CreateVoiceRequest;
use App\Http\Requests\SurveyCreateRequest;
use App\Http\Service\ElevenlabsService;
use App\Http\Service\PromptService;
use App\Jobs\ProcessVoiceMessageJob;
use App\Models\Survey;
use App\Models\VoiceMessages;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function survey(SurveyCreateRequest $request)
    {
        $data = $request->validated();

        SurveyEntity::updateOrCreate($data['telegram_id'], json_encode($data['items']));

        return response()->json([], 200);
    }

    public function voice(CreateVoiceRequest $request)
    {

        $data = $request->validated();
        $telegramId = $data['telegram_id'];

        $existingVoiceToday = VoiceMessagesEntity::findSentVoiceToday($telegramId);


        if ($existingVoiceToday !== null) {
            return response()->json([
                'telegram_id' => $telegramId,
                'result' => 'error',
                'reason' => 'Голосовое уже было отправлено сегодня'
            ], 400);
        }


        $answers = SurveyEntity::findAnswersByTelegramId($telegramId);

        if ($answers === null) {
            return response()->json([
                'telegram_id' => $telegramId,
                'result' => 'error',
                'reason' => 'Пользователь не заполнил анкету'
            ], 400);
        }


        $voice = VoiceMessagesEntity::create($telegramId);

        ProcessVoiceMessageJob::dispatch($voice);

        return response()->json([
            'telegram_id' => $voice->getTelegramId(),
            'voice_id' => $voice->getVoiceId(),
            'voice_status_link' => config('app.url') . '/api/voice/' . $voice->getVoiceId(),
            'voice_download_link' => $voice->findVoiceDownloadLink(),
            'voice_status' => $voice->getStatus(),
        ], 200);
    }

    public function test(int $tegramId)
    {
        $tgId = $tegramId;

        $answersList = SurveyEntity::getAnswersByTelegramId($tgId)->getItems();

        $promptService = new PromptService();

        $prompt = $promptService->generatePrompt($answersList);


            $service = new ElevenlabsService();

            $audioContent = $service->textToSpeech($prompt);


            $tempPath = storage_path("app/voices/voice_{$tegramId}_" . time() . ".mp3");

        Log::info($tempPath);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $audioContent);

//            @unlink($tempPath);




            $message = "✅ Готово!\nЕсли хочешь озвучить ещё один текст — жми кнопку ниже 👇";

            $keyboard = Keyboard::make()
                ->button('💫 Озвучить текст')
                ->action('voiceMessage');

            Telegraph::chat($tegramId)
                ->message($message)
                ->keyboard($keyboard)
                ->send();
    }

    public function getVoice(string $voice_id)
    {
        $voice = VoiceMessagesEntity::findByVoiceId($voice_id);

        if ($voice === null) {
            return response()->json([
                'error' => 'Voice not found'
            ], 400);
        }


        return response()->json([
            'telegram_id' => $voice->getTelegramId(),
            'voice_id' => $voice->getVoiceId(),
            'voice_status_link' => config('app.url') . '/api/voice/' . $voice->getVoiceId(),
            'voice_download_link' => $voice->findVoiceDownloadLink(),
            'voice_status' => $voice->getStatus(),
        ], 200);
    }
}
