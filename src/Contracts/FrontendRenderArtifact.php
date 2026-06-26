<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

final readonly class FrontendRenderArtifact
{
    /**
     * @param array<string, mixed> $assetActivation
     * @param array<string, mixed> $diagnostics
     */
    public function __construct(
        public BackendRenderResult $render,
        public UiAssetGraph $assetGraph,
        public array $assetActivation,
        public array $diagnostics = [],
    ) {
    }

    public function html(): string
    {
        return $this->render->html;
    }

    /**
     * @return list<string>
     */
    public function assetTags(): array
    {
        $tags = $this->assetActivation['renderable_tags'] ?? [];

        if (!is_array($tags)) {
            return [];
        }

        return array_values(array_filter(
            $tags,
            static fn (mixed $tag): bool => is_string($tag) && trim($tag) !== '',
        ));
    }

    public function isRenderable(): bool
    {
        return $this->render->isSafe()
            && $this->assetGraph->isValid()
            && ($this->assetActivation['activation_owner'] ?? null) === 'larena/core:core.assets'
            && ($this->assetActivation['physical_publication_ready'] ?? null) === true
            && ($this->assetActivation['writes_database'] ?? null) === false
            && ($this->assetActivation['copies_to_root'] ?? null) === false
            && ($this->assetActivation['uses_hardcoded_cdn'] ?? null) === false
            && $this->assetTags() !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema' => 'larena.ui.frontend_render_artifact.v1',
            'owner_package' => 'larena/ui',
            'renderable' => $this->isRenderable(),
            'html_is_render_artifact' => true,
            'html' => $this->html(),
            'hydration' => [
                'valid' => $this->render->hydration->isValid(),
                'strategy' => $this->render->hydration->strategy->value,
                'props_hash' => $this->render->hydration->propsHash,
                'dom_strategy' => $this->render->hydration->domStrategy,
                'frontend_runtime_available' => $this->render->hydration->frontendRuntimeAvailable,
            ],
            'backend_render' => [
                'safe' => $this->render->isSafe(),
                'strategy' => $this->render->strategy->value,
                'copied_frontend_source' => $this->render->copiedFrontendSource,
                'html_length' => strlen($this->render->html),
            ],
            'asset_graph' => [
                'valid' => $this->assetGraph->isValid(),
                'requirement_count' => count($this->assetGraph->requirements),
                'critical_requirement_count' => count($this->assetGraph->criticalRequirements()),
                'explain' => $this->assetGraph->explain,
            ],
            'asset_activation' => [
                'schema' => $this->assetActivation['schema'] ?? null,
                'status' => $this->assetActivation['status'] ?? null,
                'activation_owner' => $this->assetActivation['activation_owner'] ?? null,
                'activation_mode' => $this->assetActivation['activation_mode'] ?? null,
                'physical_publication_ready' => $this->assetActivation['physical_publication_ready'] ?? null,
                'writes_database' => $this->assetActivation['writes_database'] ?? null,
                'copies_to_root' => $this->assetActivation['copies_to_root'] ?? null,
                'uses_hardcoded_cdn' => $this->assetActivation['uses_hardcoded_cdn'] ?? null,
                'asset_count' => $this->assetActivation['asset_count'] ?? null,
                'renderable_tag_count' => count($this->assetTags()),
                'renderable_tags' => $this->assetTags(),
            ],
            'diagnostics' => $this->diagnostics,
        ];
    }
}
