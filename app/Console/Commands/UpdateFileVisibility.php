<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;

class UpdateFileVisibility extends Command
{
    protected $signature = 'files:update-visibility';
    protected $description = 'Update visibility of existing files in local storage to public';

    public function handle()
    {
        $this->info('Updating file visibility...');

        // Obtener todos los pagos con recibos
        $payments = Payment::whereNotNull('receipt')->get();

        $count = 0;
        foreach ($payments as $payment) {
            if (Storage::disk('public')->exists($payment->receipt)) {
                // Esta operación puede no ser necesaria para archivos locales,
                // pero la mantenemos por consistencia
                Storage::disk('public')->setVisibility($payment->receipt, 'public');
                $this->line("Updated visibility for: {$payment->receipt}");
                $count++;
            }
        }

        $this->info("Updated visibility for {$count} files.");

        return Command::SUCCESS;
    }
}
