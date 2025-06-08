<?php

declare(strict_types=1);

readonly class ExitCommand extends AbstractCommand
{
    public function execute(): void
    {
        $statusCode = (int) ($this->args[0] ?? 0);
        readline_callback_handler_remove();
        exit($statusCode);
    }
}
