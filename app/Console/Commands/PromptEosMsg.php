<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GupshupWebhookEosController;

class PromptEosMsg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:prompteosmsg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $controller = new GupshupWebhookEosController();
        $controller->promptMsg();
    }
}
