<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Menghapus permanen akun yang sudah di-soft delete melewati masa retensi.
 *
 * Dipakai scheduler setiap hari. Data pengerjaan ikut terhapus karena
 * foreign key ke users memakai cascadeOnDelete.
 */
class PurgeAkunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $batas = now()->subDays(config('osn.retensi_akun_hari'));

        $jumlah = 0;

        User::withTrashed()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $batas)
            ->select('id')
            ->chunkById(config('osn.purge_akun_chunk'), function ($users) use (&$jumlah): void {
                foreach ($users as $user) {
                    // forceDelete, bukan delete, supaya barisnya hilang dan
                    // cascade ikut membersihkan tabel jawaban dan riwayat.
                    $user->forceDelete();
                    $jumlah++;
                }
            });

        if ($jumlah > 0) {
            Log::info('Purge akun selesai.', [
                'jumlah' => $jumlah,
                'batas_retensi' => $batas->toDateString(),
            ]);
        }
    }
}
