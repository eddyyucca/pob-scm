<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('pob_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_pob');
            $table->integer('total_manpower');
            $table->string('informed_by')->nullable();
            $table->string('contact_wa')->nullable();
            $table->string('submitted_email')->nullable();
            $table->timestamps();
            $table->unique(['company_id','date']);
        });
    }
    public function down(): void { Schema::dropIfExists('pob_entries'); }
};
