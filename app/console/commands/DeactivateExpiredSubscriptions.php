<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Finance\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeactivateExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:expire';

    /**
     * The console command description.
     */
    protected $description = 'Automatically deactivate subscriptions that have passed their end_date.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        $expired = Subscription::where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'expired',
                'deactivated_by' => null, // auto-expired, not by staff
            ]);

        $this->info("✅ Automatically expired $expired subscriptions.");
    }
}
