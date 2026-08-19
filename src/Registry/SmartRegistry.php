<?php

declare(strict_types=1);

namespace Larena\Ui\Registry;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartBackendRenderer;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartContributionProvider;
use Larena\Ui\Contracts\SmartViewDescriptor;

final class SmartRegistry
{
    /** @var array<string, SmartComponentManifest> */
    private array $manifests = [];

    /** @var array<string, SmartBackendRenderer> */
    private array $renderers = [];

    /** @var array<string, SmartViewDescriptor> */
    private array $views = [];

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
        if (!SmartComponentManifest::isRendererId($id)) {
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

    public function registerView(SmartViewDescriptor $view): void
    {
        if (!$view->isValid()) {
            throw new InvalidArgumentException('ui_smart_view_invalid:' . $view->componentKey . ':' . $view->viewKey);
        }
        $manifest = $this->manifest($view->componentKey);
        if (!array_key_exists($view->viewKey, $manifest->views)) {
            throw new InvalidArgumentException('ui_smart_view_not_declared:' . $view->componentKey . ':' . $view->viewKey);
        }
        foreach (array_keys($view->presets) as $preset) {
            if (!array_key_exists($preset, $manifest->presets)) {
                throw new InvalidArgumentException('ui_smart_view_preset_not_declared:' . $view->componentKey . ':' . $preset);
            }
        }
        $allowedModifiers = is_array($manifest->constraints['modifiers'] ?? null)
            ? array_values(array_filter($manifest->constraints['modifiers'], 'is_string'))
            : [];
        foreach (array_keys($view->modifiers) as $modifier) {
            if (!in_array($modifier, $allowedModifiers, true)) {
                throw new InvalidArgumentException('ui_smart_view_modifier_not_declared:' . $view->componentKey . ':' . $modifier);
            }
        }
        $key = $view->componentKey . ':' . $view->viewKey;
        if (isset($this->views[$key])) {
            throw new InvalidArgumentException('ui_smart_view_collision:' . $key);
        }
        $this->views[$key] = $view;
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

    public function view(string $componentKey, string $viewKey): SmartViewDescriptor
    {
        $key = $componentKey . ':' . $viewKey;

        return $this->views[$key]
            ?? throw new InvalidArgumentException('ui_smart_view_unknown:' . $key);
    }

    /** @return array<string, SmartViewDescriptor> */
    public function views(): array
    {
        $views = $this->views;
        ksort($views, SORT_STRING);

        return $views;
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
