<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables d'agrégats des transactions (alimentées par analytics:refresh-aggregates) :
     * - pdv_transaction_monthly : un mois par PDV (~30x moins de lignes que pdv_transactions)
     * - transaction_daily_summary : un jour par dealer et par région (séries quotidiennes)
     */
    public function up(): void
    {
        Schema::create('pdv_transaction_monthly', function (Blueprint $table) {
            $table->date('month'); // premier jour du mois
            $table->string('pdv_numero', 64);
            $table->unsignedSmallInteger('days_count')->default(0);   // jours présents dans l'export
            $table->unsignedSmallInteger('active_days')->default(0);  // jours avec dépôt ou retrait
            $this->metricColumns($table);
            $table->primary(['month', 'pdv_numero']);
            $table->index(['pdv_numero', 'month']);
        });

        Schema::create('transaction_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->unsignedBigInteger('organization_id')->nullable(); // dealer du PDV au moment du calcul
            $table->string('region', 20)->nullable();
            $table->unsignedInteger('pdv_count')->default(0);   // PDV présents dans l'export ce jour
            $table->unsignedInteger('pdv_actifs')->default(0);  // PDV avec dépôt ou retrait ce jour
            $this->metricColumns($table);
            $table->unique(['transaction_date', 'organization_id', 'region'], 'tds_date_org_region_unique');
            $table->index(['organization_id', 'transaction_date']);
        });

        // Index en double sur pdv_transactions : (pdv_numero) x3, (transaction_date) x2,
        // et (pdv_numero, transaction_date) déjà couvert par l'index unique.
        foreach (['idx_pdv_numero', 'pdv_transactions_pdv_numero_index', 'idx_pdv_date', 'idx_transaction_date'] as $index) {
            if ($this->indexExists('pdv_transactions', $index)) {
                DB::statement("ALTER TABLE pdv_transactions DROP INDEX `{$index}`");
            }
        }

        Artisan::call('analytics:refresh-aggregates', ['--all' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_daily_summary');
        Schema::dropIfExists('pdv_transaction_monthly');

        Schema::table('pdv_transactions', function (Blueprint $table) {
            $table->index('pdv_numero', 'idx_pdv_numero');
            $table->index(['pdv_numero', 'transaction_date'], 'idx_pdv_date');
        });
    }

    private function metricColumns(Blueprint $table): void
    {
        foreach (\App\Services\TransactionAggregates::COUNT_COLUMNS as $column) {
            $table->unsignedBigInteger($column)->default(0);
        }
        foreach (\App\Services\TransactionAggregates::SUM_COLUMNS as $column) {
            $table->decimal($column, 18, 2)->default(0);
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
