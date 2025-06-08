<?php

declare(strict_types=1);

readonly class ExitCommand extends AbstractCommand
{
    public function execute(): void
    {
        global $historyFile;
        $statusCode = (int) ($this->args[0] ?? 0);
        readline_callback_handler_remove();
        if  ($historyFile !== false && !file_exists($historyFile)) {
            readline_write_history($historyFile);
            exit($statusCode);
        }
        if ($historyFile !== false && file_exists($historyFile)) {
            HistoryCommand::appendHistoryFile($historyFile);
            exit($statusCode);
        }
        exit($statusCode);
    }
}
