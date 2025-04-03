<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTourTemplateIdToTripsTable extends Migration
{
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedBigInteger('tour_template_id')->after('id');

            // Agregar la clave foránea
            $table->foreign('tour_template_id')
                ->references('id')
                ->on('tour_templates')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Primero eliminar la clave foránea
            $table->dropForeign(['tour_template_id']);
            $table->dropColumn('tour_template_id');
        });
    }
}
