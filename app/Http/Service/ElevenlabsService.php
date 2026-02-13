<?php

namespace App\Http\Service;

use ElevenLabs\V1\SDK\Endpoint\AddVoiceV1VoicesAddPost;
use ElevenLabs\V1\SDK\Endpoint\TextToSpeechV1TextToSpeechVoiceIdPost;
use ElevenLabs\V1\SDK\Model\BodyTextToSpeechV1TextToSpeechVoiceIdPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevenlabsService
{
    protected $apiKey;
    protected string $baseUrl = 'https://api.elevenlabs.io/v1';
    protected string|null $voiceId = null;

    public function __construct()
    {
        $this->apiKey = config('services.elevenlabs.api_key');
        $this->voiceId = config('services.elevenlabs.voice_id');
    }

    public function createVoice($name, $audioFilePath, $description = '')
    {
        $response = Http::withHeaders([
            'xi-api-key' => $this->apiKey,
            'Content-Type' => 'multipart/form-data',
        ])->attach('files', file_get_contents($audioFilePath), basename($audioFilePath))
            ->post("{$this->baseUrl}/voices/add", [
                'name' => $name,
                'description' => $description,
                'model_id' => 'eleven_multilingual_v2'
            ]);

        return $response->successful() ? $response->json() : $response->status();
    }


    public function getVoices()
    {
        $response = Http::withHeaders([
            'xi-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/voices");

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }


    public function textToSpeech($text): ?string
    {
        Log::info('ElevenLabs TTS запрос', [
            'voice_id' => $this->voiceId,
            'text_length' => mb_strlen($text),
            'model_id' => 'eleven_v3',
        ]);

        $response = Http::withHeaders([
            'xi-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'audio/mpeg',
            'User-Agent' => 'Mozilla/5.0 (compatible; ChingizBot/1.0)',
        ])->timeout(30)->post("{$this->baseUrl}/text-to-speech/{$this->voiceId}", [
            'text' => $text,
            'model_id' => 'eleven_v3'
        ]);

        $statusCode = $response->status();
        $contentType = $response->header('Content-Type');

        Log::info('ElevenLabs TTS ответ', [
            'status_code' => $statusCode,
            'content_type' => $contentType,
            'body_length' => strlen($response->body()),
        ]);

        if (!$response->successful()) {
            Log::error('ElevenLabs TTS ошибка', [
                'status_code' => $statusCode,
                'headers' => $response->headers(),
                'body' => mb_substr($response->body(), 0, 2000),
            ]);
            return null;
        }

        // Проверяем что вернулся аудио-контент, а не JSON с ошибкой
        if (str_contains($contentType, 'application/json')) {
            Log::error('ElevenLabs вернул JSON вместо аудио', [
                'status_code' => $statusCode,
                'body' => mb_substr($response->body(), 0, 2000),
            ]);
            return null;
        }

        return $response->body();
    }



}
