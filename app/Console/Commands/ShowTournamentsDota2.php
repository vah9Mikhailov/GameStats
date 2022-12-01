<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Api\Liquipedia;
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
     * @var string
     */
    protected $page = "Portal:Tournaments";

    /**
     * @var int
     */
    private $upcoming = 0;

    /**
     * @var int
     */
    private $ongoing = 1;

    /**
     * @var int
     */
    private $mostRecent = 2;


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
     * Execute the console command.
     *
     * @return array
     */
    public function handle()
    {
        $liquipedia = DotaBotServiceLocator::getLiquipedia(env('LIQUIPEDIA_HOST_URL'));
        try {
            $result = $liquipedia->getPageRequest($this->page);
        } catch (\DomainException $e) {
            Log::warning($e->getMessage());
        }
        $dom = DotaBotServiceLocator::getDom();
        $dom->loadStr($result);
        $listFromParser = $dom->getElementsByClass('tournament-card')->toArray();

        $dataTournament = [];
        foreach ($listFromParser as $key => $tournamentName) {
            $tournamentsInnerHtml = $dom->loadStr($tournamentName->innerHtml());
            $tournamentsInfoByClass = $tournamentsInnerHtml->getElementsByClass('divRow')->toArray();
            foreach ($tournamentsInfoByClass as $tournamentsInfo) {
                $innerHtmlName = $dom->loadStr($tournamentsInfo->innerHtml());
                $dataTournament[] = $this->uniteToArray($innerHtmlName, $key);
            }

        }
        $tournament = DotaBotServiceLocator::getTournament();

        foreach ($dataTournament as $data) {
            $this->insertOrUpdate($tournament, $data);
        }

        $telegramBot = DotaBotServiceLocator::getTelegramBot();

        $statusTournament = [
            $this->upcoming => "Будущие",
            $this->ongoing => "Проходящие",
            $this->mostRecent => "Завершившиеся"
        ];
        $updateId = 0;
        while (true) {

            sleep(2);
            $updates = $telegramBot->getUpdates(['offset' => $updateId + 1]);

            if ($updates) {
                foreach ($updates as $update) {
                    if ($update->getMessage() && $update->getMessage()->text == "/start") {
                        $params = $this->createInlineKeyboard($update->getMessage(), "Выберите этап турниров", $statusTournament);
                        $telegramBot->sendMessage($params);
                        $updateId = $update->updateId;
                    } elseif ($update->callbackQuery) {
                        $typeAndPage = explode(' ', $update->callbackQuery['data']);
                        if ($typeStatus = array_search($typeAndPage[0], $statusTournament, true)
                        ) {
                            $listTournament = $tournament->selectByColumn(
                                'type',
                                $typeStatus,
                                true,
                                8
                            );

                            $nameTournament = $this->createArray($listTournament);
                            $pagination = [];
                            if ($tournament->countData('type',$typeStatus) > 8) {
                                $pagination[0]['text'] = "2 PAGE FORWARD>>";
                                $pagination[0]['callback_data'] = $typeStatus . " 2";
                            }
                            $params = $this->createInlineKeyboard(
                                $update->getMessage(),
                                "Выберите турнир",
                                $nameTournament,
                                $pagination
                            );
                            $telegramBot->sendMessage($params);
                            $updateId = $update->updateId;
                        } elseif ($callbackPage = array_search($typeAndPage[1], range(0,100))) {
                            $listTournament = $tournament->selectByColumn(
                                'type',
                                $typeAndPage[0],
                                true,
                                8,
                                ((int)$callbackPage-1)*8
                            );
                            $nameTournament = $this->createArray($listTournament);
                            $countTournament = $tournament->countData('type', $typeAndPage[0]);
                            $pagination = $this->createPagination($countTournament, (int)$callbackPage, $typeAndPage[0]);
                            $params = $this->createInlineKeyboard(
                                $update->getMessage(),
                                "Выберите турнир",
                                $nameTournament,
                                $pagination
                            );
                            $telegramBot->sendMessage($params);
                            $updateId = $update->updateId;

                        }
                    }

                }
            }
        }
    }


    /**
     * @param $innerHtml
     * @param $key
     * @return array
     */
    private function uniteToArray($innerHtml, $key): array
    {
        return [
            'name' => $innerHtml->getElementsByTag('a')[2]->innerHtml(),
            'date' => $innerHtml->getElementsByClass('Date')->innerHtml(),
            'type' => $key,
            'link' => $innerHtml->getElementsByTag('a')[2]->href,
            'hash' => md5($innerHtml->getElementsByTag('a')[2]->innerHtml().$key.$innerHtml->getElementsByClass('Date')->innerHtml())
        ];
    }

    /**
     * @param Tournament $tournament
     * @param array $data
     * @return void
     */
    private function insertOrUpdate(Tournament $tournament, array $data): void
    {
        $dataFromTable = $tournament->selectByColumn(array_key_first($data), $data['name']);
        if ($dataFromTable){
            $hashFromDataTable = md5($dataFromTable->name.$dataFromTable->type.$dataFromTable->date);
            $changes = [];
            if ($hashFromDataTable !== $data['hash']) {
                $changes['updated_at'] = Date::now()->toDateTimeString();
                unset($data['hash']);
                $changes['type'] = $data['type'];
                $changes['date'] = $data['date'];
                $tournament->updateData($changes, array_slice($data,0,1));
            }
        } else {
            $data['created_at'] = Date::now()->toDateTimeString();
            $data['updated_at'] = $data['created_at'];
            unset($data['hash']);
            $tournament->insert($data);
        }
    }


    /**
     * @param Collection $messageInfo
     * @param string $text
     * @param array $data
     * @param array $page
     * @return array
     */
    private function createInlineKeyboard(Collection $messageInfo, string $text, array $data, array $page = []): array
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


    /**
     * @param int $count
     * @param int $page
     * @param string $keyStatus
     * @return array
     */
    private function createPagination(int $count, int $page, string $keyStatus): array
    {
       $pagination = [];
       $rightText = sprintf('%d PAGE>>', $page + 1);
       $rightCallback = $keyStatus . " " . ($page + 1);
       $leftText = sprintf('<<%d PAGE', $page - 1);
       $leftCallback = $keyStatus . " " . ($page - 1);
        switch ($page) {
            case (1) :
                $pagination[0]['text'] = sprintf('%d PAGE FORWARD>>', $page + 1);
                $pagination[0]['callback_data'] = $rightCallback;
                break;
            case (round($count/8)):
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


    /**
     * @param $data
     * @return array
     */
    private function createArray($data): array
    {
        $nameTournament = [];
        foreach ($data as $value) {
            $nameTournament[] = $value->name;
        }
        return $nameTournament;
    }
}
