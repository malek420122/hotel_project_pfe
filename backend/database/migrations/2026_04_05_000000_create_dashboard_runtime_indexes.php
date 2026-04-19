<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->isMongoConnection()) {
            return;
        }

        Schema::connection('mongodb')->table('reservations', function (Blueprint $table) {
            $table->index('reference');
            $table->index('dateDepart');
            $table->index('created_at');
        });

        Schema::connection('mongodb')->table('paiements', function (Blueprint $table) {
            $table->index('reservationId');
            $table->index('statut');
            $table->index('transactionId');
            $table->index('created_at');
        });

        Schema::connection('mongodb')->table('promotions', function (Blueprint $table) {
            $table->index('codePromo');
            $table->index('estActive');
            $table->index('dateFin');
        });

        Schema::connection('mongodb')->table('notifications', function (Blueprint $table) {
            $table->index('userId');
            $table->index('estLue');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        if (! $this->isMongoConnection()) {
            return;
        }

        Schema::connection('mongodb')->table('reservations', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->dropIndex(['dateDepart']);
            $table->dropIndex(['created_at']);
        });

        Schema::connection('mongodb')->table('paiements', function (Blueprint $table) {
            $table->dropIndex(['reservationId']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['transactionId']);
            $table->dropIndex(['created_at']);
        });

        Schema::connection('mongodb')->table('promotions', function (Blueprint $table) {
            $table->dropIndex(['codePromo']);
            $table->dropIndex(['estActive']);
            $table->dropIndex(['dateFin']);
        });

        Schema::connection('mongodb')->table('notifications', function (Blueprint $table) {
            $table->dropIndex(['userId']);
            $table->dropIndex(['estLue']);
            $table->dropIndex(['created_at']);
        });
    }

    private function isMongoConnection(): bool
    {
        $default = config('database.default');
        $driver = DB::connection($default)->getDriverName();

        return $default === 'mongodb' || $driver === 'mongodb';
    }
};
