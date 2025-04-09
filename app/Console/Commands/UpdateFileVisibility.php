<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;

class UpdateFileVisibility extends Command
{
    protected $signature = 'files:update-visibility';
    protected $description = 'Update visibility of existing files in S3 bucket to public';

    public function handle()
    {
        $this->info('Updating file visibility...');

        // Obtener todos los pagos con recibos
        $payments = Payment::whereNotNull('receipt')->get();

        $count = 0;
        foreach ($payments as $payment) {
            if (Storage::disk('s3')->exists($payment->receipt)) {
                Storage::disk('s3')->setVisibility($payment->receipt, 'public');
                $this->line("Updated visibility for: {$payment->receipt}");
                $count++;
            }
        }

        $this->info("Updated visibility for {$count} files.");

        return Command::SUCCESS;
    }
}
