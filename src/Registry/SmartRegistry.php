<?php

declare(strict_types=1);

namespace Larena\Ui\Registry;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartBackendRenderer;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartContributionProvider;

final class SmartRegistry
{
    /** @var array<string, SmartComponentManifest> */
    private array $manifests = [];

    /** @var array<string, SmartBackendRenderer> */
    private array $renderers = [];

    /** @var array<string, true> */
    private array $contributions = [];

    public static function withDefaults(): self
    {
        $registry = new self();
        $registry->registerContribution(new UiSmartContribution());

        return $registry;
    }

    public function registerContribution(SmartContributionProvider $provider): void
    {
        $id = trim($provider->contributionId());
        if (!SmartComponentManifest::isStableKey($id)) {
            throw new InvalidArgumentException('ui_smart_contribution_id_invalid');
        }
        if (isset($this->contributions[$id])) {
            return;
        }
        $provider->contribute($this);
        $this->contributions[$id] = true;
    }

    public function registerRenderer(string $id, SmartBackendRenderer $renderer): void
    {
        if (!SmartComponentManifest::isStableKey($id)) {
            throw new InvalidArgumentException('ui_smart_renderer_id_invalid');
        }
        if (isset($this->renderers[$id]) && $this->renderers[$id] !== $renderer) {
            throw new InvalidArgumentException('ui_smart_renderer_collision:' . $id);
        }
        $this->renderers[$id] = $renderer;
    }

    public function registerManifest(SmartComponentManifest $manifest): void
    {
        if (!$manifest->isCanonical()) {
            throw new InvalidArgumentException('ui_smart_manifest_invalid:' . $manifest->componentKey);
        }
        if (isset($this->manifests[$manifest->componentKey])) {
            throw new InvalidArgumentException('ui_smart_manifest_collision:' . $manifest->componentKey);
        }
        $this->manifests[$manifest->componentKey] = $manifest;
    }

    public function manifest(string $key): SmartComponentManifest
    {
        return $this->manifests[$key]
            ?? throw new InvalidArgumentException('ui_smart_manifest_unknown:' . $key);
    }

    public function renderer(string $id): SmartBackendRenderer
    {
        return $this->renderers[$id]
            ?? throw new InvalidArgumentException('ui_smart_renderer_unknown:' . $id);
    }

    /** @return array<string, SmartComponentManifest> */
    public function manifests(): array
    {
        $manifests = $this->manifests;
        ksort($manifests);

        return $manifests;
    }

    /** @return list<SmartComponentManifest> */
    public function atlasManifests(): array
    {
        $manifests = array_values(array_filter(
            $this->manifests(),
            static fn (SmartComponentManifest $manifest): bool => ($manifest->atlas['visible'] ?? false) === true,
        ));
        usort($manifests, static function (SmartComponentManifest $left, SmartComponentManifest $right): int {
            $leftOrder = is_int($left->atlas['order'] ?? null) ? $left->atlas['order'] : PHP_INT_MAX;
            $rightOrder = is_int($right->atlas['order'] ?? null) ? $right->atlas['order'] : PHP_INT_MAX;

            return ($leftOrder <=> $rightOrder) ?: ($left->componentKey <=> $right->componentKey);
        });

        return $manifests;
    }

    /** @return list<string> */
    public function contributionIds(): array
    {
        $ids = array_keys($this->contributions);
        sort($ids);

        return $ids;
    }
}
