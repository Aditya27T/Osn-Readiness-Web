<?php

namespace App\Randomizers;

use App\Contracts\Randomizers\RandomizerInterface;
use Random\Randomizer;

class AcakRandomizer implements RandomizerInterface
{
    private readonly Randomizer $randomizer;

    public function __construct()
    {
        $this->randomizer = new Randomizer;
    }

    public function acak(array $items): array
    {
        return $this->randomizer->shuffleArray($items);
    }

    public function ambilAcak(array $items, int $jumlah): array
    {
        if ($jumlah <= 0 || $items === []) {
            return [];
        }

        $keys = $this->randomizer->pickArrayKeys($items, min($jumlah, count($items)));

        return $this->acak(array_map(fn (int|string $key): mixed => $items[$key], $keys));
    }
}
