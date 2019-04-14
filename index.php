<?php

use \Igoreus\BloomFilter\BloomFilter;
use Igoreus\BloomFilter\Persist\BitString;
use App\Utils;

require_once 'vendor/autoload.php';

$lastTime = $time = microtime(true);
$falsePositiveProbability = $argv[1] ?? 0.001;
$approximateSize = $argv[2] ?? 3000000;
$hashFunctions = ['Crc32b', 'Fnv', /*'Jenkins', 'Murmur'*/];

$wordsFile = 'slowa.txt';
$equalList = [];

$equalFileName = 'equal_table.sphp';
$bloomFileName = 'bloom_filter_' . $falsePositiveProbability . '.sphp';

echo sprintf(
    'Test filtru blooma, przwidywana liczba elementów zbioru %d' . PHP_EOL .
    'z prawdopodobieństwem fałszywie pozytywnych odpowiedzi %.3f' . PHP_EOL,
    $approximateSize,
    $falsePositiveProbability
);

$isEqualListExists = file_exists($equalFileName);
$isBloomExists = file_exists($bloomFileName);

if ($isBloomExists) {
    $bloomTime = microtime(true);
    echo 'Load bloom filter...' . PHP_EOL;
    $filterPersister = BitString::createFromString(file_get_contents($bloomFileName));
    $filter = BloomFilter::createFromApproximateSize($filterPersister, 3000000, $falsePositiveProbability, $hashFunctions);
    echo 'Bloom loaded from file in ' . number_format(microtime(true) - $bloomTime, 2) . 'sek' . PHP_EOL;
    echo 'Zużycie pamięci: ' . Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;
}

if ($isEqualListExists) {
    $equalTime = microtime(true);
    echo 'Load equal array...' . PHP_EOL;
    $equalList = unserialize(file_get_contents($equalFileName), ['allowed_classes' => false]);
//    $equalList = [];
    echo 'Equal array loaded from file in ' . number_format(microtime(true) - $equalTime, 2) . 'sek' . PHP_EOL;
    echo 'Zużycie pamięci: ' . Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;
}

if (!$isBloomExists) {
    $filterPersister = new BitString();
    $filter = BloomFilter::createFromApproximateSize($filterPersister, 3000000, $falsePositiveProbability, $hashFunctions);
}

if (!$isBloomExists || !$isEqualListExists) {
    $handle = fopen($wordsFile, 'r');
    if ($handle) {
        $i = 0;
        while (($line = fgets($handle)) !== false /*&& $i < 100*/) {
            $word = Utils::cleanWord($line);
            if (!$isEqualListExists) {
                $equalList[$word] = 1;
            }

            if (!$isBloomExists) {
                $filter->add($word);
            }

            ++$i;
            if ($i % 100000 === 0) {
                echo $i . ' ' .
                    number_format($i / 2998089 * 100, 2) . '% in ' .
                    number_format(microtime(true) - $lastTime, 2) .
                    'sek (' . number_format(microtime(true) - $time, 2) . 'sek) ' .
                    Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;
                $lastTime = microtime(true);
            }
        }

        fclose($handle);
    } else {
        echo "Failed to open `{$wordsFile}`" . PHP_EOL;
    }

    echo PHP_EOL;
    echo 'Filtr nakarmiony ' . $i . ' wyrazami w ' . number_format(microtime(true) - $time, 2) . 'sek' . PHP_EOL;
    echo PHP_EOL;
}

if (!$isEqualListExists) {
    file_put_contents($equalFileName, serialize($equalList));

    echo sprintf('Zapisano tablicę wszystkich kluczy w pliku `%s` rozmiar pliku: %s' . PHP_EOL,
        $equalFileName,
        Utils::formatBytes(filesize($equalFileName), 2)
    );
}
if (!$isBloomExists) {
    file_put_contents($bloomFileName, $filterPersister->toString());
    echo sprintf(
        'Zapisano filtr blooma w pliku `%s` rozmiar pliku: %s' . PHP_EOL,
        $bloomFileName,
        Utils::formatBytes(filesize($bloomFileName), 2)
    );
}

//echo PHP_EOL . PHP_EOL . 'Rozpoczynamy testowanie filtra:' . PHP_EOL . PHP_EOL;
//
//Utils::testWordsFromFile('test_words.txt', $filter, $equalList, true);

echo PHP_EOL . PHP_EOL . 'Rozpoczynamy testowanie losowo wygenerowanych wyrazów:' . PHP_EOL . PHP_EOL;

$time = microtime(true);

Utils::testWordsFromFile('random_test.txt', $filter, $equalList);

echo sprintf('Test zakończony w %.3f sek', microtime(true) - $time) . PHP_EOL;
echo 'Zużycie pamięci: ' . Utils::formatBytes(memory_get_usage(), 2) . PHP_EOL;


