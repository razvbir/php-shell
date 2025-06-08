<?php

declare(strict_types=1);

readonly class EchoCommand extends AbstractCommand
{
    public function execute(): void
    {
        $content = implode(' ', $this->args);
        fwrite($this->out, $content . PHP_EOL);
    }
}
