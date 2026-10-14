<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembahasan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('soal_id')->unique()->constrained('soal')->cascadeOnDelete();
            $table->text('isi_pembahasan');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembahasan');
    }
};
