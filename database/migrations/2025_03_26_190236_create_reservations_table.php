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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // Relaciones con las otras tablas
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('payment_id');

            // Nuevos campos
            $table->text('descripcion')->nullable();
            // Campo status como ENUM con tres opciones y por defecto 'not paid'
            $table->enum('status', ['not paid', 'pass', 'paid'])->default('not paid');
            $table->date('date')->nullable();

            $table->timestamps();

            // Claves foráneas
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
