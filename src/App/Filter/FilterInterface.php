<?php

namespace App\Filter;

interface FilterInterface
{
    public static function optimizeOptions(array $options): array;

    public function createFromFile(string $sourceFile, string $filterFile, array $options = []): void;

    public function fileTest(string $testFile, string $filterFile, array $options = []): void;
}