<?php

namespace App\Entities;

use App\Models\Survey;

class SurveyEntity
{

    private Survey $survey;


    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }


    public static function make(Survey $survey): self
    {
        return new self($survey);
    }

    public static function updateOrCreate(int $telegram_id, string $items): void
    {
        Survey::updateOrCreate(
            ['telegram_id' => $telegram_id],
            ['items' => $items]
        );
    }

    public static function getAnswersByTelegramId(int $tgId): self
    {
        $answers = Survey::where('telegram_id', $tgId)->orderBy('created_at', 'desc')->first();

        return self::make($answers);
    }


    public function getTelegramId(): int
    {
        return $this->survey->telegram_id;
    }


    public function getItems(): string
    {
        return $this->survey->items;
    }


    public static function findAnswersByTelegramId(int $tgId)
    {
        $answers = Survey::where('telegram_id', $tgId)->orderBy('created_at', 'desc')->first();

        if ($answers === null) {
            return null;
        }

        return self::make($answers);
    }


    public function getSurvey(): Survey
    {
        return $this->survey;
    }

}
