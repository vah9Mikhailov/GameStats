<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TelegramBot extends Model
{
    use HasFactory;

    /**
     * @param Collection $messageInfo
     * @param string $text
     * @param array $data
     * @param array $page
     * @return array
     */
    public function createInlineKeyboard(Collection $messageInfo, string $text, array $data, array $page = []): array
    {
        $chatId = $messageInfo->chat->id;

        $keyboard = [];
        $k = 0;
        foreach ($data as $value) {
            $keyboard[$k]['text'] = $value;
            $keyboard[$k]['callback_data'] = $value;
            $k++;
        }

        if (empty($data)) {
            $text = "Таких турниров не найдено";
        }

        $markup =
            [
                'inline_keyboard' =>
                    array_chunk($keyboard, 1),
            ];

        $markup['inline_keyboard'] = array_merge($markup['inline_keyboard'], [$page]);

        return [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($markup)
        ];
    }
}
