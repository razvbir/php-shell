<?php
declare(strict_types=1);
error_reporting(E_ALL);

class CommandNotFoundException extends Exception {
    const INVALID_COMMAND_MESSAGE = ': command not found';
    const INVALID_COMMAND_CODE = 404;

    public static function make(string $commandName = ''): static
    {
        return new CommandNotFoundException(
            $commandName.self::INVALID_COMMAND_MESSAGE,
            self::INVALID_COMMAND_CODE,
        );
    }
};

readonly abstract class Command
{
    public function __construct(
        protected string $commandName,
        protected array $args = [],
    ) {
    }

    abstract public function execute(): void;

    public static function make(string $command): static
    {
        $args = explode(" ", trim($command));
        $commandName = $args[0];
        $commandArgs = array_slice($args, 1);

        return match ($commandName) {
            'exit' => new ExitCommand($commandName, $commandArgs),
            default => throw CommandNotFoundException::make($commandName),
        };
    }
}

readonly class ExitCommand extends Command
{
    private int $statusCode;

    public function __construct(
        protected string $commandName,
        protected array $args = [],
    ) {
        $this->statusCode = (int) ($this->args[0] ?? 0);
    }

    public function execute(): void
    {
        exit($this->statusCode);
    }
}

while (true) {
    fwrite(STDOUT, "$ ");

    $input = fgets(STDIN);
    try {
        $command = Command::make($input);
        $command->execute();
    } catch (CommandNotFoundException $e) {
        fwrite(STDOUT, $e->getMessage() . PHP_EOL);
    }
}
