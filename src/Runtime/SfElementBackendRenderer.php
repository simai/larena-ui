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

        // The Framework renamed two of the icon button's attributes (variant → kind, type → appearance; the old names
        // are deprecated). The manifest keeps its props; the element receives the current names.
        if ($manifest->frontendTag === 'sf-icon-button') {
            $renamed = [];
            foreach ($props as $name => $value) {
                $renamed[['variant' => 'kind', 'type' => 'appearance'][$name] ?? $name] = $value;
            }
            $props = $renamed;
        }

        return Smart::render($manifest->frontendTag, $props);
    }
}
