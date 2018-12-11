<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;
use App\Model\ReviewBuilder\ReviewBuilderFeedback;
use App\Cron\CronController;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $client = new CronController();
            $client->universal_campaign_send();
            $client->holiday_campaign_send();
            $client->dated_campaign_send();
            $client->activity_reminder();
        })->everyMinute();

        $schedule->call(function(){
            $client = new CronController();
            $client->broadcast_campaign_send();
        })->hourly();

        $schedule->call(function () {
            $cron = new CronController();
            $cron->update_paid_status();
        })->monthly();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
