<?php

declare(strict_types=1);

readonly class ExternalCommand extends AbstractCommand
{
    public function execute(): void
    {
        $output = [];
        $result_code = 1<<8;
        $success = exec($this->command.' '.implode(' ', $this->args), $output, $result_code);
        if ($success === false) {
            exit(1);
        }
        fwrite(STDOUT, implode(PHP_EOL, $output).PHP_EOL);
    }
}
