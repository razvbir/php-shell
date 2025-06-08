<?php

declare(strict_types=1);

enum CommandType: string
{
    case exit = 'exit';
    case echo = 'echo';
    case type = 'type';
    case pwd = 'pwd';
    case cd = 'cd';
    case history = 'history';
    case external = 'external';

    /** @return array<string> */
    public static function builtIns(): array
    {
        return array_map(fn (CommandType $c): string => $c->value, array_slice(self::cases(), 0, -1));
    }
}
