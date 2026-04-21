<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Entities\PromptEntity;

class PromptService
{

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

    public function buildApiPrompt(string $answersList): string
    {
        $basePrompt = trim($this->getLatestPrompt());
        $formattedAnswers = trim($this->formatAnswers($answersList));

        return trim($basePrompt . "\n\n" . $formattedAnswers);
    }

    public function generatePrompt(string $answersList): string
    {

        $formattedAnswers = $this->formatAnswers($answersList);

        $prompt = $formattedAnswers;

        $textToSpeech = $this->askAI($prompt);
        Log::info($textToSpeech);
        return $textToSpeech;
    }

    /**
     * Получает актуальный промпт из базы данных.
     */
    public function getLatestPrompt(): string
    {
        $promptEntity = PromptEntity::get();
        return $promptEntity->getContent();
    }

    /**
     * Удаляет markdown code-block обёртки (```python, ```text и т.д.) из текста.
     */

    protected function stripMarkdownCodeBlocks(string $text): string
    {
        $text = preg_replace('/^```\w*\s*\n?/', '', $text);
        $text = preg_replace('/\n?```\s*$/', '', $text);

        return trim($text);
    }
    /**
     * Отправляет промпт в Timeweb AI Agent и возвращает сгенерированный текст.
     *
     * @throws \Exception
     */
    public function askAI(string $prompt): string
    {
        $token = config('services.timeweb.token');
        $agentId = config('services.timeweb.agent_id');
        $url = "https://agent.timeweb.cloud/api/v1/cloud-ai/agents/{$agentId}/call";

        $response = Http::timeout(300)->withHeaders([
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

        Log::info("data['message']", ['message' => $data['message'] ?? 'not set']);

        if (isset($data['message'])) {
            return $this->stripMarkdownCodeBlocks($data['message']);
        }
        // Если это стриминг или другая структура, возможно потребуется адаптация.
        // Пока возвращаем JSON строку если не нашли message, чтобы увидеть что пришло.
        return $data['message'] ?? $data['answer'] ?? json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
