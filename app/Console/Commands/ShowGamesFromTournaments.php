<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use Illuminate\Console\Command;

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
        $arguments = $this->argument('tournament');
        $argString = implode(' ', $arguments);
        if (array_key_exists($argString, $namesLinks)){
            $dataTournament = $tournament->selectByColumn('name', $argString);
            $link = $dataTournament->link;

            $endPoint = 'https://liquipedia.net/dota2/api.php';
            $params = [
                'action' => "parse",
                'format' => "json",
                'page'=> "$link"
            ];
            $url = $endPoint . "?" . http_build_query($params);
            $content = $this->getContents($url);

            $result = json_decode($content, true);

        }

    }
}
