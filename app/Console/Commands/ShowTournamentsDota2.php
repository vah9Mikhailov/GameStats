<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use Faker\Core\DateTime;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Date;
use PHPHtmlParser\Dom;

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
     * Execute the console command.
     *
     * @return array
     */
    public function handle()
    {
        $endPoint = 'https://liquipedia.net/dota2/api.php';
        $params = [
            'action' => "parse",
            'format' => "json",
            'page'=> "Portal:Tournaments"
        ];
        $url = $endPoint . "?" . http_build_query($params);
        $content = $this->getContents($url);

        $result = json_decode($content, true);

        $html = $result['parse']['text']['*'];
        //      $revId = $result['parse']['revid'];


        $dom = new Dom();
        $dom->loadStr($html);
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

        $tournament = new Tournament();

        foreach ($dataTournament as $data) {
            $dataFromTable = $tournament->selectByColumn(array_key_first($data), $data['name']);
            $hashFromDataTable = md5($dataFromTable->name.$dataFromTable->type);
            if ($dataFromTable){
                $changes = [];
                if ($hashFromDataTable !== $data['hash']) {
                    $changes['updated_at'] = Date::now()->toDateTimeString();
                    unset($data['hash']);
                    $changes['type'] = $data['type'];
                    $tournament->updateData($changes, array_slice($data,0,1));
                }
            } else {
                $data['created_at'] = Date::now()->toDateTimeString();
                $data['updated_at'] = $data['created_at'];
                unset($data['hash']);
                $tournament->insert($data);
            }
        }


        return $tournament->select();
    }

    /**
     * @param $url
     * @return string
     */
    private function getContents($url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch,CURLOPT_HEADER,'User-Agent: TestBot/1.0');

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
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
            'hash' => md5($innerHtml->getElementsByTag('a')[2]->innerHtml().$key)
        ];
    }

}
