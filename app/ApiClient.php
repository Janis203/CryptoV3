<?php

namespace App;

interface ApiClient
{
    public function getList(int $start, int $limit, string $convert): array;

    public function getSymbol(string $symbol, string $convert): array;
}