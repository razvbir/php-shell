<?php

declare(strict_types=1);

readonly class TypeCommand extends AbstractCommand
{
    public function execute(): void
    {
        $given = $this->args[0];
        $message = "$given: not found";
        if ($this->isAShellBuiltIn($given)) {
            $message = "$given is a shell builtin";
        } elseif (($commandPath = self::tryToGetCommandPath($given)) !== null) {
            $message = "$given is $commandPath";
        }

        fwrite(STDOUT, $message.PHP_EOL);
    }

    private function isAShellBuiltIn(string $commandName): bool
    {
        return in_array($commandName, array_map(fn (Command $command): string => $command->value, Command::cases()));
    }

    public static function tryToGetCommandPath(string $commandName): ?string
    {
        $directories = getenv('PATH');
        foreach (explode(':', $directories) as $directory) {
            if ($directory === '' || !is_dir($directory)) continue;

            $directoryContent = scandir($directory);
            if ($directoryContent === false) continue;

            if (
                in_array($commandName, array_filter($directoryContent, fn ($d) => !in_array($d, ['.', '..']))) &&
                is_executable($directory.DIRECTORY_SEPARATOR.$commandName)
            ) {
                return $directory.DIRECTORY_SEPARATOR.$commandName;
            }
        }

        return null;
    }
}
