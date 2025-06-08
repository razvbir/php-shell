<?php

declare(strict_types=1);

error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {include $class . '.php';});

const PROMPT = '$ ';

function afterLine(?string $input): void
{
    if (null === $input) {
        readline_callback_handler_remove();
        exit(0);
    }

    try {
        AbstractCommand::make($input)->execute();
    } catch (CommandNotFoundException $e) {
        fwrite(STDOUT, $e->getMessage() . PHP_EOL);
    }

    readline_callback_handler_install(PROMPT, afterLine(...));
}

function completion(string $command, int $index, int $length): array
{
    $completions = array_filter(CommandType::builtIns(), fn (string $b): bool => str_starts_with($b, $command));
    array_push($completions, ...TypeCommand::getPartialPathMatch($command));
    if ($completions === []) {
        echo "\x07";
    }

    return $completions;
}

readline_callback_handler_install(PROMPT, afterLine(...));
readline_completion_function(completion(...));

$failed = 0;
$seconds = 0;
$microseconds = 200_000;
while (true) {
    $read = [STDIN];
    $write = $except = null;
    if (false === ($stream = stream_select($read, $write, $except, $seconds, $microseconds))) {
        fwrite(STDOUT, 'Could not read from STDIN' . PHP_EOL);
        usleep($microseconds);

        if (++$failed === 10) {
            fwrite(STDOUT, 'Failed too many times' . PHP_EOL);
            readline_callback_handler_remove();
            exit(1);
        }
    }
    if ($stream === 1 && in_array(STDIN, $read, true)) {
        readline_callback_read_char();
    }
}
