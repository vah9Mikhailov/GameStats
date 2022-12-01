<?php

namespace App\Console\Commands;

use App\Api\Liquipedia;
use App\Models\Tournament;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom;

class ShowGamesFromTournaments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dota2:games {tournament*}';

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
        $tournament = new Tournament();
        $listTournaments = $tournament->select();
        $namesLinks = array_column($listTournaments,'link','name');
        $argument = $this->argument('tournament');
        $argString = implode(' ', $argument);
        if (array_key_exists($argString, $namesLinks)){
            $dataTournament = $tournament->selectByColumn('name', $argString);
            $link = $dataTournament->link;
            $arrLink = explode('/', $link);
            $arrLink = array_slice($arrLink,2);
            $strLink = implode('/', $arrLink);

            $liquipedia = new Liquipedia();

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
                    $rosterInnerHtml = $dom->loadStr($rosterHtmlByTable->innerHtml());
                    $rosterHtmlByTds = $rosterInnerHtml->getElementsByTag('td')->toArray();
                    $roster = [];
                    foreach ($rosterHtmlByTds as $rosterHtmlByTd) {
                        $dom->loadStr($rosterHtmlByTd->innerHtml());
                        if ($dom->getElementsByTag('a')[1]) {
                            $roster[] = $dom->getElementsByTag('a')[1]->innerHtml();
                        }

                    }
                    $rostersTeams[$nameTeam] = $roster;
                }



            }







        }

    }
}
