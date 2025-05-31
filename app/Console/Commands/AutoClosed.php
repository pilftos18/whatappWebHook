<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Http\Controllers\CronController;
use App\Http\Controllers\AssigingController;
class AutoClosed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:autoclossed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron For Auto Closed Non Assigned Chat';

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
        $controller = new AssigingController();
        $controller->autoClosedChat();
    }
}
