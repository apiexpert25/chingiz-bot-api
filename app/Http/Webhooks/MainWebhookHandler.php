<?php

namespace App\Http\Webhooks;

use App\Entities\ChatStateEntity;
use App\Jobs\ProcessVoiceMessageJob;
use App\Models\ChatState;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;

class MainWebhookHandler extends WebhookHandler
{

//    public function start(): void
//    {
//        Log::info('gfdgdfg');
//        $this->reply('fgdfgf');
//    }
    public function handleMessage(): void
    {

        $chatId = $this->chat->chat_id;
        $message = $this->message->text();
        $user = ChatStateEntity::firstOrCreate(
            $chatId,
        );

        if ($user->inStartStatus() || $user->inCompletedStatus()){

            $this->sendGreeting($user);
        }

        if ($user->inWaitingTextStatus()){
            $this->convertTextToSpeeach($user, $message);
            return;
        }

        if ($user->isPendingStatus()){
            $this->sendWaitMessage($user);

        }
    }


    public function sendGreeting(ChatStateEntity $user): void
    {

        $message = '👋 Привет, я бот озвучки! Нажми на кнопку, чтобы начать 👇';

        $keyboard = Keyboard::make()->button('💫 Озвучить сообщение')->action('voiceMessage');


        Telegraph::chat($user->getChatId())->message($message)->keyboard($keyboard)->send();
    }

    public function voiceMessage(): void
    {
        $this->reply('');
        $chatId = $this->chat->chat_id;
        $user = ChatStateEntity::findByChatId($chatId);


        if ($user === null){

            $this->handleMessage();
        }

        $user->updateWaitingTextStatus();

        $message = '💬 Напиши свой текст следующим сообщением, чтобы мы его озвучили (максимум 300 символов)';

        Telegraph::chat($user->getChatId())->message($message)->send();

        return;

    }

    public function convertTextToSpeeach(ChatStateEntity $user, string $message): void
    {
        $message = trim($message);

        if (empty($message)) {
            Telegraph::chat($user->getChatId())
                ->message('❗ Ошибка: сообщение не может быть пустым. Пожалуйста, попробуй ещё раз.')
                ->send();
            return;
        }

        if (mb_strlen($message) > 300) {
            Telegraph::chat($user->getChatId())
                ->message('❗ Ошибка: сообщение слишком длинное. Максимум 300 символов.')
                ->send();
            return;
        }

        $user->updatePendingStatus();

        ProcessVoiceMessageJob::dispatch($user->getChatId(), $message);

        Telegraph::chat($user->getChatId())
            ->message('💾 Обработка началась, пожалуйста, подождите...')
            ->send();

        return;
    }

    public function sendWaitMessage(ChatStateEntity $user): void
    {

        Telegraph::chat($user->getChatId())
            ->message('🕜 Сообщение еще в обработке, пожалуйста подождите 😑')
            ->send();
    }

}
