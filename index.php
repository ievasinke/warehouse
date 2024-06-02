<?php

echo "Welcome to the WareHouse app!\n";
$accessCode = (string)readline("Enter your access code: ");

if (strlen($accessCode) === 6) {
    echo "Welcome \$username!\n";
} else {
    exit("Invalid access code. Please try again.\n");
}




