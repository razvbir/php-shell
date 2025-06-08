<?php

declare(strict_types=1);

error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {include $class . '.php';});

const PROMPT = '$ ';

/** @param array<string> */
function escapeCommand(array $args): string
{
    return trim(escapeshellarg($args[0])
        . ' '
        . implode(' ', array_map(fn (string $a): string => escapeshellarg($a), array_slice($args, 1))));
}

function afterLine(?string $input): void
{
    if (null === $input) {
        readline_callback_handler_remove();
        exit(0);
    }
    if ('' === $input) {
        readline_callback_handler_install(PROMPT, afterLine(...));
        return;
    }

    $args = AbstractCommand::extract($input);
    $pipe = array_find_key($args, fn (string $a): bool => $a === '|');
    if ($pipe !== null) {
        $remainder = $args;
        $commands = [array_slice($remainder, 0, $pipe)];
        while (($remainder = array_slice($remainder, $pipe + 1)) !== []) {
            $pipe = array_find_key($remainder, fn (string $a): bool => $a === '|');
            if ($pipe === null) {
                $commands[] = $remainder;
                break;
            }
            $commands[] = array_slice($remainder, 0, $pipe);
        }

        $processes = [];
        $pipes = [];

        $first = array_shift($commands);
        $firstPipes = [];
        $prevPipes = &$firstPipes;
        $processes[] = proc_open(escapeCommand($first), [STDIN, ['pipe', 'w'], STDERR], $firstPipes);
        foreach (array_slice($commands, 0, -1) as $command) {
            $commandPipes = [];
            $processes[] = proc_open(escapeCommand($command), [$prevPipes[1], ['pipe', 'w'], STDERR], $commandPipes);
            array_push($pipes, ...$prevPipes);
            $prevPipes = $commandPipes;
        }
        $commandPipes = [];
        $processes[] = proc_open(
            escapeCommand($commands[count($commands) - 1]),
            [$prevPipes[1], STDOUT, STDERR],
            $commandPipes
        );
        array_push($pipes, ...$commandPipes);

        do {
            $running = array_reduce(
                $processes,
                fn (bool $carry, mixed $proc) => $carry || proc_get_status($proc)['running'],
                false
            );
        }while ($running === true);

        array_walk($processes, fn (mixed $process): int => proc_close($process));
        array_walk($pipes, fn (mixed $pipe): bool => is_resource($pipe) && fclose($pipe));

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

$historyFile = getenv('HISTFILE');
if (false !== $historyFile) {
    readline_clear_history();
    $file = fopen($historyFile, 'r');
    if (false === $file) {
        exit('Could not open history file: ' . $historyFile . PHP_EOL);
    }

    while (($line = fgets($file)) !== false) {
        $trimmed = trim($line);
        if (strlen($trimmed) > 0) {
            if (false === readline_add_history($trimmed)) {
                exit('Could not add history');
            };
        }
    }

    fclose($file);
}
$history = readline_list_history();

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
