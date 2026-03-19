<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MongoDB doesn't use Schema::create for the same purpose, but we can define indexes here.
        // For 'laravel-mongodb', we use Schema::table or define indexes in the Model.
        // However, defining them in a migration is the 'professional software engineer' way for consistency.
        
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('role');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->index('ville');
            $table->index('etoiles');
            $table->index('estActif');
        });

        Schema::table('chambres', function (Blueprint $table) {
            $table->index('hotelId');
            $table->index('estDisponible');
        });

        Schema::table('reservations', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) { $table->dropIndex(['email']); });
        Schema::table('hotels', function (Blueprint $table) { $table->dropIndex(['ville']); });
        Schema::table('chambres', function (Blueprint $table) { $table->dropIndex(['hotelId']); });
        Schema::table('reservations', function (Blueprint $table) { $table->dropIndex(['clientId']); });
    }
};
