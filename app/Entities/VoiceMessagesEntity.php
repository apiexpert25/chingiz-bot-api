<?php

namespace App\Entities;

use App\Models\VoiceMessages;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VoiceMessagesEntity
{


    private const STATUS_STARTED = 'started';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_ERROR = 'error';


    private VoiceMessages $voiceMessages;

    public function __construct(VoiceMessages $voiceMessages)
    {
        $this->voiceMessages = $voiceMessages;
    }


    public static function make(VoiceMessages $voiceMessages): VoiceMessagesEntity
    {
        return new self($voiceMessages);
    }


    public static function findSentVoiceToday(int $telegramId): ?VoiceMessagesEntity
    {
        $voice = VoiceMessages::where('telegram_id', $telegramId)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($voice === null) {
            return null;
        }

        return self::make($voice);
    }

    public static function create(int $telegramId): VoiceMessagesEntity
    {
        $voice = VoiceMessages::create([
            'telegram_id' => $telegramId,
            'voice_id' => (string)Str::uuid(),
            'status' => self::STATUS_STARTED,
        ]);

        return self::make($voice);
    }

    public static function findByVoiceId(string $voice_id)
    {
        $voice = VoiceMessages::where('voice_id', $voice_id)->first();
        if ($voice === null) {
            return null;
        }

        return self::make($voice);
    }

    public function getTelegramId(): int
    {
        return $this->voiceMessages->telegram_id;
    }

    public function getVoiceId(): string
    {
        return $this->voiceMessages->voice_id;
    }


    public function getStatus(): string
    {
        return $this->voiceMessages->status;
    }


    public function getVoiceMessages(): VoiceMessages
    {
        return $this->voiceMessages;
    }

    public function findVoiceDownloadLink()
    {
        $link = $this->voiceMessages->voice_download_link;

        if ($link == null) {
            return null;
        }

        return $link;
    }

    public function updateInCompletedStatus(): void
    {
        $this->voiceMessages->status = self::STATUS_COMPLETED;
        $this->voiceMessages->save();
    }

    public function setCompletedState(string $path): void
    {
        $this->voiceMessages->voice_download_link = $path;
        $this->voiceMessages->status = self::STATUS_COMPLETED;
        $this->voiceMessages->save();
    }

    public function setStatusError(string $getMessage): void
    {
        $this->voiceMessages->status = self::STATUS_ERROR;
        $this->voiceMessages->error_message = $getMessage;
    }

    public static function getStatistics(): array
    {
        $since = Carbon::today()->subMonth();

        $voices = VoiceMessages::query()
            ->whereDate('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        $statistics = $voices
            ->toBase()
            ->groupBy(fn ($voice) => Carbon::parse($voice->created_at)->format('Y-m-d'))
            ->map(fn ($group, $date) => [
                'date' => $date,
                'voices_requested' => $group->count(),
                'voices_sent' => $group->where('status', self::STATUS_COMPLETED)->count(),
            ])
            ->values()
            ->all();

        return ['statistics' => $statistics];
    }
}
