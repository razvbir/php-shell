<?php

declare(strict_types=1);

final class CommandNotFoundException extends Exception {
    private const MESSAGE = ': command not found';
    private const CODE = 404;

    public static function make(string $commandName = ''): self
    {
        return new self($commandName . self::MESSAGE, self::CODE);
    }
}
