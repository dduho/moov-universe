<?php

namespace Tests\Feature;

use App\Services\TransactionAggregates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class TransactionImportTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    /**
     * Fichier au format de l'export "All Agent Consolidated Report" :
     * date en A6, en-têtes ligne 11, données à partir de la ligne 12.
     */
    private function exportFile(string $date, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A6', 'Start Date: ' . \Carbon\Carbon::parse($date)->format('d/m/Y'));

        $headers = ['PDV_NUMERO', 'COUNT_DEPOT', 'SUM_DEPOT', 'COUNT_RETRAIT', 'RETRAIT_KEYCOST', 'COUNT_GIVE_RECEIVE_OUT_NETWORK'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 11], $header);
        }
        foreach (array_values($rows) as $r => $row) {
            foreach ($row as $i => $value) {
                $sheet->setCellValueExplicit([$i + 1, 12 + $r], $value, $i === 0
                    ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'tx') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, "All Agent Consolidated Report_{$date}.xlsx", null, null, true);
    }

    public function test_import_of_several_files_inserts_data_and_refreshes_aggregates_once_per_month(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $response = $this->post('/api/transactions/import', [
            'files' => [
                $this->exportFile('2026-09-29', [['22890000001', 10, 50000, 4, 1200, 0], ['22890000002', 3, 9000, 1, 300, -1]]),
                $this->exportFile('2026-09-30', [['22890000001', 12, 60000, 5, 1500, 1]]),
                $this->exportFile('2026-10-01', [['22890000001', 7, 20000, 2, 800, 0]]),
            ],
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('total_imported', 4)
            ->assertJsonCount(3, 'success')
            ->assertJsonCount(0, 'errors');

        $this->assertSame(4, DB::table('pdv_transactions')->count());

        // Recalcul fait après la réponse (terminating) : agrégats à jour, file vide
        $september = DB::table('pdv_transaction_monthly')->where('month', '2026-09-01')->where('pdv_numero', '22890000001')->first();
        $this->assertEqualsWithDelta(2700, (float) $september->retrait_keycost, 0.001);
        $this->assertSame(2, (int) $september->days_count);
        $this->assertEqualsWithDelta(-1, (float) DB::table('pdv_transaction_monthly')->where('pdv_numero', '22890000002')->value('count_give_receive_out_network'), 0.001);
        $this->assertTrue(DB::table('transaction_daily_summary')->where('transaction_date', '2026-10-01')->exists());
        $this->assertSame(0, TransactionAggregates::processPendingRefreshes());
    }

    public function test_reimport_updates_existing_rows(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->post('/api/transactions/import', ['files' => [$this->exportFile('2026-09-29', [['22890000001', 10, 50000, 4, 1200, 0]])]], ['Accept' => 'application/json'])
            ->assertJsonPath('total_imported', 1);

        $this->post('/api/transactions/import', ['files' => [$this->exportFile('2026-09-29', [['22890000001', 10, 50000, 4, 9999, 0]])]], ['Accept' => 'application/json'])
            ->assertJsonPath('total_imported', 0)
            ->assertJsonPath('total_updated', 1);

        $this->assertEqualsWithDelta(9999, (float) DB::table('pdv_transaction_monthly')->where('month', '2026-09-01')->value('retrait_keycost'), 0.001);
    }

    public function test_pending_months_are_deduplicated(): void
    {
        foreach (['2026-09-01', '2026-09-15', '2026-09-30', '2026-10-02'] as $date) {
            TransactionAggregates::queueRefresh($date);
        }

        $batches = [];
        $count = TransactionAggregates::processPendingRefreshes(function (array $dates) use (&$batches) {
            $batches[] = $dates;
        });

        $this->assertSame(2, $count); // septembre + octobre, une seule fois chacun
        $this->assertCount(1, $batches);
        $this->assertSame(0, TransactionAggregates::processPendingRefreshes());
    }

    public function test_file_with_unreadable_date_is_reported_as_error(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $file = $this->exportFile('2026-09-29', [['22890000001', 1, 1, 1, 1, 0]]);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $spreadsheet->getActiveSheet()->setCellValue('A6', 'Start Date: inconnue');
        (new Xlsx($spreadsheet))->save($file->getRealPath());

        $this->post('/api/transactions/import', ['files' => [$file]], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(0, 'success')
            ->assertJsonCount(1, 'errors');
    }
}
