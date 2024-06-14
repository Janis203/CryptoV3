<?php

namespace App;

class Currency
{
    private string $name;
    private string $symbol;
    private int $rank;
    private float $price;

    public function __construct(string $name, string $symbol, int $rank, float $price)
    {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->rank = $rank;
        $this->price = $price;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}