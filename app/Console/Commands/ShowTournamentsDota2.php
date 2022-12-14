<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Models\TelegramBot;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;


class ShowTournamentsDota2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dota2:tournaments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of Dota2 tournaments from Liquipedia';



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param object $update
     * @param array $typeStatus
     * @param Tournament $tournament
     * @return array
     */
    public function choiceTournamentFirstPage(
        object $update,
        array $typeStatus,
        Tournament $tournament
    ): array
    {
        $listTournament = $tournament->selectByColumn(
            'type',
            key($typeStatus),
            true,
            8
        );

        $nameTournament = array_column($listTournament, 'name');
        $pagination = [];
        if ($tournament->countData('type',key($typeStatus)) > 8) {
            $pagination[0]['text'] = "2 PAGE FORWARD>>";
            $pagination[0]['callback_data'] = current($typeStatus) . " 2 page";
        }
        $telegramBot = DotaBotServiceLocator::getTelegramBot();
        $backButton = $telegramBot->createBackButton('/start');

        return $telegramBot->createInlineKeyboard(
            $update->getMessage(),
            "Выберите турнир статуса " . "<b>" . '"' . current($typeStatus) . '"' . "</b>",
            $nameTournament,
            $backButton,
            $pagination
        );
    }


    /**
     * @param Tournament $tournament
     * @param TelegramBot $telegramBot
     * @param array $typeStatus
     * @param array $callbackPage
     * @param object $update
     * @return array
     */
    public function choiceTournamentWithPagination(
        Tournament $tournament,
        TelegramBot $telegramBot,
        array $typeStatus,
        array $callbackPage,
        object $update
    ): array
    {
        $offset = ($callbackPage[1]-1)*8;
        $listTournament = $tournament->selectByColumn(
            'type',
            key($typeStatus),
            true,
            8,
            $offset
        );
        $limit = $offset + 8;
        $nameTournament = array_column($listTournament, 'name');
        $countTournament = $tournament->countData('type', key($typeStatus));
        $pagination = $this->createPagination($countTournament, $callbackPage[1], current($typeStatus), $limit);
        $backButton = $telegramBot->createBackButton('/start');

        return $telegramBot->createInlineKeyboard(
            $update->getMessage(),
            "Выберите турнир статуса " . "<b>" . '"' . current($typeStatus) . '"' . "</b>",
            $nameTournament,
            $backButton,
            $pagination
        );
    }



    /**
     * @param int $count
     * @param int $page
     * @param string $keyStatus
     * @return array
     */
    public function createPagination(int $count, int $page, string $keyStatus, int $limit): array
    {
       $pagination = [];
       $rightText = sprintf('%d PAGE FORWARD>>', $page + 1);
       $rightCallback = $keyStatus . " " . ($page + 1) . " page";
       $leftText = sprintf('<<%d PAGE BACK', $page - 1);
       $leftCallback = $keyStatus . " " . ($page - 1) . " page";
        switch ($page) {
            case (1) :
                $pagination[0]['text'] = sprintf('%d PAGE FORWARD>>', $page + 1);
                $pagination[0]['callback_data'] = $rightCallback;
                break;
            case (floor($count/8) + 1):
                $pagination[0]['text'] = sprintf('<<%d PAGE BACK', $page - 1);
                $pagination[0]['callback_data'] = $leftCallback;
                break;
            default:
                $pagination[0]['text'] = $leftText;
                $pagination[0]['callback_data'] = $leftCallback;
                if ($limit !== $count) {
                    $pagination[1]['text'] = $rightText;
                    $pagination[1]['callback_data'] = $rightCallback;
                }
                break;
        }

        return $pagination;
    }
}
