<?php

declare(strict_types=1);

namespace App;

use Igoreus\BloomFilter\BloomFilter;

class Utils
{
    public static function createFilterFileName(string $className, array $options): string
    {
        $classNameChunks = explode('\\', $className);
        return strtolower(array_pop($classNameChunks)) . '_' . implode('_', $options) . '.filter';
    }

    public static function cleanWord(string $word): string
    {
        return preg_replace('/\r\n|\r|\n/', '', $word);
    }

    public static function chars(string $word): string
    {
        return '["' . implode('", "', preg_split('//u', $word, 0, PREG_SPLIT_NO_EMPTY)) . '"]';
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function testWordsFromFile(string $filename, BloomFilter $filter, array &$equalList): void
    {
        $allTests = 0;
        $failPositive = 0;
        $failNegative = 0;
        $positive = 0;
        $negative = 0;

        $handle = fopen($filename, 'rb');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $testWord = Utils::cleanWord($line);

                $filterResult = $filter->has($testWord);
                $equalResult = isset($equalList[$testWord]);

                $allTests++;
                if ($filterResult && !$equalResult) {
                    $failPositive++;
                } elseif (!$filterResult && $equalResult) {
                    $failNegative++;
                } elseif ($filterResult && $equalResult) {
                    $positive++;
                } else {
                    $negative++;
                }
            }

            fclose($handle);
        } else {
            echo "Failed to open `{$filename}`" . PHP_EOL;
        }
        echo 'Wyrazów w słowniku:          ' . str_pad((string)count($equalList), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Testów:                      ' . str_pad((string)$allTests, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Wszystkich pozytywnych:      ' . str_pad((string)($positive + $failPositive), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Fałszywie pozytywnych:       ' . str_pad((string)$failPositive, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Prawdziwych pozytywnych:     ' . str_pad((string)$positive, 7, ' ', STR_PAD_LEFT) . ' <-' . PHP_EOL;
        echo 'Wszystkich negatywnych:      ' . str_pad((string)($negative + $failNegative), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Fałszywie negatywnych:       ' . str_pad((string)$failNegative, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Prawdziwych negatywnych:     ' . str_pad((string)$negative, 7, ' ', STR_PAD_LEFT) . ' <-' . PHP_EOL;

        echo sprintf(
                'Skuteczność filtru:           %.2f%% (wszystkie_testy - fałszywy_wynik) / wszystkie_testy',
                ($allTests - ($failPositive + $failNegative))/$allTests * 100
            ) . PHP_EOL;
        echo sprintf(
                'Poprawna pozytywna odpowiedź: %.2f%% (pozytywne_odpowiedzi - fałszywy_wynik) / pozytywne_odpowiedzi',
                ($positive)/($positive + $failPositive) * 100
            ) . PHP_EOL;
    }
}