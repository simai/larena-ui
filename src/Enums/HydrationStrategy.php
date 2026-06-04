<?php

declare(strict_types=1);

namespace Larena\Ui\Enums;

enum HydrationStrategy: string
{
    case None = 'none';
    case Adopt = 'adopt';
    case Replace = 'replace';
    case Island = 'island';

    public function requiresPropsHash(): bool
    {
        return $this !== self::None;
    }
}
