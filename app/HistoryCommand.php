<?php

declare(strict_types=1);

readonly class HistoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        // Maybe use getopt
        $write = isset($this->args[0]) && $this->args[0] === '-w';
        $filename = $this->args[1] ?? '/dev/null';
        if ($write === true) {
            readline_write_history($filename);
            return;
        }

        $read = isset($this->args[0]) && $this->args[0] === '-r';
        $filename = $this->args[1] ?? '/dev/null';
        if ($read === true) {
            readline_read_history($filename);
            return;
        }

        $offset = (int) ($this->args[0] ?? 0);
        $prevCommands = array_slice(readline_list_history(), -$offset, null, true);
        foreach ($prevCommands as $index => $prevCommand) {
            $line = sprintf('%5d  %s', $index + 1, $prevCommand);
            fwrite($this->out, $line . PHP_EOL);
        }
    }
}
