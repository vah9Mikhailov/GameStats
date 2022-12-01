<?php

namespace App\Api;

use App\Models\Tournament;
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
    private static $telegramBot;

    /**
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
    public static function getTelegramBot(): Api
    {

        if (!self::$telegramBot) {
            self::$telegramBot = new Api(env('TELEGRAM_TOKEN'));
        }
        return self::$telegramBot;
    }

}
