<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

/**
 * The Framework utilities a collection's outer layout and content region use. Consumers must resolve these family IDs
 * from the Framework Contract Registry before emitting the classes. Utility placement is part of the admin.collection
 * recipe: collection utilities compose the outer layout, while overflow belongs to the content region that actually owns
 * horizontal scrolling. Layout's recipe (AdminCollectionLayoutPlan) names the same ones, for an assembly that has Layout.
 */
final class AdminCollectionUtilities
{
    public const string RECIPE_KEY = 'admin.collection';

    /** @return array<string, list<string>> */
    public static function classes(): array
    {
        return array_merge(...array_values(self::regions()));
    }

    /** @return array<string, array<string, list<string>>> */
    public static function regions(): array
    {
        return [
            'collection' => [
                'utility.display' => ['flex'],
                'utility.flex-direction' => ['flex-col'],
                'utility.gap' => ['gap-1'],
            ],
            'content' => [
                'utility.overflow' => ['overflow-x-auto'],
                'utility.width' => ['w-full'],
            ],
        ];
    }
}
