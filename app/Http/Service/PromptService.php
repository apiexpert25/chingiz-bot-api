<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromptService
{
    private const BASE_PROMPT = <<<'PROMPT'
Сгенерируй теплое, живое голосовое напутствие на день. Тон дружелюбный, поддерживающий, вдохновляющий, как будто говорит личный наставник. Текст должен звучать естественно для устной речи, без канцелярита и без перечисления вопросов. Не упоминай анкету, вопросы и источник данных. Обращайся к человеку на «ты». Объем — примерно 40–70 секунд звучания.

Сделай речь персонализированной на основе данных ниже: используй факты, интересы, занятия, предпочтения и любые личные детали из ответов. Вплетай их в текст естественно — через образы, метафоры и мягкие тематические отсылки. Не перечисляй факты напрямую — интегрируй их в мотивацию и поддержку. Свяжи напутствие с образом дня, движения вперед и личной сильной стороны человека.

Добавь:
— ощущение личного обращения
— энергию старта дня
— уверенность и спокойный фокус
— короткое воодушевляющее завершение

Избегай:
— упоминания формата вопросов и ответов
— сухого пересказа данных
— пафосных лозунгов
— слишком длинных и сложных предложений

Данные о человеке:
PROMPT;

    /**
     * Форматирует JSON-строку с вопросами/ответами в читаемый текст.
     */
    private function formatAnswers(string $itemsJson): string
    {
        $items = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            return '';
        }

        $lines = [];

        foreach ($items as $index => $item) {
            $number = $index + 1;
            $lines[] = "Вопрос #{$number} {$item['question']}";
            $lines[] = "Ответ - {$item['answer']}";
        }

        return implode("\n", $lines);
    }

    public function generatePrompt(string $answersList): string
    {


        $formattedAnswers = $this->formatAnswers($answersList);

        $prompt = self::BASE_PROMPT . "\n" . $formattedAnswers;

        $textToSpeech = $this->askAI($prompt);
        Log::info($textToSpeech);
        return $textToSpeech;
    }

    /**
     * Отправляет промт в Timeweb AI Agent и возвращает сгенерированный текст.
     *
     * @throws \Exception
     */
    public function askAI(string $prompt): string
    {
        $token = config('services.timeweb.token');
        $agentId = config('services.timeweb.agent_id');
        $url = "https://agent.timeweb.cloud/api/v1/cloud-ai/agents/{$agentId}/call";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-proxy-source' => '',
            'Content-Type' => 'application/json',
        ])->post($url, [
                    'message' => $prompt,
                    'parent_message_id' => '',
                    'file_ids' => []
                ]);

        if ($response->failed()) {
            Log::error('Timeweb AI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Timeweb AI API request failed: ' . $response->body());
        }


        Log::info('Timeweb AI response', ['body' => $response->json()]);

        $data = $response->json();



        if (isset($data['message'])) {
            return $data['message'];
        }

        // Если это стриминг или другая структура, возможно потребуется адаптация.
        // Пока возвращаем JSON строку если не нашли message, чтобы увидеть что пришло.
        return $data['message'] ?? $data['answer'] ?? json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
