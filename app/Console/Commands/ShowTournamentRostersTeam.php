<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Models\TelegramBot;
use App\Models\Tournament;
use App\Models\TournamentRosterTeam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ShowTournamentRostersTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show:rosters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show tournament rosters team';

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
     * @param TournamentRosterTeam $tournamentRosterTeam
     * @param TelegramBot $telegramBot
     * @param object $update
     * @param object $tournamentData
     * @return array
     */
    public function choiceTournamentRostersFirstPage(
        TournamentRosterTeam $tournamentRosterTeam,
        TelegramBot $telegramBot,
        object $update,
        object $tournamentData
    ): array
    {
        $listTeams = $tournamentRosterTeam->selectByColumn(
            ['tournament_id' => $tournamentData->id],
            true,
            8
        );
        if ($listTeams) {
            $nameTournament = array_column($listTeams, 'name');
            $pagination = [];
            if ($tournamentRosterTeam->countData('tournament_id',$tournamentData->id) > 8) {
                $pagination[0]['text'] = "2 PAGE FORWARD>>";
                $pagination[0]['callback_data'] = $tournamentData->id . " 2 page";
            }
            $backButton = $telegramBot->createBackButton($tournamentData->name);

            return $telegramBot->createInlineKeyboard(
                $update->getMessage(),
                "Выберите команду турнира " . "<b>" . '"' . $tournamentData->name . '"' . "</b>",
                $nameTournament,
                $backButton,
                $pagination
            );
        }

    }


    /**
     * @param TournamentRosterTeam $tournamentRosterTeam
     * @param TelegramBot $telegramBot
     * @param ShowTournamentsDota2 $showCommandTournament
     * @param object $update
     * @param object $tournamentData
     * @param array $callbackPage
     * @return array
     */
    public function choiceTournamentRostersWithPagination(
        TournamentRosterTeam $tournamentRosterTeam,
        TelegramBot $telegramBot,
        ShowTournamentsDota2 $showCommandTournament,
        object $update,
        object $tournamentData,
        array $callbackPage
    ): array
    {
        $offset = ($callbackPage[1]-1)*8;
        $listTeams = $tournamentRosterTeam->selectByColumn(
            ['tournament_id' => $tournamentData->id],
            true,
            8,
            $offset
        );
        $nameTeam = array_column($listTeams, 'name');
        $countTeam = $tournamentRosterTeam->countData('tournament_id', $tournamentData->id);
        $limit = $offset + 8;
        $pagination = $showCommandTournament->createPagination($countTeam, $callbackPage[1], $tournamentData->id, $limit);
        $backButton = $telegramBot->createBackButton($tournamentData->name);

        return  $telegramBot->createInlineKeyboard(
            $update->getMessage(),
            "Выберите команду турнира " . "<b>" . '"' . $tournamentData->name . '"' . "</b>",
            $nameTeam,
            $backButton,
            $pagination
        );
    }

    /**
     * @param TelegramBot $telegramBot
     * @param string $nameTournament
     * @param object $update
     * @param string $statusTournament
     * @return array
     */
    public function choiceRostersOrGamesTournament(
        TelegramBot $telegramBot,
        string $nameTournament,
        object $update,
        string $statusTournament
    ): array
    {
        return $telegramBot->createInlineKeyboardForGamesAndRosters(
            $update->getMessage(),
            $nameTournament,
            'Турнир "' . "<b>" . "$nameTournament" . "</b>" . '". Выберите, что хотите посмотреть',
            $statusTournament
        );
    }


    /**
     * @param TelegramBot $telegramBot
     * @param object $tournamentRosterTeam
     * @param object $update
     * @param object $tournamentFromTable
     * @return array
     */
    public function showRosterTeam(
        TelegramBot $telegramBot,
        object $tournamentRosterTeam,
        object $update,
        object $tournamentFromTable
    ): array
    {
        $text = $this->createHtmlText($tournamentRosterTeam);
        return $telegramBot->createMessageWithBackButtonInlineKeyboard(
            $update->getMessage(),
            $tournamentFromTable,
            $text
        );
    }

    private function createHtmlText(object $tournamentRosterTeam): string
    {
        $roster = json_decode($tournamentRosterTeam->roster);
        $text = 'Состав команды "' . "<b>" . $tournamentRosterTeam->name . "</b>" . '":' . "\n";
        $text .= "\n";
        foreach ($roster as $position => $name) {
            $nameHtml = '<code language="php">' . $name . '</code>';
            switch ($position) {
                case (1):
                    $text .= "<i>" . "Carry" . "</i>" . " => " . $nameHtml . "\n";
                    break;
                case(2):
                    $text .= "<i>" . "Middle" . "</i>" . " => " . $nameHtml . "\n";
                    break;
                case(3):
                    $text .= "<i>" . "Offlane" . "</i>" . " => " . $nameHtml . "\n";
                    break;
                case(4):
                    $text .= "<i>" . "Roaming Support" . "</i>" . " => " . $nameHtml . "\n";
                    break;
                case(5):
                    $text .= "<i>" . "Hard support" . "</i>" . " => " . $nameHtml . "\n";
                    break;
                case("C"):
                    $text .= "<i>" . "Coach" . "</i>" . " => " . $nameHtml;
                    break;
            }
        }
        return $text;
    }
}
