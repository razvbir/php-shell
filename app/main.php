<?php

declare(strict_types=1);

error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {include $class . '.php';});

while (true) {
    fwrite(STDOUT, '$ ');

    $input = fgets(STDIN);
    try {
        AbstractCommand::make($input)->execute();
    } catch (CommandNotFoundException $e) {
        fwrite(STDOUT, $e->getMessage() . PHP_EOL);
    }
}
