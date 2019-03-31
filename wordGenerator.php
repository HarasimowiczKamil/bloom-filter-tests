<?php

function generateWord(int $strlen): string
{
    $word = '';
    $alphabet = 'aąbcćdeęfghijklłmnńoópqrsśtuwxyzżź';
    $size = mb_strlen($alphabet);
    for ($i = 0; $i < $strlen; ++$i) {
        $word .= mb_substr($alphabet, rand (0, $size), 1);
    }
    return $word;
}

$time = microtime(true);

$words = '';
for ($i = 0; $i < 1000000; ++$i) {
    $words .= generateWord(rand (2, 8)) . PHP_EOL;
    if ($i % 100000 === 0) {
        file_put_contents('random_test.txt', $words, FILE_APPEND);
        $words = '';
        echo 'Generate ' . $i . ' words in ' . number_format(microtime(true) - $time, 2) . 'sek' . PHP_EOL;
    }
}

if ($words) {
    file_put_contents('random_test.txt', $words, FILE_APPEND);
    $words = '';
    echo 'Generate ' . $i . ' words in ' . number_format(microtime(true) - $time, 2) . 'sek' . PHP_EOL;
}
