<?php

declare(strict_types=1);

readonly abstract class AbstractCommand
{
    private const string DEFAULT_SEPARATOR = ' ';

    public function __construct(
        protected string $command = '',
        /** @var array<string> */
        protected array $args = [],
    ) {
    }

    abstract public function execute(): void;

    public static function make(string $command): static
    {
        $args = self::extract($command);
        $commandName = $args[0];
        $commandPath = TypeCommand::tryToGetCommandPath($commandName);
        if ($commandPath !== null) {
            $command = CommandType::external;
        } else {
            $command = CommandType::tryFrom($commandName);
        }

        $commandArgs = array_slice($args, 1);

        return match ($command) {
            CommandType::exit => new ExitCommand(args: $commandArgs),
            CommandType::echo => new EchoCommand(args: $commandArgs),
            CommandType::type => new TypeCommand(args: $commandArgs),
            CommandType::external => new ExternalCommand($commandPath, $commandArgs),
            CommandType::pwd => new PrintWorkingDirectoryCommand(),
            CommandType::cd => new ChangeDirectoryCommand(args: $commandArgs),
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
            if ($char === '\'' && !$inDouble) {
                $inSingle = !$inSingle;
                continue;
            }

            if ($char === '"' && !$inSingle) {
                $inDouble = !$inDouble;
                continue;
            }

            if ($char === self::DEFAULT_SEPARATOR && !$inSingle && !$inDouble && !$wasEscaped) {
                $args[] = $transformed;
                $transformed = '';
                continue;
            }

            if ($char === '\\' && !$inSingle && !$inDouble && !$wasEscaped) {
                $wasEscaped = true;
                continue;
            }
            $wasEscaped = false;

            if (ctype_space($char) && !$inSingle && !$inDouble && !$wasEscaped) {
                continue;
            }

            $transformed .= $char;
        }

        $args[] = $transformed;

        return $args;
    }
}
