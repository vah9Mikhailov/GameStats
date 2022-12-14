<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class ParseInfoTournamentsFromLiquidpedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:tournamentsDota2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parsing From Liquipedia information tournaments Dota2';

    /**
     * @var string
     */
    protected $page = "Portal:Tournaments";

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
        $liquipedia = DotaBotServiceLocator::getLiquipedia(env('LIQUIPEDIA_HOST_URL'));
        try {
            $result = $liquipedia->getPageRequest($this->page);
        } catch (\DomainException $e) {
            Log::warning($e->getMessage());
        }
        $dom = DotaBotServiceLocator::getDom();
        $dom->loadStr($result);
        $listFromParser = $dom->getElementsByClass('tournament-card')->toArray();

        $listTournaments = [];
        foreach ($listFromParser as $key => $tournamentName) {
            $tournamentsInnerHtml = $dom->loadStr($tournamentName->innerHtml());
            $tournamentsInfoByClass = $tournamentsInnerHtml->getElementsByClass('divRow')->toArray();
            foreach ($tournamentsInfoByClass as $tournamentsInfo) {
                $innerHtmlName = $dom->loadStr($tournamentsInfo->innerHtml());
                $listTournaments[] = $this->uniteToArray($innerHtmlName, $key);
            }

        }


        $tournament = DotaBotServiceLocator::getTournament();

        foreach ($listTournaments as $dataTournament) {
            $this->insertOrUpdate($tournament, $dataTournament);
        }

        return $listTournaments;
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
            'hash' => md5($key.$innerHtml->getElementsByClass('Date')->innerHtml())
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
            $hashFromDataTable = md5($dataFromTable->type.$dataFromTable->date);
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
}
