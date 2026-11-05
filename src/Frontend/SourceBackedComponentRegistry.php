<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use InvalidArgumentException;

final readonly class SourceBackedComponentRegistry
{
    /**
     * The Framework's smart tag rename (wave R3, 2026-11-03): an old tag still named by a package, a stored Document or
     * an older adapter is read as its new one, so the page draws the new tag. The Framework keeps the old tags until
     * 2027-01-01; this table goes with them.
     */
    public const array RENAMED = [
        'sf-avatars' => 'sf-avatar-group', 'sf-close' => 'sf-button-close', 'sf-copy-button' => 'sf-button-copy',
        'sf-icon-button' => 'sf-button-icon', 'sf-code-input' => 'sf-input-code', 'sf-color' => 'sf-input-color',
        'sf-country-code' => 'sf-input-country', 'sf-datepicker' => 'sf-input-date', 'sf-image-field' => 'sf-input-image',
        'sf-lookup' => 'sf-input-lookup', 'sf-quantity' => 'sf-input-quantity', 'sf-rich-text' => 'sf-input-rich',
        'sf-textarea' => 'sf-input-textarea', 'sf-context-menu' => 'sf-menu-context', 'sf-page-header' => 'sf-header-page',
        'sf-site-header' => 'sf-header-site', 'sf-inline-editor' => 'sf-editor-inline', 'sf-composition-overlay' => 'sf-editor-overlay',
        'sf-download-file' => 'sf-file-download', 'sf-range-slider' => 'sf-slider-range', 'sf-data-view' => 'sf-view',
        'sf-table' => 'sf-view-table', 'sf-property-view' => 'sf-view-properties', 'sf-content-editor' => 'sf-editor-content',
        'sf-thread-list' => 'sf-chat-threads', 'sf-change-confirm' => 'sf-confirm-change',
    ];

    private FrontendRuntimeLock $lock;

    public function __construct(?FrontendRuntimeLock $lock = null)
    {
        $this->lock = $lock ?? FrontendRuntimeLock::bundled();
    }

    public static function bundled(): self
    {
        return new self(FrontendRuntimeLock::bundled());
    }

    /** The tag the pinned Framework draws: a renamed one's new name, any other as it is. */
    public static function currentTag(string $tag): string
    {
        return self::RENAMED[$tag] ?? $tag;
    }

    /** @return array<string, mixed> */
    public function get(string $tag): array
    {
        if (!preg_match('/^sf-[a-z0-9-]+$/', $tag)) {
            throw new InvalidArgumentException('ui_smart_tag_invalid');
        }
        $tag = self::currentTag($tag);
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
        $tag = self::currentTag($tag);
        $allowed = $definition['attributes'] ?? [];
        if (!is_array($allowed)) {
            throw new InvalidArgumentException('ui_smart_component_attributes_invalid:' . $tag);
        }
        if ($tag === 'sf-view-table') {
            $allowed = [...$allowed, 'data'];
        }
        if ($tag === 'sf-dropdown') {
            $allowed = [...$allowed, 'options'];
        }
        if ($tag === 'sf-alert-bar') {
            $allowed = [...$allowed, 'items'];
        }
        if ($tag === 'sf-pagination') {
            $allowed = [...$allowed, 'next-href'];
        }
        if (in_array($tag, ['sf-admin-menu', 'sf-breadcrumbs'], true)) {
            $allowed = [...$allowed, 'items'];
        }
        foreach (array_keys($props) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('ui_smart_prop_unknown:' . $tag . ':' . (string) $key);
            }
        }
    }

    public function pairId(): string { return $this->lock->pairId(); }
}
