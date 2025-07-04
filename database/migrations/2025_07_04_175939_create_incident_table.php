<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentTable extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();  // UUID untuk id yang unik
            $table->uuid('site_id');  // Relasi dengan site_user
            $table->uuid('incident_type_id');  // Relasi dengan incident_types
            $table->string('what_happened')->nullable();  // Deskripsi insiden
            $table->string('where_happened')->nullable();  // Lokasi insiden
            $table->string('why_happened')->nullable();  // Mengapa insiden terjadi
            $table->string('how_happened')->nullable();  // Bagaimana insiden terjadi
            $table->string('persons_involved')->nullable();  // Orang yang terlibat
            $table->string('persons_injured')->nullable();  // Orang yang terluka
            $table->dateTime('happened_at')->nullable();  // Waktu insiden terjadi
            $table->text('details')->nullable();  // Detail kejadian insiden
            $table->string('ops_incharge')->nullable();  // Penanggung jawab
            $table->boolean('reported_to_management')->default(false);  // Apakah laporan ke manajemen
            $table->string('management_report_note')->nullable();  // Catatan laporan manajemen
            $table->boolean('reported_to_police')->default(false);  // Apakah laporan ke polisi
            $table->string('police_report_note')->nullable();  // Catatan laporan polisi
            $table->boolean('property_damaged')->default(false);  // Apakah ada kerusakan properti
            $table->string('damage_note')->nullable();  // Catatan kerusakan properti
            $table->string('image')->nullable();  // Gambar insiden dalam format base64 (CCTV footage)
            $table->timestamps();  // Timestamps untuk created_at dan updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('incidents');
    }
}

