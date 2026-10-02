<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque modèle doit pointer vers une table créée par les migrations
 * (OAuthToken visait "o_auth_tokens" et l'import Outlook échouait chaque jour).
 */
class ModelTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_model_table_exists(): void
    {
        $missing = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');
            if (!is_subclass_of($class, Model::class)) {
                continue;
            }

            try {
                $class::query()->limit(1)->get();
            } catch (\Illuminate\Database\QueryException $e) {
                $missing[] = $class . ' -> ' . (new $class)->getTable();
            }
        }

        $this->assertSame([], $missing);
    }
}
