<?php

declare(strict_types=1);

namespace Larena\Ui\Facades;

use Closure;
use InvalidArgumentException;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Runtime\SmartManager;

/**
 * Short application call surface for canonical Larena Smart component keys.
 *
 * The application provider supplies the container-owned SmartManager so
 * package contributions, validation and diagnostics stay on the same path.
 */
final class Smart
{
    /** @var (Closure(): mixed)|null */
    private static ?Closure $managerResolver = null;

    /** @param Closure(): mixed $resolver */
    public static function resolveUsing(Closure $resolver): void
    {
        self::$managerResolver = $resolver;
    }

    public static function forgetResolver(): void
    {
        self::$managerResolver = null;
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $assetActivation
     * @param array<string, mixed> $slots
     */
    public static function render(
        string $componentKey,
        array $props = [],
        array $assetActivation = [],
        array $slots = [],
    ): FrontendRenderArtifact {
        return self::manager()->render($componentKey, $props, $assetActivation, $slots);
    }

    public static function manager(): SmartManager
    {
        if (self::$managerResolver === null) {
            throw new InvalidArgumentException('ui_smart_facade_manager_not_configured');
        }

        $manager = (self::$managerResolver)();
        if (!$manager instanceof SmartManager) {
            throw new InvalidArgumentException('ui_smart_facade_manager_invalid');
        }

        return $manager;
    }
}
