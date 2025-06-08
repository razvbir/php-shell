<?php

declare(strict_types=1);

readonly abstract class AbstractCommand
{
    private const string DEFAULT_SEPARATOR = ' ';

    public function __construct(
        protected string $command = '',
        /** @var array<string> */
        protected array $args = [],
        protected mixed $out = STDOUT,
    ) {
    }

    abstract public function execute(): void;

    public static function make(string $command): static
    {
        $args = self::extract($command);

        $commandName = array_shift($args);

        $commandPath = TypeCommand::tryToGetCommandPath($commandName);
        $commandType = CommandType::tryFrom($commandName);
        if ($commandPath !== null && $commandType === null) {
            $commandType = CommandType::external;
        }

        $redirectStdout = array_find_key($args, fn (string $a): bool => $a === '>' || $a === '1>');
        $stdoutFilename = $args[(int) $redirectStdout + 1] ?? null;

        $out = STDOUT;
        if ($redirectStdout !== null && $stdoutFilename !== null) {
            $out = fopen($stdoutFilename, 'w');
        }
        if ($out !== STDOUT && !is_resource($out)) {
            throw FileNotFoundException::make();
        }

        $commandArgs = array_slice($args, 0, $redirectStdout !== null ? (int) $redirectStdout : null);

        return match ($commandType) {
            CommandType::exit => new ExitCommand(args: $commandArgs, out: $out),
            CommandType::echo => new EchoCommand(args: $commandArgs, out: $out),
            CommandType::type => new TypeCommand(args: $commandArgs, out: $out),
            CommandType::external => new ExternalCommand($commandPath, $commandArgs, $out),
            CommandType::pwd => new PrintWorkingDirectoryCommand(out: $out),
            CommandType::cd => new ChangeDirectoryCommand(args: $commandArgs, out: $out),
            default => throw CommandNotFoundException::make($commandName),
        };
    }

    /** @return array<string> */
    private static function extract(string $command): array
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
