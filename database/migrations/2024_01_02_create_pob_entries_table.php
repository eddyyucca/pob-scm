<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pob_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_pob');       // Personal on Board
            $table->integer('total_manpower');  // Total Manpower
            $table->string('informed_by')->nullable();
            $table->string('contact_wa')->nullable();
            $table->string('submitted_by')->nullable(); // nama pelapor
            $table->string('submitted_email')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'date']); // 1 entri per perusahaan per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pob_entries');
    }
};
