<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\SmartBackendRenderer;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Smart;

final class SfElementBackendRenderer implements SmartBackendRenderer
{
    public function render(SmartComponentManifest $manifest, array $props, array $slots = []): BackendRenderResult
    {
        if ($manifest->frontendRuntime !== 'simai-framework' || $manifest->frontendTag === null) {
            throw new InvalidArgumentException('ui_smart_frontend_contract_missing:' . $manifest->componentKey);
        }
        if ($slots !== []) {
            throw new InvalidArgumentException('ui_smart_slots_not_supported_by_renderer:' . $manifest->componentKey);
        }

        return Smart::render($manifest->frontendTag, $props);
    }
}
