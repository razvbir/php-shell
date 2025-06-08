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

    $args = AbstractCommand::extract($input);
    $pipe = array_find_key($args, fn (string $a): bool => $a === '|');
    if ($pipe !== null) {
        $one = array_slice($args, 0, $pipe);
        $two = array_slice($args, $pipe + 1);

        $commandOne = trim(escapeshellarg($one[0])
                . ' '
                . implode(' ', array_map(fn (string $a): string => escapeshellarg($a), array_slice($one, 1))));

        $commandTwo = trim(escapeshellarg($two[0])
                . ' '
                . implode(' ', array_map(fn (string $a): string => escapeshellarg($a), array_slice($two, 1))));

        $pipesOne = [];
        $pipesTwo = [];
        $processOne = proc_open($commandOne, [STDIN, ['pipe', 'w'], ['pipe', 'w']], $pipesOne);
        $processTwo = proc_open($commandTwo, [$pipesOne[1], STDOUT, STDERR], $pipesTwo);

        while (proc_get_status($processOne)['running'] || proc_get_status($processTwo)['running']);

        proc_close($processOne);
        proc_close($processTwo);

        readline_add_history($input);
        readline_callback_handler_install(PROMPT, afterLine(...));
        return;
    }

    readline_add_history($input);

    try {
        AbstractCommand::make($args)->execute();
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
