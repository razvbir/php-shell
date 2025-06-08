<?php

declare(strict_types=1);

readonly class ExitCommand extends AbstractCommand
{
    public function execute(): void
    {
        global $historyFile;
        $statusCode = (int) ($this->args[0] ?? 0);
        readline_callback_handler_remove();
        if  ($historyFile !== false) {
            readline_write_history($historyFile);
        }
        exit($statusCode);
    }
}
