<?php

namespace App\Models;

use Illuminate\Http\UploadedFile;
use TelegramBot\Api\BotApi;
use CURLFile;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

/**
 * Class Telegram
 * @package App\Models
 */
class Telegram
{
    private const MARKDOWN = 'Markdown';

    /**
     * @var string
     */
    private string $botToken;

    /**
     * @var int
     */
    private int $botId;

    /**
     * Telegram constructor.
     */
    public function __construct()
    {
        $this->botToken = config('telegram.telegram-bot-api.token');
        $this->botId = config('telegram.telegram-bot-api.id');
    }

    /**
     * @param array $validated
     * @param int $id
     * @param array|null $files
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function sendToTelegram(array $validated, int $id, ?array $files): void
    {
        (new BotApi($this->botToken))->sendMessage(
            $this->botId,
            $this->getMessage($validated, $id),
            self::MARKDOWN
        );

        if (!empty($files)) {
            foreach ($files as $file) {
                (new BotApi($this->botToken))->sendDocument($this->botId, $this->getCurlFile($file));
            }
        }
    }

    /**
     * @param array $validated
     * @param int $id
     * @return string
     */
    private function getMessage(array $validated, int $id): string
    {
        return sprintf("*Оставлена новая заявка #{$id}*

*Имя:* %s
*Почта:* %s
*Телефон:* %s
*Город:* %s
*Сообщение:* %s
*Файл:* %s
",
            $validated['name'] ? trim($validated['name']) : 'Не задано',
            $validated['email'] ? trim($validated['email']) : 'Не задано',
            $validated['phone'] ? trim($validated['phone']) : 'Не задано',
            $validated['city'] ? trim($validated['city']) : 'Не задано',
            $validated['message'] ? trim($validated['message']) : 'Не задано',
            !empty($files) ? 'Прикреплены ниже' : 'Не задано',
        );
    }

    /**
     * @param UploadedFile $file
     * @return CURLFile
     */
    private function getCurlFile(UploadedFile $file): CURLFile
    {
        return new CURLFile($file->path(), null, $file->getClientOriginalName());
    }

    /**
     * @param $validated
     * @return array
     */
    public function prepareRequestWithFiles(&$validated): array
    {
        $files = $validated['files'];
        $validated['file'] = json_encode(
            array_map(fn ($item) => $item->getClientOriginalName(), $validated['files']),
            JSON_UNESCAPED_UNICODE
        );
        unset($validated['files']);
        return $files;
    }
}
