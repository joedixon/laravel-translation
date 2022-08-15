<?php

namespace JoeDixon\Translation\Types;

enum DriverType: string
{
    case Database = 'database';
    case File = 'file';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
