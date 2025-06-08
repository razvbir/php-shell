<?php

declare(strict_types=1);

enum CommandType: string
{
    case exit = 'exit';
    case echo = 'echo';
    case type = 'type';
    case pwd = 'pwd';
    case cd = 'cd';
    case external = 'external';

    /** @return array<string> */
    public static function builtIns(): array
    {
        return [self::exit->value, self::echo->value, self::type->value, self::pwd->value, self::cd->value];
    }
}
