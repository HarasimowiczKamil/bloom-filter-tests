<?php

declare(strict_types=1);

namespace App;

use Igoreus\BloomFilter\BloomFilter;

class Utils
{
    public static function cleanWord(string $word): string
    {
        //preg_match_all('/([^a-ząćęłńóśźż]+)/ui', $word, $matches);
        //var_dump($matches);
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

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function testWordsFromFile(string $filename, BloomFilter $filter, array &$equalList, bool $printAllWords = true): void
    {
//        $testWord = 'słowo';
//        echo str_pad($testWord, 18 - mb_strlen($testWord) + strlen($testWord), ' ', STR_PAD_LEFT);
//        echo ' | ';
//        echo ' F ';
//        echo ' | ';
//        echo ' = ';
//        echo ' | ';
//        echo 'mb_strlen';
//        echo ' | ';
//        echo 'strlen';
//        echo ' | ';
//        echo 'różnica wyniku';
//        echo PHP_EOL;

        $allTests = 0;
        $failPositive = 0;
        $failNegative = 0;
        $positive = 0;
        $negative = 0;
        $control = 0;

        $handle = fopen($filename, 'r');
        if ($handle) {
            $i = 0;
            while (($line = fgets($handle)) !== false) {
                $testWord = Utils::cleanWord($line);

                $filterResult = $filter->has($testWord);
                $equalResult = isset($equalList[$testWord]);

                if ($printAllWords/* || $equalResult || $equalResult !== $filterResult*/) {
                    echo str_pad($testWord, 18 - mb_strlen($testWord) + strlen($testWord), ' ', STR_PAD_LEFT);
                    echo ' | ';
                    echo $filterResult ? 'tak' : 'nie';
                    echo ' | ';
                    echo $equalResult ? 'tak' : 'nie';
                    echo ' | ';
                    echo str_pad((string)mb_strlen($testWord), 9, ' ', STR_PAD_BOTH);
                    echo ' | ';
                    echo str_pad((string)strlen($testWord), 6, ' ', STR_PAD_BOTH);
                    echo ' | ';
                    echo (int)($equalResult !== $filterResult);
                    echo PHP_EOL;
                }

                $allTests++;
                if ($filterResult && !$equalResult) {
                    $failPositive++;
                } elseif (!$filterResult && $equalResult) {
                    $failNegative++;
                } elseif ($filterResult && $equalResult) {
                    $positive++;
                } elseif (!$filterResult && !$equalResult) {
                    $negative++;
                } else {
                    $control++;
                }
            }

            fclose($handle);
        } else {
            echo "Failed to open `{$filename}`" . PHP_EOL;
        }
        echo 'Wyrazów w słowniku:      ' . str_pad((string)count($equalList), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Testów:                  ' . str_pad((string)$allTests, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Wszystkich pozytywnych:  ' . str_pad((string)($positive + $failPositive), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Fałszywie pozytywnych:   ' . str_pad((string)$failPositive, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Prawdziwych pozytywnych: ' . str_pad((string)$positive, 7, ' ', STR_PAD_LEFT) . ' <-' . PHP_EOL;
        echo 'Wszystkich negatywnych:  ' . str_pad((string)($negative + $failNegative), 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Fałszywie negatywnych:   ' . str_pad((string)$failNegative, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Prawdziwych negatywnych: ' . str_pad((string)$negative, 7, ' ', STR_PAD_LEFT) . ' <-' . PHP_EOL;
        echo 'Kontrolne zero:          ' . str_pad((string)$control, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo sprintf(
                'Skuteczność filtru:    %.2f%%',
                ($allTests - ($failPositive + $failNegative))/$allTests * 100
            ) . PHP_EOL;
    }
}