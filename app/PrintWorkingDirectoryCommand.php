<?php

declare(strict_types=1);

readonly class PrintWorkingDirectoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        fwrite($this->out, getcwd() . PHP_EOL);
    }
}
