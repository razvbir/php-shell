<?php

declare(strict_types=1);

readonly class HistoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        $offset = (int) ($this->args[0] ?? 0);
        $prevCommands = array_slice(readline_list_history(), -$offset, null, true);
        foreach ($prevCommands as $index => $prevCommand) {
            $line = sprintf('%5d  %s', $index + 1, $prevCommand);
            fwrite($this->out, $line . PHP_EOL);
        }
    }
}
