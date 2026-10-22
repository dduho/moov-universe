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
            "22899175109\t22896776202",        // numéro de téléphone : écarté
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
        foreach ([$eightDigits, $empty, $phoneAsCode, $dupA, $dupB, $locked, $takenElsewhere] as $untouched) {
            $this->assertNull($untouched->fresh()->shortcode, $untouched->numero_flooz);
        }

        $rejected = file_get_contents($report);
        foreach (['22899023081', '22879766785', '22899175109', '22899094953', '22899130723', '22897409033', '22897453635', '22800000000'] as $flooz) {
            $this->assertStringContainsString($flooz, $rejected);
        }
        $this->assertStringNotContainsString('22899979614', $rejected);
    }
}
