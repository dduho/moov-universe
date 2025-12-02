<?php

namespace Database\Seeders;

use App\Models\GeographicHierarchy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeographicHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // MARITIME
            ['region' => 'MARITIME', 'prefecture' => 'Golfe', 'commune' => 'Lomé'],
            ['region' => 'MARITIME', 'prefecture' => 'Agoè_Nyivé', 'commune' => 'Agoè-Nyivé'],
            ['region' => 'MARITIME', 'prefecture' => 'Avé', 'commune' => 'Tabligbo'],
            ['region' => 'MARITIME', 'prefecture' => 'Lacs', 'commune' => 'Aného'],
            ['region' => 'MARITIME', 'prefecture' => 'Vo', 'commune' => 'Vogan'],
            ['region' => 'MARITIME', 'prefecture' => 'Yoto', 'commune' => 'Tabligbo'],
            ['region' => 'MARITIME', 'prefecture' => 'Zio', 'commune' => 'Tsévié'],
            ['region' => 'MARITIME', 'prefecture' => 'Bas_Mono', 'commune' => 'Baguida'],
            
            // PLATEAUX
            ['region' => 'PLATEAUX', 'prefecture' => 'Kloto', 'commune' => 'Kpalimé'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Agou', 'commune' => 'Agou-Gadzépé'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Akébou', 'commune' => 'Kougnohou'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Amou', 'commune' => 'Amlamé'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Anié', 'commune' => 'Anié'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Danyi', 'commune' => 'Danyi'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Est_Mono', 'commune' => 'Elavagnon'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Haho', 'commune' => 'Notsé'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Wawa', 'commune' => 'Badou'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Amou_Oblo', 'commune' => 'Kpalimé'],
            ['region' => 'PLATEAUX', 'prefecture' => 'Kpélé', 'commune' => 'Kpélé'],
            
            // CENTRALE
            ['region' => 'CENTRALE', 'prefecture' => 'Tchaoudjo', 'commune' => 'Sokodé'],
            ['region' => 'CENTRALE', 'prefecture' => 'Blitta', 'commune' => 'Blitta'],
            ['region' => 'CENTRALE', 'prefecture' => 'Mô', 'commune' => 'Mô'],
            ['region' => 'CENTRALE', 'prefecture' => 'Sotouboua', 'commune' => 'Sotouboua'],
            ['region' => 'CENTRALE', 'prefecture' => 'Tchamba', 'commune' => 'Tchamba'],
            
            // KARA
            ['region' => 'KARA', 'prefecture' => 'Kozah', 'commune' => 'Kara'],
            ['region' => 'KARA', 'prefecture' => 'Assoli', 'commune' => 'Bafilo'],
            ['region' => 'KARA', 'prefecture' => 'Bassar', 'commune' => 'Bassar'],
            ['region' => 'KARA', 'prefecture' => 'Binah', 'commune' => 'Pagouda'],
            ['region' => 'KARA', 'prefecture' => 'Dankpen', 'commune' => 'Guérin-Kouka'],
            ['region' => 'KARA', 'prefecture' => 'Doufelgou', 'commune' => 'Niamtougou'],
            ['region' => 'KARA', 'prefecture' => 'Kéran', 'commune' => 'Kéran'],
            
            // SAVANES
            ['region' => 'SAVANES', 'prefecture' => 'Tône', 'commune' => 'Dapaong'],
            ['region' => 'SAVANES', 'prefecture' => 'Cinkassé', 'commune' => 'Cinkassé'],
            ['region' => 'SAVANES', 'prefecture' => 'Kpendjal', 'commune' => 'Mandouri'],
            ['region' => 'SAVANES', 'prefecture' => 'Kpendjal_Ouest', 'commune' => 'Kpendjal'],
            ['region' => 'SAVANES', 'prefecture' => 'Oti', 'commune' => 'Mango'],
            ['region' => 'SAVANES', 'prefecture' => 'Oti_Sud', 'commune' => 'Oti'],
            ['region' => 'SAVANES', 'prefecture' => 'Tandjouaré', 'commune' => 'Tandjouaré'],
        ];

        foreach ($data as $item) {
            GeographicHierarchy::firstOrCreate($item);
        }
    }
}
