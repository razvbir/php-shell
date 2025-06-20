<?php

declare(strict_types=1);

readonly class TypeCommand extends AbstractCommand
{
    public function execute(): void
    {
        $given = $this->args[0] ?? '';
        if ($given === '') {
            return;
        }

        $message = "$given: not found";
        if ($this->isAShellBuiltIn($given)) {
            $message = "$given is a shell builtin";
        } elseif (($commandPath = self::tryToGetCommandPath($given)) !== null) {
            $message = "$given is $commandPath";
        }

        fwrite($this->out, $message . PHP_EOL);
    }

    private function isAShellBuiltIn(string $commandName): bool
    {
        return in_array(
            $commandName,
            array_map(fn (CommandType $command): string => $command->value, CommandType::cases()),
            true
        );
    }

    public static function tryToGetCommandPath(string $commandName): ?string
    {
        foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $directory) {
            if ($directory === '' || !is_dir($directory)) continue;

            $directoryContent = scandir($directory);
            if ($directoryContent === false) continue;

            $path = $directory . DIRECTORY_SEPARATOR . $commandName;
            if (
                in_array(
                    $commandName,
                    array_filter($directoryContent, fn (string $d): bool => !in_array($d, ['.', '..'], true))
                ) &&
                is_executable($path)
            ) {
                return $path;
            }
        }

        return null;
    }

    public static function getPartialPathMatch(string $partialCommand): array
    {
        $result = [];
        foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $directory) {
            if ($directory === '' || !is_dir($directory)) continue;

            $directoryContent = scandir($directory);
            if ($directoryContent === false) continue;

            $prefix = $directory . DIRECTORY_SEPARATOR;
            $executables = array_filter(
                $directoryContent,
                function (string $file) use ($prefix, $partialCommand): bool {
                    return !in_array($file, ['.', '..'], true) &&
                        is_executable($prefix . $file) &&
                        str_starts_with($file, $partialCommand);
                }
            );
            if ($executables !== []) {
                array_push($result, ...$executables);
            }
        }

        return $result;
    }
}
