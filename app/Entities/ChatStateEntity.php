<?php

namespace App\Entities;

use App\Models\ChatState;

class ChatStateEntity
{
    private const IN_START = 'in_start';
    private const PENDING = 'pending';

    private const WAITING_TEXT = 'waiting_text';

    private const COMPLETED = 'completed';

    private ChatState $chatState;
    private function  __construct(ChatState $chatState)
    {
        $this->chatState = $chatState;
    }

    public static function fromModel(ChatState $chatState): ChatStateEntity
    {
        return new self($chatState);
    }

    public static function findByChatId(int $chatId): ?ChatStateEntity
    {
        $chatState = ChatState::query()->where('chat_id', $chatId)->first();

        if (!$chatState) {
            return null;
        }

        return self::fromModel($chatState);
    }

    public function isPendingStatus(): bool
    {
        return $this->chatState->status === self::PENDING;
    }

    public function inStartStatus(): bool
    {
        return $this->chatState->status === self::IN_START;
    }


    public function inCompletedStatus(): bool
    {
        return $this->chatState->status === self::COMPLETED;
    }

    public function inWaitingTextStatus(): bool
    {
        return $this->chatState->status === self::WAITING_TEXT;
    }


    public static function firstOrCreate(int $chatId): ChatStateEntity
    {
        $chatState = ChatState::firstOrCreate(
            ['chat_id' => $chatId],
            ['status' => self::IN_START]
        );

        return self::fromModel($chatState);
    }

    public function getChatState(): ChatState
    {
        return $this->chatState;
    }

    public function getChatId(): int
    {
        return $this->chatState->chat_id;
    }

    public function getStatus(): string
    {
        return $this->chatState->status;
    }

    public function getLastMessage(): string
    {
        return $this->chatState->last_message;
    }

    public function updatePendingStatus(): void
    {
        $this->chatState->status = self::PENDING;
        $this->chatState->update();
    }

    public function updateInStartStatus(): void
    {
        $this->chatState->status = self::IN_START;
        $this->chatState->update();
    }

    public function updateWaitingTextStatus(): void
    {
        $this->chatState->status = self::WAITING_TEXT;
        $this->chatState->update();
    }

    public function updateInCompletedStatus(): void
    {
        $this->chatState->status = self::COMPLETED;
        $this->chatState->update();
    }


}
