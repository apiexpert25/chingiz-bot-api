<?php

namespace App\Http\Controllers;

use App\Entities\SurveyEntity;
use App\Entities\VoiceMessagesEntity;
use App\Http\Requests\CreateVoiceRequest;
use App\Http\Requests\SurveyCreateRequest;
use App\Jobs\ProcessVoiceMessageJob;

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

        $canCreateMultipleVoicesDaily = config('app.admin_list');

        $existingVoiceToday = VoiceMessagesEntity::findSentVoiceToday($telegramId);

        if ($existingVoiceToday !== null && !in_array($telegramId, $canCreateMultipleVoicesDaily)) {
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

    public function checkVoiceStatus(string $voice_id)
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
    public function statistics()
    {
       $array = VoiceMessagesEntity::getStatistics();
       return response()->json($array, 200);
    }
    public function checkSurvey(int $telegramId)
    {
        $answers = SurveyEntity::findAnswersByTelegramId($telegramId);

        if ($answers === null) {
            return response()->json([
                'telegram_id' => $telegramId,
                'survey_is_filled' => false
            ], 200);
        }
        return response()->json([
            'telegram_id' => $telegramId,
            'survey_is_filled' => true,
            'answers' => json_decode($answers->getItems())
        ], 200);
    }
    public function checkVoice(int $telegramId)
    {
        $canCreateMultipleVoicesDaily = config('app.admin_list');
        $existingVoiceToday = VoiceMessagesEntity::findSentVoiceToday($telegramId);

        if ($existingVoiceToday === null || in_array($telegramId, $canCreateMultipleVoicesDaily)) {
            return response()->json([
                'telegram_id' => $telegramId,
                'voice_was_sent' => false
            ], 200);
        }
        $voice_id = $existingVoiceToday->getVoiceId();
        return response()->json([
            'telegram_id' => $telegramId,
            'voice_was_sent' => true,
            'voice_id' => $voice_id,
            'voice_status_link' => config('app.url') . '/api/voice/' . $voice_id,
            'voice_download_link' => $existingVoiceToday->findVoiceDownloadLink(),
            'voice_status' => $existingVoiceToday->getStatus(),
        ], 200);
    }
}
