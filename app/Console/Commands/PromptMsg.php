<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Http\Controllers\CronController;
use App\Http\Controllers\GupshupWebhookPanasonicController;
class PromptMsg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:promptmsg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron For Auto Prompt message';

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
        $controller = new GupshupWebhookPanasonicController();
        $controller->promptMsg();
    }
}
