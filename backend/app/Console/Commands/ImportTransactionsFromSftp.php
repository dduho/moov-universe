<?php

namespace App\Console\Commands;

use App\Http\Controllers\TransactionImportController;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportTransactionsFromSftp extends Command
{
    protected $signature = 'transactions:import-sftp';

    protected $description = 'Importe automatiquement les transactions depuis un dossier SFTP et supprime les fichiers après succès';

    public function handle(): int
    {
        $diskName = config('services.transactions_import.disk', env('TRANSACTIONS_DISK', 'sftp'));
        $remotePath = config('services.transactions_import.sftp_path', env('SFTP_TRANSACTIONS_PATH', 'transactions'));
        $disk = Storage::disk($diskName);

        try {
            $files = $disk->files($remotePath);
        } catch (\Throwable $e) {
            $this->error("Impossible de lister les fichiers sur le disque {$diskName}: " . $e->getMessage());
            Log::error('SFTP/FTP listing error', [
                'disk' => $diskName,
                'path' => $remotePath,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }

        if (empty($files)) {
            $this->info('Aucun fichier à importer.');
            return Command::SUCCESS;
        }

        $importer = app(TransactionImportController::class);

        foreach ($files as $filePath) {
            $this->info("Traitement du fichier: {$filePath}");

            $tmpPath = tempnam(sys_get_temp_dir(), 'txn_');

            try {
                $stream = $disk->readStream($filePath);
                if ($stream === false) {
                    throw new \RuntimeException('Lecture SFTP impossible');
                }

                $tmpHandle = fopen($tmpPath, 'w+');
                stream_copy_to_stream($stream, $tmpHandle);
                fclose($stream);
                fclose($tmpHandle);

                $uploaded = new UploadedFile($tmpPath, basename($filePath), null, null, true);

                $result = $importer->importUploadedFile($uploaded);
                $this->info("Import OK: {$result['imported']} lignes importées, {$result['skipped']} ignorées (date {$result['date']})");

                // Suppression côté SFTP après succès
                $disk->delete($filePath);
            } catch (\Throwable $e) {
                $this->error("Échec import {$filePath}: " . $e->getMessage());
                Log::error('Import transactions depuis SFTP échoué', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } finally {
                if (is_file($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        }

        return Command::SUCCESS;
    }
}
