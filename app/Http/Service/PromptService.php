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

    private const OPENROUTER_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const OPENROUTER_MODEL = 'google/gemma-3n-e2b-it:free';

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

        return $textToSpeech;
    }

    /**
     * Отправляет промт в OpenRouter API (бесплатная модель) и возвращает сгенерированный текст.
     *
     * @throws \Exception
     */
    public function askAI(string $prompt): string
    {
        $apiKey = config('services.openrouter.api_key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::OPENROUTER_URL, [
                    'model' => self::OPENROUTER_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

        if ($response->failed()) {
            Log::error('OpenRouter API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenRouter API request failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
