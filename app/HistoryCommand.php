<?php

declare(strict_types=1);

readonly class HistoryCommand extends AbstractCommand
{
    public function execute(): void
    {
        // Maybe use getopt
        $append = isset($this->args[0]) && $this->args[0] === '-a';
        $filename = $this->args[1] ?? '/dev/null';
        if ($append === true) {
            static::appendHistoryFile($filename);
            return;
        }

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

        global $history;
        $localHistory = readline_list_history();
        if (isset($localHistory[0]) && isset($history[0])) {
            $localHistory[0] = $history[0];
        }

        $offset = (int) ($this->args[0] ?? 0);
        $prevCommands = array_slice($localHistory, -$offset, null, true);
        foreach ($prevCommands as $index => $prevCommand) {
            $line = sprintf('%5d  %s', $index + 1, trim($prevCommand));
            fwrite($this->out, $line . PHP_EOL);
        }
    }

    public static function appendHistoryFile(string $filename): void
    {
        $file = fopen($filename, 'r+');
        if (false === $file) {
            exit('Could not open file: ' . $filename . PHP_EOL);
        }

        $history = [];
        while (($line = fgets($file)) !== false) {
            $trimmed = trim($line);
            if (strlen($trimmed) > 0) {
                $history[] = $trimmed;
            }
        }
        array_push($history, ...array_diff_assoc(readline_list_history(), $history));
        readline_clear_history();

        if (false === rewind($file)) {
            exit('Could not rewind the file' . PHP_EOL);
        }
        array_walk($history, function (string $prompt) use($file):void {
            if (strlen(trim($prompt)) === 0) {
                return;
            }
            if (false === fwrite($file, $prompt . PHP_EOL)) {
                exit('Could not write to file' . PHP_EOL);
            }
            if (false === readline_add_history($prompt)) {
                exit('Failed to append history' . PHP_EOL);
            }
        });

        fclose($file);
    }
}
