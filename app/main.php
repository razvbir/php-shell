<?php
error_reporting(E_ALL);

// Uncomment this block to pass the first stage
fwrite(STDOUT, "$ ");

// Wait for user input
$input = fgets(STDIN);
printf("%s: command not found\n", trim($input));
