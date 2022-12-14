<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Models\TelegramBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CallTelegramBotDota2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:telegramBotDota2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call Telegram Bot Dota2 information';

    /**
     * @var int
     */
    private const UPCOMING = 0;

    /**
     * @var int
     */
    private const INGOING = 1;

    /**
     * @var int
     */
    private const MOSTRECENT = 2;

    /**
     * @var array
     */
    private const STATUS_TOURNAMENT = [
        self::UPCOMING => "Будущие",
        self::INGOING=> "Проходящие",
        self::MOSTRECENT => "Завершившиеся"
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $tournament = DotaBotServiceLocator::getTournament();
        $telegramBotApi = DotaBotServiceLocator::getTelegramBotApi();
        $telegramBot = DotaBotServiceLocator::getTelegramBot();
        $tournamentRosterTeam = DotaBotServiceLocator::getTournamentRosterTeams();
        $commandShowTournaments = DotaBotServiceLocator::getCommandShowTournaments();
        $commandShowRostersTeam = DotaBotServiceLocator::getCommandShowRostersTeam();
        $updateId = 0;
        while (true) {

            sleep(2);
            $updates = $telegramBotApi->getUpdates(['offset' => $updateId + 1]);

            if ($updates) {
                foreach ($updates as $update) {
                    if ($update->getMessage()) {
                        $params = [];
                        if ($update->getMessage()->text == "/start"){
                            $params = $this->sendMessageStartMessageOrCallback($telegramBot, $update);
                        } elseif ($update->callbackQuery) {
                            $callbackData = explode(' ', $update->callbackQuery['data']);

                            $tournamentFromTable = $tournament->selectByColumn('name', $update->callbackQuery['data']);
                            $rosterTeamFromTable = $tournamentRosterTeam->selectByColumn(['name' => $update->callbackQuery['data']]);
                            if (count($callbackData) < 2
                                &&
                                $typeStatusTournament = array_intersect(self::STATUS_TOURNAMENT, $callbackData)
                            ) {
                                $params = $commandShowTournaments->choiceTournamentFirstPage(
                                    $update,
                                    $typeStatusTournament,
                                    $tournament
                                );
                            } elseif (strpos($update->callbackQuery['data'], "page")) {
                                $typeStatusTournament = array_intersect(self::STATUS_TOURNAMENT, $callbackData);
                                if ($typeStatusTournament) {
                                    $params = $commandShowTournaments->choiceTournamentWithPagination(
                                        $tournament,
                                        $telegramBot,
                                        $typeStatusTournament,
                                        $callbackData,
                                        $update
                                    );
                                } elseif (
                                    is_numeric($callbackData[0])
                                    &&
                                    $tournamentFromTable = $tournament->selectByColumn('id', $callbackData[0])
                                ) {
                                    $params = $commandShowRostersTeam->choiceTournamentRostersWithPagination(
                                        $tournamentRosterTeam,
                                        $telegramBot,
                                        $commandShowTournaments,
                                        $update,
                                        $tournamentFromTable,
                                        $callbackData
                                    );
                                }
                            } elseif ($tournamentFromTable) {
                                $statusTournament = array_search(
                                    $tournamentFromTable->type,
                                    array_flip(self::STATUS_TOURNAMENT)
                                );
                                $params = $commandShowRostersTeam->choiceRostersOrGamesTournament(
                                    $telegramBot,
                                    $tournamentFromTable->name,
                                    $update,
                                    $statusTournament
                                );
                            } elseif (strpos($update->callbackQuery['data'], "rosters")) {
                                $callbackDataNameTournament = explode('#', $update->callbackQuery['data']);
                                $tournamentFromTable = $tournament->selectByColumn('name', $callbackDataNameTournament[0]);
                                $params = $commandShowRostersTeam->choiceTournamentRostersFirstPage(
                                    $tournamentRosterTeam,
                                    $telegramBot,
                                    $update,
                                    $tournamentFromTable
                                );
                            } elseif ($update->callbackQuery['data'] == "/start") {
                                $params = $this->sendMessageStartMessageOrCallback($telegramBot, $update);
                            } elseif ($rosterTeamFromTable) {
                                $tournamentFromTable = $tournament->selectByColumn('id', $rosterTeamFromTable->tournament_id);
                                $params = $commandShowRostersTeam->showRosterTeam(
                                    $telegramBot,
                                    $rosterTeamFromTable,
                                    $update,
                                    $tournamentFromTable
                                );
                            }
                        }
                        try {
                            $telegramBotApi->sendMessage($params);
                        } catch (TelegramSDKException $e) {
                            Log::error($e->getMessage());
                        }
                        $updateId = $update->updateId;
                    }
                }
            }
        }
    }

    /**
     * @param TelegramBot $telegramBot
     * @param object $update
     * @return array
     */
    private function sendMessageStartMessageOrCallback(
        TelegramBot $telegramBot,
        object $update
    ): array
    {
        return $telegramBot->createInlineKeyboard(
            $update->getMessage(),
            "Выберите этап турниров",
            self::STATUS_TOURNAMENT,
        );

    }
}

