#!/bin/bash
read -n 1 -s -r -p "Press any key to continue...";echo

php createFilterCommand.php bloom --option [0.001,3000000] --source slowa.txt --destination bloom_0.001_3000000.filter

read -n 1 -s -r -p "Press any key to continue...";echo

php createFilterCommand.php array-keys --source slowa.txt --destination arraykey_.filter

read -n 1 -s -r -p "Press any key to continue...";echo

php testCommand.php bloom --option [0.001,3000000] --source bloom_0.001_3000000.filter --test random_test.txt

read -n 1 -s -r -p "Press any key to continue...";echo

php testCommand.php array-keys --source arraykey_.filter --test random_test.txt

read -n 1 -s -r -p "Press any key to continue...";echo

echo "End"