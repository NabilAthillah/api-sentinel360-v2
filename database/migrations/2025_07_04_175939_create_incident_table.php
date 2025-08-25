<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentTable extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('person_involved');
            $table->string('how_it_happened');
            $table->date('incident_date');
            $table->date('reported_date');
            $table->string('conclution');
            $table->boolean('reported_to_management');
            $table->boolean('reported_to_police');
            $table->boolean('any_damages_to_property');
            $table->boolean('any_pictures_attached');
            $table->boolean('cctv_footage');
            $table->string('picture');
            $table->string('footage');
            $table->string('id_incident_type');
            $table->foreign('id_incident_type')->references('id')->on('incident_types');
            $table->string('id_site');
            $table->foreign('id_site')->references('id')->on('sites');
            $table->string('id_user');
            $table->foreign('id_user')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('incidents');
    }
}

