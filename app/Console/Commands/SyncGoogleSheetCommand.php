<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetSyncService;
use Illuminate\Console\Command;

class SyncGoogleSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'safety:sync-sheet {--sheet_id= : Custom Google Sheet ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data laporan K3 dari Google Sheets secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetSyncService $syncService): int
    {
        $this->info('Memulai sinkronisasi Google Sheet Safety Warrior...');
        
        $sheetId = $this->option('sheet_id');
        $result = $syncService->sync($sheetId);

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);
            return Command::SUCCESS;
        } else {
            $this->error('✗ ' . $result['message']);
            return Command::FAILURE;
        }
    }
}
