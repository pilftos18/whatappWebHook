<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Http\Controllers\CronController;
use App\Http\Controllers\CommonController;
class UpdateLoginStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:sessiontimeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron For Auto session timeout';

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
        $controller = new CommonController();
        $controller->updateLoginStatus();
    }
}
