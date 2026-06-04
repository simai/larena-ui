<?php

declare(strict_types=1);

namespace Larena\Ui\Enums;

enum RenderStrategy: string
{
    case Native = 'native';
    case Host = 'host';
    case Skeleton = 'skeleton';
    case None = 'none';

    public function requiresFrontendRuntime(): bool
    {
        return in_array($this, [self::Host, self::Skeleton], true);
    }
}
