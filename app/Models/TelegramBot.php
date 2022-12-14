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
     * @param array $back
     * @param array $page
     * @return array
     */
    public function createInlineKeyboard(
        Collection $messageInfo,
        string $text,
        array $data,
        array $back = [],
        array $page = []
    ): array
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
            $text = "Не найдено";
        }

        $markup =
            [
                'inline_keyboard' =>
                    array_chunk($keyboard, 1),
            ];

        $markup['inline_keyboard'] = array_merge($markup['inline_keyboard'], [$page], [$back]);

        return [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'reply_markup' => json_encode($markup)
        ];
    }

    /**
     * @param Collection $messageInfo
     * @param string $nameTournament
     * @param string $text
     * @return array
     */
    public function createInlineKeyboardForGamesAndRosters(
        Collection $messageInfo,
        string $nameTournament,
        string $text,
        string $statusTournament
    ): array
    {
        $chatId = $messageInfo->chat->id;

        $keyboard = [
            [
                'text' => "Составы команд",
                'callback_data' => $nameTournament . "#rosters"
            ],
            [
                'text' => "Игры турнира",
                'callback_data' => $nameTournament . "#games"
            ]
        ];

        $markup =
            [
                'inline_keyboard' => [$keyboard],
            ];
        $markup['inline_keyboard'] = array_merge($markup['inline_keyboard'], [$this->createBackButton($statusTournament)]);

        return [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'reply_markup' => json_encode($markup)
        ];
    }

    /**
     * @param string $value
     * @return array
     */
    public function createBackButton(string $value): array
    {
        return [
            [
                'text' => "<<" . "BACK" . ">>",
                'callback_data' => $value
            ]
        ];
    }


    /**
     * @param Collection $messageInfo
     * @param object $tournamentRosterTeam
     * @param object $tournamentFromTable
     * @param string $text
     * @return array
     */
    public function createMessageWithBackButtonInlineKeyboard(
        Collection $messageInfo,
        object $tournamentFromTable,
        string $text
    ): array
    {
        $chatId = $messageInfo->chat->id;

        $markup['inline_keyboard'] = [$this->createBackButton($tournamentFromTable->name . " #rosters")];

        return [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'reply_markup' => json_encode($markup)
        ];
    }

}
