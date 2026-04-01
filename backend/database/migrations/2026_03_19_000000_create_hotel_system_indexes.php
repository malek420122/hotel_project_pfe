<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->isMongoConnection()) {
            return;
        }

        Schema::connection('mongodb')->table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('role');
        });

        Schema::connection('mongodb')->table('hotels', function (Blueprint $table) {
            $table->index('ville');
            $table->index('etoiles');
            $table->index('estActif');
        });

        Schema::connection('mongodb')->table('chambres', function (Blueprint $table) {
            $table->index('hotelId');
            $table->index('estDisponible');
        });

        Schema::connection('mongodb')->table('reservations', function (Blueprint $table) {
            $table->index('clientId');
            $table->index('hotelId');
            $table->index('chambreId');
            $table->index('dateArrivee');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->isMongoConnection()) {
            return;
        }

        Schema::connection('mongodb')->table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['role']);
        });
        Schema::connection('mongodb')->table('hotels', function (Blueprint $table) {
            $table->dropIndex(['ville']);
            $table->dropIndex(['etoiles']);
            $table->dropIndex(['estActif']);
        });
        Schema::connection('mongodb')->table('chambres', function (Blueprint $table) {
            $table->dropIndex(['hotelId']);
            $table->dropIndex(['estDisponible']);
        });
        Schema::connection('mongodb')->table('reservations', function (Blueprint $table) {
            $table->dropIndex(['clientId']);
            $table->dropIndex(['hotelId']);
            $table->dropIndex(['chambreId']);
            $table->dropIndex(['dateArrivee']);
            $table->dropIndex(['statut']);
        });
    }

    private function isMongoConnection(): bool
    {
        $default = config('database.default');
        $driver = DB::connection($default)->getDriverName();

        return $default === 'mongodb' || $driver === 'mongodb';
    }
};
