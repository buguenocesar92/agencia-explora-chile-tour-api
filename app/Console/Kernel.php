<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These schedules are run in a default, single-user context.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Añadir aquí otras tareas programadas existentes

        // Renovar token de WhatsApp cada 30 días (antes de que caduque el token de 60 días)
        $schedule->command('whatsapp:renew-token')
            ->monthlyOn(1, '01:00')
            ->appendOutputTo(storage_path('logs/whatsapp-token-renewal.log'));
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
