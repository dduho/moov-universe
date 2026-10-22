<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

/**
 * Le fichier d'import PDV réel (modèle « DEALER / NUMERO FLOOZ / SHORT CODE / … / COMMERCIAL ») :
 * chaque colonne du fichier doit se retrouver sur le PDV importé.
 */
class PdvImportAllFieldsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    private const HEADERS = [
        'N°', 'DEALER', 'NUMERO FLOOZ', 'SHORT CODE', 'NOM DU POINT', 'PROFIL', 'FIRSTNAME', 'LASTNAME', 'GENDER',
        'IDDESCRIPTION', 'IDNUMBER', 'IDEXPIRYDATE', 'NATIONALITY', 'PROFESSION', "TYPE D'ACTIVITE", 'LOCALISATION',
        'REGION', 'PREFECTURE', 'COMMUNE', 'CANTON', 'QUARTIER', 'VILLE', 'LATITUDE', 'LONGITUDE',
        'NUMERO PROPRIETAIRE DU PDV', 'AUTRE CONTACT DU PDV', 'SEXE', 'NIF', 'REGIME FISCAL', 'NIF PARTICULIER',
        'SUPPORT DE VISIBILITE', 'ETAT DU SUPPORT', 'NUMERO CAGNT', 'COMMERCIAL',
    ];

    private const ROW = [
        '7', 'PSDTT', '22879940633', '1140834', 'PSDTT ETS ALBATROS 0633', 'DISTROWNIF', 'YAO', 'DIZEWE', 'MASCULIN',
        "CARTE D ELECTEUR", 'A4-31-084-03-06-02-03-0', '03/06/2029', 'TOGOLAISE', 'COMMERCANT', 'COMMERCE GENERAL',
        'Kougnohou qt petit juste derriere le bar valee akim',
        'PLATEAUX', 'Akébou', 'Akébou_1', 'Kougnohou', 'PETIT KOUGNOHOU', 'KOUGNOHOU', '7.656135', '0.791660',
        '22897308829', '92308370', 'FEMININ', '1001665855', 'Sans TVA', '1002027875',
        'DRAPELET', 'MAUVAIS', '22897095270', 'ZOGLI Kossi Mensah',
    ];

    private function xlsx(array $headers, array $row): UploadedFile
    {
        $sheet = ($spreadsheet = new Spreadsheet())->getActiveSheet();
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($row as $i => $value) {
            $sheet->setCellValueExplicit([$i + 1, 2], (string) $value, DataType::TYPE_STRING);
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'pdv.xlsx', null, null, true);
    }

    public function test_every_column_of_the_import_file_reaches_the_pdv(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        Sanctum::actingAs($admin);

        $this->post('/api/point-of-sales/import', [
            'file' => $this->xlsx(self::HEADERS, self::ROW),
            'organization_id' => $org->id,
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('summary.imported', 1);

        $pdv = \App\Models\PointOfSale::where('numero_flooz', '22879940633')->firstOrFail();

        $expected = [
            'numero' => 7,
            'dealer_name' => $org->name,   // le dealer vient de l'organisation choisie à l'import
            'shortcode' => '1140834',
            'nom_point' => 'PSDTT ETS ALBATROS 0633',
            'profil' => 'DISTROWNIF',
            'firstname' => 'YAO',
            'lastname' => 'DIZEWE',
            'gender' => 'M',
            'id_description' => 'elector',
            'id_number' => 'A4-31-084-03-06-02-03-0',
            'nationality' => 'TOGOLAISE',
            'profession' => 'COMMERCANT',
            'type_activite' => 'COMMERCE GENERAL',
            'localisation' => 'Kougnohou qt petit juste derriere le bar valee akim',
            'region' => 'PLATEAUX',
            'prefecture' => 'Akébou',
            'commune' => 'Akébou_1',
            'canton' => 'Kougnohou',
            'quartier' => 'PETIT KOUGNOHOU',
            'ville' => 'KOUGNOHOU',
            'numero_proprietaire' => '22897308829',
            'autre_contact' => '22892308370',
            'sexe_gerant' => 'F',
            'nif' => '1001665855',
            'regime_fiscal' => 'Réel sans TVA',
            'nif_particulier' => '1002027875',
            'support_visibilite' => 'Drapelet',
            'etat_support' => 'MAUVAIS',
            'numero_cagnt' => '22897095270',
            'commercial' => 'ZOGLI Kossi Mensah',
        ];

        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $pdv->{$field}, "Champ « {$field} » mal importé");
        }
        $this->assertSame('2029-06-03', $pdv->id_expiry_date->format('Y-m-d'));
        $this->assertEqualsWithDelta(7.656135, (float) $pdv->latitude, 0.000001);
        $this->assertEqualsWithDelta(0.79166, (float) $pdv->longitude, 0.000001);
    }

    public function test_day_first_dates_are_read_as_dd_mm_yyyy_even_when_the_day_is_above_12(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        Sanctum::actingAs($admin);

        $headers = ['NUMERO FLOOZ', 'NOM DU POINT', 'REGION', 'IDEXPIRYDATE', 'DATE DE NAISSANCE'];
        $this->post('/api/point-of-sales/import', [
            'file' => $this->xlsx($headers, ['22879940633', 'PDV', 'PLATEAUX', '15/01/2030', '02/04/1990']),
            'organization_id' => $org->id,
        ], ['Accept' => 'application/json'])->assertOk();

        $pdv = \App\Models\PointOfSale::where('numero_flooz', '22879940633')->firstOrFail();
        $this->assertSame('2030-01-15', $pdv->id_expiry_date?->format('Y-m-d'));
        $this->assertSame('1990-04-02', $pdv->date_of_birth?->format('Y-m-d'));
    }

    public function test_reimport_updates_the_new_fields_of_an_existing_pdv(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['numero_flooz' => '22879940633']);
        Sanctum::actingAs($admin);

        $this->post('/api/point-of-sales/import', [
            'file' => $this->xlsx(self::HEADERS, self::ROW),
            'organization_id' => $org->id,
            'allow_updates' => '1',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('summary.updated', 1);

        $fresh = $pdv->fresh();
        $this->assertSame('ZOGLI Kossi Mensah', $fresh->commercial);
        $this->assertSame('1002027875', $fresh->nif_particulier);
        $this->assertSame('Kougnohou qt petit juste derriere le bar valee akim', $fresh->localisation);
    }
}
