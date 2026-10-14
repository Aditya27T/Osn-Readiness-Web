<?php

namespace App\Contracts\Services;

use App\DTOs\PermintaanSoal;
use App\DTOs\SoalTerpilih;
use App\Exceptions\BankSoalTidakCukupException;

interface SoalPickerServiceInterface
{
    /**
     * Susun paket soal sesuai permintaan, atau gagal seluruhnya.
     *
     * Tidak pernah mengembalikan susunan yang kurang dari yang diminta dan
     * tidak menulis apa pun ke database. Pemanggil yang membungkus pembuatan
     * baris pengerjaan dalam satu transaksi.
     *
     * @throws BankSoalTidakCukupException
     */
    public function pilih(PermintaanSoal $permintaan): SoalTerpilih;
}
