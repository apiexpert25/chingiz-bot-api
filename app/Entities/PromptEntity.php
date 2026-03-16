<?php

namespace App\Entities;

use App\Models\Prompt;

class PromptEntity
{
    private Prompt $prompt;

    public function __construct(Prompt $prompt)
    {
        $this->prompt = $prompt;
    }

    public static function make(Prompt $prompt): self
    {
        return new self($prompt);
    }

    /**
     * Get the singleton prompt (always record with ID 1)
     */
    public static function get(): self
    {
        $prompt = Prompt::first();

        if (!$prompt) {
            $prompt = Prompt::create(['content' => 'Default prompt']);
        }

        return self::make($prompt);
    }

    public static function update(string $content): void
    {
        $prompt = Prompt::first();
        if ($prompt) {
            $prompt->update(['content' => $content]);
        } else {
            Prompt::create(['content' => $content]);
        }
    }

    public function getContent(): string
    {
        return $this->prompt->content;
    }

    public function getId(): int
    {
        return $this->prompt->id;
    }
}
