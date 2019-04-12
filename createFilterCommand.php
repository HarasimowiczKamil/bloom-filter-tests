<?php

declare(strict_types=1);

use App\Filter\ArrayKey;
use App\Filter\Bloom;
use App\Filter\FileSearch;
use App\Filter\FilterInterface;
use App\Utils;

require_once 'vendor/autoload.php';

const FILTER = 0;

$command = new Commando\Command();
$command
    ->option()
    ->require()
    ->describedAs('Filter type')
    ->must(static function($filterName) {
        $filters = ['bloom', 'array-keys', 'file-search'];
        return in_array($filterName, $filters, true);
    })
    ->map(static function($title) {
        $filters = [
            'bloom' => Bloom::class,
            'array-keys' => ArrayKey::class,
            'file-search' => FileSearch::class,
        ];
        return $filters[$title];
    })
;

$command
    ->option('option')
    ->describedAs('Filter specific options')
    ->default([])
    ->map(static function($value) use ($command) {
        echo 'run with value' . $value . PHP_EOL;
        return $command[FILTER]::optimizeOptions(json_decode($value, true));
    })
;

$command
    ->option('source')
    ->describedAs('Source file')
    ->file()
    ->default('slowa.txt')
;

$command
    ->option('destination')
    ->describedAs('Destination file')
    ->default(Utils::createFilterFileName($command[FILTER], $command['option']))
;

$filterClassName = $command[FILTER];
/** @var FilterInterface $filter */
$filter = new $filterClassName();

echo $command[FILTER] . PHP_EOL;
echo $command['source'] . PHP_EOL;
echo $command['destination'] . PHP_EOL;
echo var_export($command['option'], true) . PHP_EOL;

$startTime = microtime(true);

$filter->createFromFile($command['source'], $command['destination'], $command['option']);

echo sprintf('Filter created in %.2f sec.', microtime(true) - $startTime) . PHP_EOL;
echo sprintf(
    'Filter saved in file `%s` size: %s' . PHP_EOL,
    $command['destination'],
    Utils::formatBytes(filesize($command['destination']), 2)
);
