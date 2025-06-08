<?php

declare(strict_types=1);

readonly abstract class AbstractCommand
{
    private const string DEFAULT_SEPARATOR = ' ';

    public function __construct(
        protected string $command = '',
        /** @var array<string> */
        protected array $args = [],
        protected mixed $in = STDIN,
        protected mixed $out = STDOUT,
        protected mixed $err = STDERR,
    ) {
    }

    abstract public function execute(): void;

    /** @param array<string> $args */
    public static function make(array $args): static
    {
        $commandName = array_shift($args);

        $commandPath = TypeCommand::tryToGetCommandPath($commandName);
        $commandType = CommandType::tryFrom($commandName);
        if ($commandPath !== null && $commandType === null) {
            $commandType = CommandType::external;
        }

        $redirectStdout = array_find_key($args, fn (string $a): bool => in_array($a, ['>', '1>', '>>', '1>>'], true));
        $stdoutFilename = $args[(int) $redirectStdout + 1] ?? null;

        $out = STDOUT;
        if ($redirectStdout !== null && $stdoutFilename !== null) {
            $out = fopen($stdoutFilename, str_contains($args[$redirectStdout], '>>') === true ? 'a' : 'w');
        }
        if ($out !== STDOUT && !is_resource($out)) {
            throw FileNotFoundException::make();
        }

        $redirectStderr = array_find_key($args, fn (string $a): bool => $a === '2>' || $a === '2>>');
        $stderrFilename = $args[(int) $redirectStderr + 1] ?? null;

        $err = STDERR;
        if ($redirectStderr !== null && $stderrFilename !== null) {
            $err = fopen($stderrFilename, str_contains($args[$redirectStderr], '>>') === true ? 'a' : 'w');
        }
        if ($err !== STDERR && !is_resource($err)) {
            throw FileNotFoundException::make();
        }

        $length = null;
        if ($redirectStdout !== null) {
            $length = (int) $redirectStdout;
        } elseif ($redirectStderr !== null) {
            $length = (int) $redirectStderr;
        }
        $commandArgs = array_slice($args, 0, $length);

        return match ($commandType) {
            CommandType::exit => new ExitCommand(args: $commandArgs, out: $out, err: $err),
            CommandType::echo => new EchoCommand(args: $commandArgs, out: $out, err: $err),
            CommandType::type => new TypeCommand(args: $commandArgs, out: $out, err: $err),
            CommandType::external => new ExternalCommand($commandPath, $commandArgs, STDIN, $out, $err),
            CommandType::pwd => new PrintWorkingDirectoryCommand(out: $out, err: $err),
            CommandType::cd => new ChangeDirectoryCommand(args: $commandArgs, out: $out, err: $err),
            CommandType::history => new HistoryCommand(args: $commandArgs, out: $out, err: $err),
            default => throw CommandNotFoundException::make($commandName),
        };
    }

    /** @return array<string> */
    public static function extract(string $command): array
    {
        $args = [];
        $transformed = '';

        $inSingle = false;
        $inDouble = false;
        $wasEscaped = false;
        foreach (str_split($command) as $char) {
            if ($char === '\'' && !$inDouble && !$wasEscaped) {
                $inSingle = !$inSingle;
                continue;
            }

            if ($char === '"' && !$inSingle && !$wasEscaped) {
                $inDouble = !$inDouble;
                continue;
            }

            if ($char === self::DEFAULT_SEPARATOR && !$inSingle && !$inDouble && !$wasEscaped && strlen($transformed)) {
                $args[] = $transformed;
                $transformed = '';
                continue;
            }

            if ($char === '\\' && !$inSingle && !$wasEscaped) {
                $wasEscaped = true;
                continue;
            }

            if ($wasEscaped && !in_array($char, ['\\','"', ' '], true) && $inDouble) {
                $wasEscaped = false;
                $transformed .= '\\' . $char;
                continue;
            }

            if ($wasEscaped) {
                $wasEscaped = false;
                $transformed .= $char;
                continue;
            }

            if (ctype_space($char) && !$inSingle && !$inDouble && !$wasEscaped) {
                continue;
            }

            $transformed .= $char;
        }

        $args[] = $transformed;

        return $args;
    }
}
