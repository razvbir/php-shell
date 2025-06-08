<?php

declare(strict_types=1);

readonly class HistoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        $read = isset($this->args[0]) && $this->args[0] === '-r';
        $filename = $this->args[1] ?? '/dev/null';
        if ($read === true) {
            $this->appendHistoryFile($filename);
            return;
        }

        $offset = (int) ($this->args[0] ?? 0);
        $prevCommands = array_slice(readline_list_history(), -$offset, null, true);
        foreach ($prevCommands as $index => $prevCommand) {
            $line = sprintf('%5d  %s', $index + 1, $prevCommand);
            fwrite($this->out, $line . PHP_EOL);
        }
    }

    private function appendHistoryFile(string $filename): void
    {
        $file = fopen($filename, 'r');
        if (false === $file) {
            exit('Could not open file: ' . $filename . PHP_EOL);
        }

        while (($line = fgets($file)) !== false) {
            $trimmed = trim($line);
            if (strlen($trimmed) > 0) {
                readline_add_history($trimmed);
            }
        }

        fclose($file);
    }
}
