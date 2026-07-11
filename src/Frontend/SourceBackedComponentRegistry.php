<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use InvalidArgumentException;

final readonly class SourceBackedComponentRegistry
{
    private FrontendRuntimeLock $lock;

    public function __construct(?FrontendRuntimeLock $lock = null)
    {
        $this->lock = $lock ?? FrontendRuntimeLock::bundled();
    }

    public static function bundled(): self
    {
        return new self(FrontendRuntimeLock::bundled());
    }

    /** @return array<string, mixed> */
    public function get(string $tag): array
    {
        if (!preg_match('/^sf-[a-z0-9-]+$/', $tag)) {
            throw new InvalidArgumentException('ui_smart_tag_invalid');
        }
        try {
            return $this->lock->component($tag);
        } catch (\RuntimeException) {
            throw new InvalidArgumentException('ui_smart_component_unknown:' . $tag);
        }
    }

    /** @param array<string, mixed> $props */
    public function assertPropsAllowed(string $tag, array $props): void
    {
        $definition = $this->get($tag);
        $allowed = $definition['attributes'] ?? [];
        if (!is_array($allowed)) {
            throw new InvalidArgumentException('ui_smart_component_attributes_invalid:' . $tag);
        }
        if ($tag === 'sf-table') {
            $allowed = [...$allowed, 'data'];
        }
        if ($tag === 'sf-dropdown') {
            $allowed = [...$allowed, 'options'];
        }
        foreach (array_keys($props) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('ui_smart_prop_unknown:' . $tag . ':' . (string) $key);
            }
        }
    }

    public function pairId(): string { return $this->lock->pairId(); }
}
