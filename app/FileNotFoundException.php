<?php

declare(strict_types=1);

final class FileNotFoundException extends Exception {
    private const MESSAGE = ': No such file or directory';
    private const CODE = 404;

    public static function make(string $commandName = ''): self
    {
        return new self($commandName . self::MESSAGE, self::CODE);
    }
}
