<?php

namespace App\Console\Commands;

use App\Api\DotaBotServiceLocator;
use Illuminate\Console\Command;

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


    public function handle()
    {
        $tournament = DotaBotServiceLocator::getTournament();
        $telegramBotApi = DotaBotServiceLocator::getTelegramBotApi();
        $telegramBot = DotaBotServiceLocator::getTelegramBot();
        $statusTournament = [
            $this->upcoming => "Будущие",
            $this->ongoing => "Проходящие",
            $this->mostRecent => "Завершившиеся"
        ];
        $updateId = 0;
        while (true) {

            sleep(2);
            $updates = $telegramBotApi->getUpdates(['offset' => $updateId + 1]);

            if ($updates) {
                foreach ($updates as $update) {
                    if ($update->getMessage() && $update->getMessage()->text == "/start") {
                        $params = $telegramBot->createInlineKeyboard(
                            $update->getMessage(),
                            "Выберите этап турниров",
                            $statusTournament
                        );
                        $telegramBotApi->sendMessage($params);
                        $updateId = $update->updateId;
                    } elseif ($update->callbackQuery) {
                        $commandTournaments = DotaBotServiceLocator::getCommandShowTournaments();
                        $updateId = $commandTournaments->handle($update, $statusTournament, $tournament, $telegramBotApi, $telegramBot);
                    }
                }
            }
        }

    }
}
