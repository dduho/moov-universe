<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PdvImportShortcodeTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    /** @param array<int, array> $rows lignes de données ; la première ligne du fichier contient les en-têtes */
    private function xlsx(array $headers, array $rows): UploadedFile
    {
        $sheet = ($spreadsheet = new Spreadsheet())->getActiveSheet();
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($rows as $r => $row) {
            foreach ($row as $i => $value) {
                $sheet->setCellValueExplicit([$i + 1, $r + 2], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'pdv.xlsx', null, null, true);
    }

    private function import(UploadedFile $file, int $organizationId)
    {
        return $this->post('/api/point-of-sales/import', [
            'file' => $file,
            'organization_id' => $organizationId,
            'allow_updates' => '1',
        ], ['Accept' => 'application/json']);
    }

    public function test_reimport_without_shortcode_keeps_the_known_shortcode(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['numero_flooz' => '22896777558', 'shortcode' => '1311244', 'nom_point' => 'ANCIEN NOM']);
        Sanctum::actingAs($admin);

        $headers = ['nom_point', 'numero_flooz', 'region', 'prefecture', 'firstname', 'lastname'];
        $response = $this->import($this->xlsx($headers, [['NOUVEAU NOM', '22896777558', 'MARITIME', 'Golfe', 'A', 'B']]), $org->id);

        $response->assertOk()->assertJsonPath('summary.updated', 1);
        $fresh = $pdv->fresh();
        $this->assertSame('NOUVEAU NOM', $fresh->nom_point); // la mise à jour a bien eu lieu...
        $this->assertSame('1311244', $fresh->shortcode);    // ...sans effacer le shortcode
    }

    public function test_reimport_with_empty_or_na_shortcode_keeps_the_known_shortcode(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['numero_flooz' => '22896777558', 'shortcode' => '1311244']);
        Sanctum::actingAs($admin);

        $headers = ['nom_point', 'numero_flooz', 'shortcode', 'region', 'prefecture', 'firstname', 'lastname'];
        foreach (['', 'N/A'] as $value) {
            $this->import($this->xlsx($headers, [['PDV', '22896777558', $value, 'MARITIME', 'Golfe', 'A', 'B']]), $org->id)->assertOk();
            $this->assertSame('1311244', $pdv->fresh()->shortcode, "valeur de fichier « {$value} »");
        }
    }

    public function test_reimport_with_a_shortcode_fills_or_updates_it(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $withoutShortcode = $this->pdv($org, $admin, ['numero_flooz' => '22896777558', 'shortcode' => null]);
        Sanctum::actingAs($admin);

        $headers = ['nom_point', 'numero_flooz', 'shortcode', 'region', 'prefecture', 'firstname', 'lastname'];
        $this->import($this->xlsx($headers, [['PDV', '22896777558', '1311244', 'MARITIME', 'Golfe', 'A', 'B']]), $org->id)->assertOk();

        $this->assertSame('1311244', $withoutShortcode->fresh()->shortcode);
    }
}
