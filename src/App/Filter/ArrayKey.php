<?php

namespace App\Filter;

use App\Utils;
use Igoreus\BloomFilter\BloomFilter;
use Igoreus\BloomFilter\Persist\BitString;

class ArrayKey implements FilterInterface
{
    public static function optimizeOptions(array $options): array
    {
        return $options;
    }

    public function createFromFile(string $sourceFile, string $filterFile, array $options = []): void
    {
        echo 'Create array-key filter' . PHP_EOL;

        $handle = fopen($sourceFile, 'rb');
        if ($handle) {
            $i = 0;
            while (($line = fgets($handle)) !== false /*&& $i < 100*/) {
                $word = Utils::cleanWord($line);
                $equalList[$word] = 1;

                ++$i;
                if ($i % 100000 === 0) {
                    echo $i . ' ' .
                        number_format($i / 2998089 * 100, 2) . '% memory usage: ' .
                        Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;
                }
            }

            fclose($handle);

            file_put_contents($filterFile, serialize($equalList));
        } else {
            echo "Failed to open `{$sourceFile}`" . PHP_EOL;
        }
    }

    public function fileTest(string $testFile, string $filterFile, array $options = []): void
    {
        $equalList = unserialize(file_get_contents($filterFile), ['allowed_classes' => false]);

        $positive = 0;
        $negative = 0;

        $handle = fopen($testFile, 'rb');
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false) {
                $testWord = Utils::cleanWord($line);

                $filterResult = isset($equalList[$testWord]);;

                if ($filterResult) {
                    $positive++;
                } else {
                    $negative++;
                }
                $count++;
            }

            fclose($handle);
        } else {
            echo "Failed to open `{$testFile}`" . PHP_EOL;
        }
        echo 'Words in dictionary: ' . str_pad((string)$count, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Found words:         ' . str_pad((string)$positive, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
        echo 'Rejected words:      ' . str_pad((string)$negative, 7, ' ', STR_PAD_LEFT) . PHP_EOL;
    }
}