<?php

declare(strict_types=1);

readonly class ChangeDirectoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        $directory = $this->args[0] ?? '~';
        if (str_starts_with($directory, '~')) {
            $directory = str_replace('~', getenv('HOME'), $directory);
        }

        if (!is_dir($directory)) {
            fwrite($this->err, 'cd: '.$directory.': No such file or directory' . PHP_EOL);
            return;
        }

        chdir($directory);
    }
}
