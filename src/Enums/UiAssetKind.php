<?php

declare(strict_types=1);

namespace Larena\Ui\Enums;

enum UiAssetKind: string
{
    case Css = 'css';
    case JavaScript = 'javascript';
    case Module = 'module';
    case Font = 'font';
    case Manifest = 'manifest';
}
