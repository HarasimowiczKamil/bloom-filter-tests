<?php

declare(strict_types=1);

namespace App\Filter;

use App\Utils;
use Igoreus\BloomFilter\BloomFilter;
use Igoreus\BloomFilter\Persist\BitString;

class Bloom implements FilterInterface
{
    const OPTION_PRECISION = 0;
    const OPTION_APPROXIMATE_SIZE = 1;

    const DEFAULT_PRECISION = 0.01;
    const DEFAULT_APPROXIMATE_SIZE = 1000000000;

    public static function optimizeOptions(array $options): array
    {
        return [
            'precision' => $options[self::OPTION_PRECISION] ?? self::DEFAULT_PRECISION,
            'approximateSize' => $options[self::OPTION_APPROXIMATE_SIZE] ?? self::DEFAULT_APPROXIMATE_SIZE,
        ];
    }

    public function createFromFile(string $sourceFile, string $filterFile, array $options = []): void
    {
        $precision = $options['precision'];
        $approximateSize = $options['approximateSize'];
        echo sprintf('Create bloom filter with precision %f and proximate size %d', $precision, $approximateSize) . PHP_EOL;

        $filterPersister = new BitString();
        $filter = BloomFilter::createFromApproximateSize($filterPersister, 3000000, $precision);

        $handle = fopen($sourceFile, 'rb');
        if ($handle) {
            $i = 0;
            while (($line = fgets($handle)) !== false /*&& $i < 100*/) {
                $word = Utils::cleanWord($line);
                $filter->add($word);

                ++$i;
                if ($i % 100000 === 0) {
                    echo $i . ' ' .
                        number_format($i / 2998089 * 100, 2) . '% memory usage: ' .
                        Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;
                }
            }

            fclose($handle);

            file_put_contents($filterFile, $filterPersister->toString());
        } else {
            echo "Failed to open `{$sourceFile}`" . PHP_EOL;
        }
    }

    public function fileTest(string $testFile, string $filterFile, array $options = []): void
    {
        $precision = $options['precision'];
        $approximateSize = $options['approximateSize'];
        $filterString = BitString::createFromString(file_get_contents($filterFile));
        $filter = BloomFilter::createFromApproximateSize($filterString, $approximateSize, $precision);

        $positive = 0;
        $negative = 0;

        $handle = fopen($testFile, 'rb');
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false) {
                $testWord = Utils::cleanWord($line);

                $filterResult = $filter->has($testWord);

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