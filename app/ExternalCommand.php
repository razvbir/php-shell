<?php

declare(strict_types=1);

readonly class ExternalCommand extends AbstractCommand
{
    public function execute(): void
    {
        $output = [];
        $result_code = 1<<8;
        $command = escapeshellarg(basename($this->command))
                . ' '
                . implode(' ', array_map(fn (string $a): string => escapeshellarg($a), $this->args));
        $success = exec($command, $output, $result_code);
        if ($success === false) {
            exit(1);
        }
        fwrite($this->out, implode(PHP_EOL, $output) . PHP_EOL);
    }
}
