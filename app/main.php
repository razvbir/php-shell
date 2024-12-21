<?php
error_reporting(E_ALL);

while (true) {
    fwrite(STDOUT, "$ ");

    $input = fgets(STDIN);
    $command = trim($input);
    fwrite(STDOUT, "$command: command not found" . PHP_EOL);
}
