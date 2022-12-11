<?php

namespace App\Api;

use App\Console\Commands\ShowTournamentsDota2;
use App\Models\TelegramBot;
use App\Models\Tournament;
use App\Models\TournamentRosterTeam;
use PHPHtmlParser\Dom;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

final class DotaBotServiceLocator
{
    /**
     * @var Transport
     */
    private static $transport;

    /**
     * @var Liquipedia
     */
    private static $liquipedia;

    /**
     * @var Dom
     */
    private static $dom;

    /**
     * @var Tournament
     */
    private static $tournament;

    /**
     * @var Api
     */
    private static $telegramBotApi;

    /**
     * @var TelegramBot
     */
    private static $telegramBot;

    /**
     * @var ShowTournamentsDota2
     */
    private static $commandTournaments;

    /**
     * @var TournamentRosterTeam
     */
    private static $tournamentRosterTeam;


    /**
     * @param $host
     * @return Transport
     */
    public static function getTransport($host): Transport
    {
        if (!self::$transport) {
            self::$transport = new Transport($host);
        }

        return self::$transport;
    }

    /**
     * @return Liquipedia
     */
    public static function getLiquipedia($host): Liquipedia
    {
        if (!self::$liquipedia) {
            self::$liquipedia = new Liquipedia(self::getTransport($host));
        }

        return self::$liquipedia;

    }

    /**
     * @return Dom
     */
    public static function getDom(): Dom
    {
        if (!self::$dom) {
            self::$dom = new Dom();
        }

        return self::$dom;

    }

    /**
     * @return Tournament
     */
    public static function getTournament(): Tournament
    {
        if (!self::$tournament) {
            self::$tournament = new Tournament();
        }

        return self::$tournament;
    }


    /**
     * @return Api
     * @throws TelegramSDKException
     */
    public static function getTelegramBotApi(): Api
    {

        if (!self::$telegramBotApi) {
            self::$telegramBotApi = new Api(env('TELEGRAM_TOKEN'));
        }
        return self::$telegramBotApi;
    }


    /**
     * @return ShowTournamentsDota2
     */
    public static function getCommandShowTournaments(): ShowTournamentsDota2
    {

        if (!self::$commandTournaments) {
            self::$commandTournaments = new ShowTournamentsDota2();
        }
        return self::$commandTournaments;
    }

    /**
     * @return TelegramBot
     */
    public static function getTelegramBot(): TelegramBot
    {

        if (!self::$telegramBot) {
            self::$telegramBot = new TelegramBot();
        }
        return self::$telegramBot;
    }

    /**
     * @return TournamentRosterTeam
     */
    public static function getTournamentRosterTeams(): TournamentRosterTeam
    {
        if (!self::$tournamentRosterTeam) {
            self::$tournamentRosterTeam = new TournamentRosterTeam();
        }
        return self::$tournamentRosterTeam;
    }

}
