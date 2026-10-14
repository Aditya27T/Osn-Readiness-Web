<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rapikan tabel users bawaan Laravel agar cocok dengan skema aplikasi:
     * soft delete, penonaktifan akun, dan email unik hanya untuk akun yang belum dihapus.
     *
     * Setiap perubahan dijaga pengecekan kolom karena migration ini ditulis tanpa
     * melihat keadaan tabel users di repo.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletesTz();
            }

            foreach (['role', 'kelas', 'sekolah'] as $kolom) {
                if (Schema::hasColumn('users', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }

            $table->dropUnique('users_email_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX users_email_aktif_unique
             ON users (email) WHERE deleted_at IS NULL'
        );

        Schema::dropIfExists('sessions');
    }

    /**
     * Kembalikan unique email biasa dan buang index parsial. Kolom yang sudah dibuang tidak dikembalikan.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_email_aktif_unique');
            $table->unique('email');
        });

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }
};
