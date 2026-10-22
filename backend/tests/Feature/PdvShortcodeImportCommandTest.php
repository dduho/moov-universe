<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PdvShortcodeImportCommandTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    private function file(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sc') . '.txt';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_dry_run_writes_nothing_and_apply_updates_shortcodes_with_the_rules(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $ok = $this->pdv($org, $admin, ['numero_flooz' => '22899979614', 'shortcode' => null]);
        $sixDigits = $this->pdv($org, $admin, ['numero_flooz' => '22896779403', 'shortcode' => null]);
        $replaced = $this->pdv($org, $admin, ['numero_flooz' => '22897419474', 'shortcode' => '9999999']);
        $same = $this->pdv($org, $admin, ['numero_flooz' => '22897430123', 'shortcode' => '5454023']);
        $eightDigits = $this->pdv($org, $admin, ['numero_flooz' => '22899023081', 'shortcode' => null]);
        $empty = $this->pdv($org, $admin, ['numero_flooz' => '22879766785', 'shortcode' => null]);
        $phoneAsCode = $this->pdv($org, $admin, ['numero_flooz' => '22899175109', 'shortcode' => null]);
        $phoneAsCode2 = $this->pdv($org, $admin, ['numero_flooz' => '22899175110', 'shortcode' => null]);
        $dupA = $this->pdv($org, $admin, ['numero_flooz' => '22899094953', 'shortcode' => null]);
        $dupB = $this->pdv($org, $admin, ['numero_flooz' => '22899130723', 'shortcode' => null]);
        $locked = $this->pdv($org, $admin, ['numero_flooz' => '22897409033', 'shortcode' => null, 'is_locked' => true]);
        $takenElsewhere = $this->pdv($org, $admin, ['numero_flooz' => '22897453635', 'shortcode' => null]);
        $this->pdv($org, $admin, ['numero_flooz' => '22897422845', 'shortcode' => '5454021']);

        $content = implode("\n", [
            "Numéro Flooz\tShortcode",
            "22899979614\t5911607",
            "22896779403\t151880",            // 6 chiffres → 0151880
            "22897419474\t5454022",            // remplace 9999999
            "22897430123\t5454023",            // déjà à jour
            "22899023081\t78219902",           // 8 chiffres : écarté
            "22879766785\t",                   // vide : écarté
            "22899175109	22896776202",        // numero de telephone : conserve tel quel
            "22899175110	22896776202",        // meme numero pour un autre PDV : autorise
            "22899094953\t5453968",            // même shortcode pour deux PDV : écartés
            "22899130723\t5453968",
            "22897409033\t5454018",            // PDV verrouillé : écarté
            "22897453635\t5454021",            // porté par un autre PDV : écarté
            "22899979614\t5911607",            // doublon identique : ignoré
            "22800000000\t1234567",            // PDV inconnu : écarté
        ]);
        $file = $this->file($content);
        $report = sys_get_temp_dir() . '/rejected_' . uniqid() . '.csv';

        // Simulation : rien n'est écrit
        $this->artisan('pdv:import-shortcodes', ['file' => $file, '--report' => $report])->assertSuccessful();
        $this->assertNull($ok->fresh()->shortcode);
        $this->assertSame('9999999', $replaced->fresh()->shortcode);

        $this->artisan('pdv:import-shortcodes', ['file' => $file, '--apply' => true, '--report' => $report])->assertSuccessful();

        $this->assertSame('5911607', $ok->fresh()->shortcode);
        $this->assertSame('0151880', $sixDigits->fresh()->shortcode);
        $this->assertSame('5454022', $replaced->fresh()->shortcode);
        $this->assertSame('5454023', $same->fresh()->shortcode);
        $this->assertSame('22896776202', $phoneAsCode->fresh()->shortcode);
        $this->assertSame('22896776202', $phoneAsCode2->fresh()->shortcode);
        foreach ([$eightDigits, $empty, $dupA, $dupB, $locked, $takenElsewhere] as $untouched) {
            $this->assertNull($untouched->fresh()->shortcode, $untouched->numero_flooz);
        }

        $rejected = file_get_contents($report);
        foreach (['22899023081', '22879766785', '22899094953', '22899130723', '22897409033', '22897453635', '22800000000'] as $flooz) {
            $this->assertStringContainsString($flooz, $rejected);
        }
        $this->assertStringNotContainsString('22899979614', $rejected);
    }

    public function test_codes_moved_between_pdv_by_the_file_are_applied_as_a_whole(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $a = $this->pdv($org, $admin, ['numero_flooz' => '22890000001', 'shortcode' => '1111111']);
        $b = $this->pdv($org, $admin, ['numero_flooz' => '22890000002', 'shortcode' => '2222222']);
        $c = $this->pdv($org, $admin, ['numero_flooz' => '22890000003', 'shortcode' => '3333333']);
        $d = $this->pdv($org, $admin, ['numero_flooz' => '22890000004', 'shortcode' => null]);
        // E veut le code de F, mais F n'est pas dans le fichier : refusé
        $e = $this->pdv($org, $admin, ['numero_flooz' => '22890000005', 'shortcode' => null]);
        $f = $this->pdv($org, $admin, ['numero_flooz' => '22890000006', 'shortcode' => '6666666']);

        $file = $this->file(implode("\n", [
            "Numéro Flooz\tShortcode",
            "22890000001\t2222222", // échange A <-> B
            "22890000002\t1111111",
            "22890000004\t3333333", // D prend le code de C, C change
            "22890000003\t4444444",
            "22890000005\t6666666",
        ]));

        $this->artisan('pdv:import-shortcodes', ['file' => $file, '--apply' => true, '--report' => sys_get_temp_dir() . '/r_' . uniqid() . '.csv'])->assertSuccessful();

        $this->assertSame('2222222', $a->fresh()->shortcode);
        $this->assertSame('1111111', $b->fresh()->shortcode);
        $this->assertSame('3333333', $d->fresh()->shortcode);
        $this->assertSame('4444444', $c->fresh()->shortcode);
        $this->assertNull($e->fresh()->shortcode);
        $this->assertSame('6666666', $f->fresh()->shortcode);
    }
}
