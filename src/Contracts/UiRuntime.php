<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

interface UiRuntime
{
    public function validateManifest(SmartComponentManifest $manifest): bool;

    public function renderBackend(SmartComponentManifest $manifest, mixed $props): BackendRenderResult;

    public function collectAssetGraph(SmartComponentManifest $manifest): UiAssetGraph;

    public function validateDesignPack(DesignPackDescriptor $designPack): bool;

    public function validateResourcePack(UiResourcePackManifest $resourcePack): bool;
}
