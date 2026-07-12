<?php

declare(strict_types=1);

namespace Larena\Ui;

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Enums\HydrationStrategy;
use Larena\Ui\Enums\RenderStrategy;

/** Semantic anchor rendered with the native Simai Framework button component. */
final class SfActionLink
{
    public static function render(
        string $href,
        string $text,
        string $scheme = 'secondary',
        string $type = 'tonal',
        string $size = '1',
        bool $current = false,
    ): BackendRenderResult {
        self::assertEnum('scheme', $scheme, ['primary', 'secondary', 'success', 'warning', 'error', 'neutral', 'on-surface']);
        self::assertEnum('type', $type, ['default', 'tonal', 'outline', 'text']);
        self::assertEnum('size', $size, ['1/3', '1/2', '1', '2', '3']);

        $attributes = [
            'href' => $href,
            'class' => sprintf('sf-button sf-button--%s sf-button--%s sf-button--size-%s', $type, $scheme, $size),
            'data-larena-ui-runtime' => 'larena/ui:sf_action_link',
        ];
        if ($current) {
            $attributes['aria-current'] = 'page';
        }

        $html = '<a' . self::attributes($attributes) . '><span class="sf-button-text-container">'
            . self::escape($text)
            . '</span></a>';

        return new BackendRenderResult(
            $html,
            RenderStrategy::Native,
            new HydrationContract(HydrationStrategy::None, hash('sha256', $html), 'native-anchor', false),
            Smart::assetGraph('sf-button')->requirements,
        );
    }

    /** @param list<string> $allowed */
    private static function assertEnum(string $name, string $value, array $allowed): void
    {
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException('ui_sf_action_link_invalid_' . $name . ':' . $value);
        }
    }

    /** @param array<string, string> $attributes */
    private static function attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . self::escape($key) . '="' . self::escape($value) . '"';
        }

        return $html;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
