<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;
ini_set('memory_limit', '-1');

class ShowRostersTeamsFromTournaments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dota2:roster {--tournament*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show games Dota 2 from Tournaments';

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
     * @return int
     */
    public function handle()
    {
        $tournament = DotaBotServiceLocator::getTournament();
        $listTournaments = $tournament->select();
        $namesLinks = array_column($listTournaments,'link','name');

        $listTournamentsFromParsing = new ParseInfoTournamentsFromLiquidpeia();
        foreach ($listTournamentsFromParsing->handle() as $turnir) {
//            if ($arguments = $this->argument('tournament')) {
//                $turnir['name'] = implode(' ', $arguments);
//            }
            if (array_key_exists($turnir['name'], $namesLinks)){
                $dataTournament = $tournament->selectByColumn('name', $turnir['name']);
                $link = $dataTournament->link;
                $arrLink = explode('/', $link);
                $arrLink = array_slice($arrLink,2);
                $strLink = implode('/', $arrLink);

                $liquipedia = DotaBotServiceLocator::getLiquipedia(env('LIQUIPEDIA_HOST_URL'));

                $result = $liquipedia->getPageRequest($strLink);

                $dom = new Dom();
                $dom->loadStr($result);
                $templateBox = $dom->getElementsByClass('template-box')->toArray();
                $rostersTeams = [];
                foreach ($templateBox as $template) {
                    $dom->loadStr($template->innerHtml());
                    if (count($dom->getElementsByClass('teamcard'))) {
                        $nameTeamHtml = $dom->loadStr($dom->getElementsByClass('teamcard')->innerHtml());
                        $nameTeam = $nameTeamHtml->getElementsByTag('a')->innerHtml();
                        if (count($dom->getElementsByClass('list')) > 1) {
                            $rosterHtmlByTable = $dom->getElementsByTag('table')[1];
                        } else {
                            $rosterHtmlByTable = $dom->getElementsByTag('table')[0];
                        }
                        $rosterWithRolesInnerHtml = $dom->loadStr($rosterHtmlByTable->innerHtml());
                        $rolesInTeamThs = $rosterWithRolesInnerHtml->getElementsByTag('th')->toArray();

                        $roles = [];
                        foreach ($rolesInTeamThs as $roleInTeamTh) {
                            if (array_key_exists($roleInTeamTh->innerHtml(), range(0,20))) {
                                $roles[] = $roleInTeamTh->innerHtml();
                            } else {
                                $roles[] = "C";
                            }
                        }

                        $roster = [];
                        $rosterHtmlByTds = $rosterWithRolesInnerHtml->getElementsByTag('td')->toArray();
                        for ($i = 0; $i < count($roles); $i++){
                            $rosterHtmlByTdInnerHtml = $dom->loadStr($rosterHtmlByTds[$i]->innerHtml());
                            if ($rosterHtmlByTdInnerHtml->find('a[class=new]')->count()) {
                                $roster[] = $rosterHtmlByTdInnerHtml->find('a[class=new]')->text;
                            } elseif ($dom->getElementsByTag('a')[1]
                                &&
                                empty($rosterHtmlByTdInnerHtml->getElementsByClass('team-template-image-icon')->count())
                            ) {
                                $roster[] = $dom->getElementsByTag('a')[1]->innerHtml();
                            } elseif (!$dom->getElementsByTag('a')[1] && $dom->getElementsByTag('abbr')[0]){
                                $roster[] = 'TBD (unknown)';
                            } elseif ($dom->getElementsByTag('a')[3]){
                                $roster[] = $dom->getElementsByTag('a')[3]->innerHtml();
                            }
                        }

                        $rosterWithRoles = array_combine($roles, $roster);
                        $rostersTeams[$nameTeam] = $rosterWithRoles;
                    }

                }

                $tournamentRosterTeam = DotaBotServiceLocator::getTournamentRosterTeams();
                foreach ($rostersTeams as $nameTeam => $rosterTeam)
                {
                    $dataFromTable = $tournamentRosterTeam->selectByColumn(
                        ['name' => $nameTeam, 'tournament_id' => $dataTournament->id]
                    );
                    if ($dataFromTable){
                        $hashFromDataTable = md5($dataFromTable->roster);
                        $hashRostersTeams = md5(json_encode($rosterTeam));
                        $changes = [];
                        if ($hashFromDataTable !== $hashRostersTeams) {
                            $changes['updated_at'] = Date::now()->toDateTimeString();
                            $changes['roster'] = json_encode($rosterTeam);
                            $tournamentRosterTeam->updateData(
                                $changes, array_slice((array)$dataFromTable,0, 1)
                            );
                        }
                    } else {
                        $data['name'] = $nameTeam;
                        $data['roster'] = json_encode($rosterTeam);
                        $data['tournament_id'] = $dataTournament->id;
                        $data['created_at'] = Date::now()->toDateTimeString();
                        $data['updated_at'] = $data['created_at'];
                        $tournamentRosterTeam->insert($data);
                    }
                }

        }
        }

    }
}
