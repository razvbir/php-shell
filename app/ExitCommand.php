<?php

declare(strict_types=1);

readonly class ExitCommand extends AbstractCommand
{
    private int $statusCode;

    public function __construct(
        protected string $command = '',
        protected array $args = [],
    ) {
        $this->statusCode = (int) ($this->args[0] ?? 0);
    }

    public function execute(): void
    {
        exit($this->statusCode);
    }
}
