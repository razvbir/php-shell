<?php

declare(strict_types=1);

readonly class EchoCommand extends AbstractCommand
{
    private string $content;

    public function __construct(
        protected string $command = '',
        protected array $args = [],
    ) {
        $this->content = implode(' ', $this->args);
    }

    public function execute(): void
    {
        fwrite(STDOUT, $this->content.PHP_EOL);
    }
}
