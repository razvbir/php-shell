<?php

declare(strict_types=1);

final class CommandNotFoundException extends Exception {
    const INVALID_COMMAND_MESSAGE = ': command not found';
    const INVALID_COMMAND_CODE = 404;

    public static function make(string $commandName = ''): static
    {
        return new CommandNotFoundException(
            $commandName.self::INVALID_COMMAND_MESSAGE,
            self::INVALID_COMMAND_CODE,
        );
    }
};
