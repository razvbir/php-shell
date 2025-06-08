<?php

declare(strict_types=1);

readonly class HistoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        foreach (readline_list_history() as $index => $prevCommand) {
            $line = sprintf('%5d  %s', $index + 1, $prevCommand);
            fwrite($this->out, $line . PHP_EOL);
        }
    }
}
