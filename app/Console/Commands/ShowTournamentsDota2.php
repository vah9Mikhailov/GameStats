<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Api\Liquipedia;
use App\Models\TelegramBot;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;
use Telegram\Bot\Api;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;
use function Composer\Autoload\includeFile;

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


    public function handle(object $update, array $statusTournament, Tournament $tournament, Api $telegramBotApi, TelegramBot $telegramBot)
    {
        $typeAndPage = explode(' ', $update->callbackQuery['data']);
        if ($typeStatus = array_search($typeAndPage[0], $statusTournament, true)
        ) {
            $listTournament = $tournament->selectByColumn(
                'type',
                $typeStatus,
                true,
                8
            );

            $nameTournament = array_column($listTournament, 'name');;
            $pagination = [];
            if ($tournament->countData('type',$typeStatus) > 8) {
                $pagination[0]['text'] = "2 PAGE FORWARD>>";
                $pagination[0]['callback_data'] = $typeStatus . " 2";
            }
            $telegramBot = DotaBotServiceLocator::getTelegramBot();
            $params = $telegramBot->createInlineKeyboard(
                $update->getMessage(),
                "Выберите турнир",
                $nameTournament,
                $pagination
            );

            $telegramBotApi->sendMessage($params);
            return $update->updateId;
        } elseif ($callbackPage = array_search($typeAndPage[1], range(0,100))) {
            $listTournament = $tournament->selectByColumn(
                'type',
                $typeAndPage[0],
                true,
                8,
                ((int)$callbackPage-1)*8
            );
            $nameTournament = array_column($listTournament, 'name');
            $countTournament = $tournament->countData('type', $typeAndPage[0]);
            $pagination = $this->createPagination($countTournament, (int)$callbackPage, $typeAndPage[0]);
            $params = $telegramBot->createInlineKeyboard(
                $update->getMessage(),
                "Выберите турнир",
                $nameTournament,
                $pagination
            );
            $telegramBotApi->sendMessage($params);
            return $update->updateId;
        }
    }



    /**
     * @param int $count
     * @param int $page
     * @param string $keyStatus
     * @return array
     */
    private function createPagination(int $count, int $page, string $keyStatus): array
    {
       $pagination = [];
       $rightText = sprintf('%d PAGE FORWARD>>', $page + 1);
       $rightCallback = $keyStatus . " " . ($page + 1);
       $leftText = sprintf('<<%d PAGE BACK', $page - 1);
       $leftCallback = $keyStatus . " " . ($page - 1);
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
                $pagination[1]['text'] = $rightText;
                $pagination[1]['callback_data'] = $rightCallback;
                break;
        }

        return $pagination;
    }
}
