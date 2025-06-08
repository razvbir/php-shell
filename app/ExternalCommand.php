<?php

declare(strict_types=1);

readonly class ExternalCommand extends AbstractCommand
{
    public function execute(): void
    {
        $pipes = [];
        $descriptorSpec = [$this->in, $this->out, $this->err];
        $command = escapeshellarg(basename($this->command))
                . ' '
                . implode(' ', array_map(fn (string $a): string => escapeshellarg($a), $this->args));

        $process = proc_open($command, $descriptorSpec, $pipes);
        while (proc_get_status($process)['running']);
        proc_close($process);
    }
}
