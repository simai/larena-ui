<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

interface SmartBackendRenderer
{
    /**
     * @param array<string, mixed> $props
     * @param array<string, string> $slots
     */
    public function render(SmartComponentManifest $manifest, array $props, array $slots = []): BackendRenderResult;
}
