<?php
declare(strict_types=1);
error_reporting(E_ALL);

enum Command: string
{
    case exit = 'exit';
    case echo = 'echo';
    case type = 'type';
}

final class CommandNotFoundException extends Exception {
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

readonly abstract class AbstractCommand
{
    public function __construct(
        protected Command $commandName,
        protected array $args = [],
    ) {
    }

    abstract public function execute(): void;

    public static function make(string $command): static
    {
        $args = explode(" ", trim($command));
        $commandName = $args[0];
        $command = Command::tryFrom($commandName);
        $commandArgs = array_slice($args, 1);

        return match ($command) {
            Command::exit => new ExitCommand($command, $commandArgs),
            Command::echo => new EchoCommand($command, $commandArgs),
            Command::type => new TypeCommand($command, $commandArgs),
            default => throw CommandNotFoundException::make($commandName),
        };
    }
}

readonly class ExitCommand extends AbstractCommand
{
    private int $statusCode;

    public function __construct(
        protected Command $commandName,
        protected array $args = [],
    ) {
        $this->statusCode = (int) ($this->args[0] ?? 0);
    }

    public function execute(): void
    {
        exit($this->statusCode);
    }
}

readonly class EchoCommand extends AbstractCommand
{
    private string $content;

    public function __construct(
        protected Command $commandName,
        protected array $args = [],
    ) {
        $this->content = implode(' ', $this->args);
    }

    public function execute(): void
    {
        fwrite(STDOUT, $this->content.PHP_EOL);
    }
}

readonly class TypeCommand extends AbstractCommand
{
    public function execute(): void
    {
        $given = $this->args[0];
        $message = "$given: not found";
        if ($this->isAShellBuiltIn($given)) {
            $message = "$given is a shell builtin";
        } elseif (($commandPath = $this->tryToGetCommandPath($given)) !== null) {
            $message = "$given is $commandPath".DIRECTORY_SEPARATOR.$given;
        }

        fwrite(STDOUT, $message.PHP_EOL);
    }

    private function isAShellBuiltIn(string $commandName): bool
    {
        return in_array($commandName, array_map(fn (Command $command): string => $command->value, Command::cases()));
    }

    private function tryToGetCommandPath(string $commandName): ?string
    {
        $directories = getenv('PATH');
        foreach (explode(':', $directories) as $directory) {
            if ($directory === '' || !is_dir($directory)) continue;

            $directoryContent = scandir($directory);
            if ($directoryContent === false) continue;

            if (in_array($commandName, array_filter($directoryContent, fn ($d) => !in_array($d, ['.', '..'])))) {
                return $directory;
            }
        }

        return null;
    }
}

while (true) {
    fwrite(STDOUT, "$ ");

    $input = fgets(STDIN);
    try {
        $command = AbstractCommand::make($input);
        $command->execute();
    } catch (CommandNotFoundException $e) {
        fwrite(STDOUT, $e->getMessage() . PHP_EOL);
    }
}
