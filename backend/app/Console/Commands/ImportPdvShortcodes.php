<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Renseigne les shortcodes des PDV à partir d'un fichier à deux colonnes (Numéro Flooz, Shortcode).
 *
 * Simulation par défaut : rien n'est écrit sans --apply. Un shortcode fait 7 chiffres ; avec 6 chiffres
 * on considère qu'un 0 initial a été perdu (Excel) et on le restitue. Tout ce qui est ambigu est écarté
 * et listé dans un rapport CSV plutôt qu'appliqué.
 */
class ImportPdvShortcodes extends Command
{
    protected $signature = 'pdv:import-shortcodes {file : Fichier .xlsx/.xls/.csv/.txt (Numéro Flooz ; Shortcode)} {--apply : Écrit réellement en base} {--report= : Chemin du rapport CSV des lignes écartées}';

    protected $description = 'Met à jour le shortcode des PDV depuis un fichier (Numéro Flooz, Shortcode) — simulation sans --apply';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $lines = $this->readRows($path);
        $rejected = [];   // [ligne, numéro, shortcode, raison]
        $candidates = []; // numéro Flooz => [ligne, shortcode]

        foreach ($lines as [$line, $rawFlooz, $rawCode]) {
            $flooz = preg_replace('/\D+/', '', (string) $rawFlooz);
            $code = trim((string) $rawCode);

            if (strlen($flooz) !== 11) {
                $rejected[] = [$line, $rawFlooz, $rawCode, 'numéro Flooz invalide (11 chiffres attendus)'];
                continue;
            }
            if ($code === '') {
                $rejected[] = [$line, $flooz, '', 'shortcode vide'];
                continue;
            }
            if (!preg_match('/^\d+$/', $code)) {
                $rejected[] = [$line, $flooz, $code, 'shortcode non numérique'];
                continue;
            }
            if (strlen($code) === 6) {
                $code = '0' . $code; // zéro initial perdu
            }
            // Un numéro de téléphone (11 chiffres, indicatif 228) tient lieu de shortcode pour certains PDV : conservé tel quel
            if (!$this->isPhoneCode($code) && strlen($code) !== 7) {
                $rejected[] = [$line, $flooz, $code, 'shortcode de ' . strlen($code) . ' chiffres (7 attendus)'];
                continue;
            }

            // Même PDV répété : identique = doublon ignoré, différent = ambigu
            if (isset($candidates[$flooz])) {
                if ($candidates[$flooz][1] !== $code) {
                    $rejected[] = [$line, $flooz, $code, "numéro présent deux fois avec des shortcodes différents (ligne {$candidates[$flooz][0]} : {$candidates[$flooz][1]})"];
                }
                continue;
            }
            $candidates[$flooz] = [$line, $code];
        }

        // Un même shortcode donné à plusieurs numéros du fichier : on n'en applique aucun
        $byCode = [];
        foreach ($candidates as $flooz => [$line, $code]) {
            if (!$this->isPhoneCode($code)) { // un numéro de téléphone peut légitimement servir à plusieurs PDV
                $byCode[$code][] = $flooz;
            }
        }
        foreach ($byCode as $code => $floozList) {
            if (count($floozList) > 1) {
                foreach ($floozList as $flooz) {
                    $rejected[] = [$candidates[$flooz][0], $flooz, $code, 'même shortcode attribué à ' . count($floozList) . ' PDV dans le fichier'];
                    unset($candidates[$flooz]);
                }
            }
        }

        // Rapprochement avec la base
        $stats = ['to_set' => 0, 'to_change' => 0, 'unchanged' => 0];
        $pending = [];  // id du PDV => [ligne, numéro Flooz, nouveau shortcode, ancien shortcode]
        $holders = [];  // shortcode => [[id, numéro Flooz], ...] : PDV qui le portent aujourd'hui en base

        foreach (array_chunk(array_keys($candidates), 1000) as $chunk) {
            $pdvs = DB::table('point_of_sales')->whereIn('numero_flooz', $chunk)->get(['id', 'numero_flooz', 'shortcode', 'is_locked'])->keyBy('numero_flooz');

            $codes = array_map(fn ($f) => $candidates[$f][1], $chunk);
            foreach (DB::table('point_of_sales')->whereIn('shortcode', $codes)->get(['id', 'numero_flooz', 'shortcode']) as $holder) {
                $holders[$holder->shortcode][] = [$holder->id, $holder->numero_flooz];
            }

            foreach ($chunk as $flooz) {
                [$line, $code] = $candidates[$flooz];
                $pdv = $pdvs->get($flooz);

                if (!$pdv) {
                    $rejected[] = [$line, $flooz, $code, 'aucun PDV avec ce numéro Flooz'];
                } elseif ($pdv->is_locked) {
                    $rejected[] = [$line, $flooz, $code, 'PDV verrouillé par un administrateur'];
                } elseif ($pdv->shortcode === $code) {
                    $stats['unchanged']++;
                } else {
                    $pending[$pdv->id] = [$line, $flooz, $code, $pdv->shortcode];
                }
            }
        }

        // Un shortcode ne peut aller qu'à un PDV : il est refusé s'il reste porté par un autre PDV après mise à jour.
        // Un PDV qui reçoit lui-même un nouveau shortcode dans ce fichier libère l'ancien (échanges et permutations
        // inclus) ; on répète tant qu'un refus en entraîne un autre.
        do {
            $dropped = false;
            foreach ($pending as $id => [$line, $flooz, $code]) {
                if ($this->isPhoneCode($code)) {
                    continue; // un numéro de téléphone peut servir à plusieurs PDV
                }
                foreach ($holders[$code] ?? [] as [$holderId, $holderFlooz]) {
                    if ($holderId !== $id && !isset($pending[$holderId])) {
                        $rejected[] = [$line, $flooz, $code, "shortcode déjà porté par un autre PDV de la base (numéro {$holderFlooz})"];
                        unset($pending[$id]);
                        $dropped = true;
                        break;
                    }
                }
            }
        } while ($dropped);

        $updates = [];
        $changes = [];
        foreach ($pending as $id => [$line, $flooz, $code, $old]) {
            $updates[$id] = $code;
            if ($old === null || $old === '' || strtoupper($old) === 'N/A') {
                $stats['to_set']++;
            } else {
                $stats['to_change']++;
                $changes[] = [$line, $flooz, $old, $code];
            }
        }

        if ($this->option('apply')) {
            DB::transaction(function () use ($updates) {
                // Mise à jour directe : ni horodatage ni auteur ne changent, seul le shortcode est renseigné
                foreach ($updates as $id => $code) {
                    DB::table('point_of_sales')->where('id', $id)->update(['shortcode' => $code]);
                }
            });
        }

        $report = $this->option('report') ?: storage_path('app/shortcodes_rejected_' . date('Ymd_His') . '.csv');
        $handle = fopen($report, 'w');
        fputcsv($handle, ['ligne', 'numero_flooz', 'shortcode', 'raison']);
        foreach ($rejected as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $this->info(($this->option('apply') ? 'APPLIQUÉ' : 'SIMULATION (rien n\'est écrit, ajouter --apply)') . ' — ' . count($lines) . ' lignes lues');
        $this->line("  Shortcodes renseignés (PDV sans shortcode) : {$stats['to_set']}");
        $this->line("  Shortcodes remplacés (valeur différente)   : {$stats['to_change']}");
        $this->line("  Déjà à jour                                : {$stats['unchanged']}");
        $this->line('  Lignes écartées                            : ' . count($rejected));
        $this->line("  Rapport des lignes écartées : {$report}");

        $reasons = [];
        foreach ($rejected as $row) {
            $key = preg_replace('/\(.*\)|\d+ PDV|\d+ chiffres/u', '', $row[3]);
            $reasons[$key] = ($reasons[$key] ?? 0) + 1;
        }
        arsort($reasons);
        foreach ($reasons as $reason => $count) {
            $this->line("    - {$count} × " . trim($reason));
        }
        if ($changes) {
            $this->line('  Exemples de remplacements (ancien → nouveau) :');
            foreach (array_slice($changes, 0, 10) as [$line, $flooz, $old, $new]) {
                $this->line("    ligne {$line} · {$flooz} : {$old} → {$new}");
            }
        }

        return self::SUCCESS;
    }

    private function isPhoneCode(string $code): bool
    {
        return strlen($code) === 11 && str_starts_with($code, '228');
    }

    /** @return array<int, array{0:int,1:mixed,2:mixed}> [n° de ligne du fichier, numéro Flooz, shortcode] */
    private function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['txt', 'tsv', 'csv'], true)) {
            $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));
            $table = [];
            foreach (preg_split('/\r\n|\n|\r/', $content) as $text) {
                $table[] = trim($text) === '' ? [] : preg_split('/[\t;,]/', $text);
            }
        } else {
            $table = IOFactory::load($path)->getSheet(0)->toArray(null, true, true, false);
        }

        // Colonnes repérées par leur intitulé (« Numéro Flooz », « Shortcode »), sinon les deux premières
        $floozCol = 0;
        $codeCol = 1;
        $first = $table[0] ?? [];
        if ($first && !preg_match('/\d{6,}/', implode('', array_map('strval', $first)))) {
            foreach ($first as $i => $header) {
                $name = preg_replace('/[^a-z]/', '', strtolower(\Illuminate\Support\Str::ascii((string) $header)));
                if (str_contains($name, 'flooz') && $name !== 'shortcode') {
                    $floozCol = $i;
                } elseif ($name === 'shortcode') {
                    $codeCol = $i;
                }
            }
            array_shift($table);
            $offset = 2;
        } else {
            $offset = 1;
        }

        $rows = [];
        foreach ($table as $i => $cells) {
            $flooz = $cells[$floozCol] ?? '';
            $code = $cells[$codeCol] ?? '';
            if (trim((string) $flooz) !== '' || trim((string) $code) !== '') {
                $rows[] = [$i + $offset, $flooz, $code];
            }
        }

        return $rows;
    }
}
