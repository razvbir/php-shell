<?php

declare(strict_types=1);

readonly class PrintWorkingDirectoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        fwrite(STDOUT, getcwd() . PHP_EOL);
    }
}
