<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\VoiceMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function survey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'integration_key' => 'required|string',
            'telegram_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.question' => 'required|string',
            'items.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        Survey::updateOrCreate(
            ['telegram_id' => $data['telegram_id']],
            ['items' => $data['items']]
        );

        return response()->json([], 200);
    }

    public function voice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'integration_key' => 'required|string',
            'telegram_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $telegramId = $data['telegram_id'];

        $existingVoiceToday = VoiceMessages::where('telegram_id', $telegramId)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($existingVoiceToday) {
            return response()->json([
                'telegram_id' => $telegramId,
                'result' => 'error',
                'reason' => 'Голосовое уже было отправлено сегодня'
            ], 400);
        }

        $voice = VoiceMessages::create([
            'telegram_id' => $telegramId,
            'status' => 'started',
        ]);

        return response()->json([
            'telegram_id' => $voice->telegram_id,
            'voice_id' => $voice->voice_id,
            'voice_status_link' => $voice->voice_status_link,
            'voice_download_link' => $voice->voice_download_link,
            'voice_status' => $voice->status,
        ], 200);
    }

    public function getVoice(string $voice_id)
    {
        $voice = VoiceMessages::where('voice_id', $voice_id)->first();

        if (!$voice) {
            return response()->json([
                'error' => 'Voice not found'
            ], 400);
        }

        return response()->json([
            'telegram_id' => $voice->telegram_id,
            'voice_id' => $voice->voice_id,
            'voice_status_link' => $voice->voice_status_link,
            'voice_download_link' => $voice->voice_download_link,
            'voice_status' => $voice->status,
        ], 200);
    }
}
