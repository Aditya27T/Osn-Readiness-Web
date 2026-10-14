<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci import konten: id_sumber dari berkas Markdown/JSON sumber.
     * Nullable supaya baris yang dibuat lewat menu admin tidak perlu mengisinya,
     * dan unik per tabel supaya satu berkas selalu memetakan ke satu baris.
     */
    public function up(): void
    {
        Schema::table('materi', function (Blueprint $table): void {
            $table->string('id_sumber', 100)->nullable()->unique()->after('id');
        });

        Schema::table('soal', function (Blueprint $table): void {
            $table->string('id_sumber', 100)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('soal', function (Blueprint $table): void {
            $table->dropUnique('soal_id_sumber_unique');
            $table->dropColumn('id_sumber');
        });

        Schema::table('materi', function (Blueprint $table): void {
            $table->dropUnique('materi_id_sumber_unique');
            $table->dropColumn('id_sumber');
        });
    }
};
