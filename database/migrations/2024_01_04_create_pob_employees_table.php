<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pob_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pob_entry_id')->constrained('pob_entries')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date'); // copy dari pob_entry untuk query langsung

            // Identitas
            $table->string('id_number');        // minepermit atau KTP visitor
            $table->enum('id_type', ['minepermit', 'ktp'])->default('minepermit');
            $table->string('name');
            $table->string('position')->nullable();    // jabatan
            $table->string('department')->nullable();  // departemen
            $table->enum('employee_type', ['employee', 'visitor'])->default('employee');

            $table->timestamps();

            // index untuk pencarian cepat
            $table->index(['company_id', 'date']);
            $table->index(['date']);
            $table->index(['id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pob_employees');
    }
};
