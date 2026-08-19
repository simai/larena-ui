<?php

declare(strict_types=1);

namespace Larena\Ui\Registry;

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartContributionProvider;
use Larena\Ui\Contracts\SmartViewDescriptor;
use Larena\Ui\Runtime\CompositeBackendRenderer;
use Larena\Ui\Runtime\SfElementBackendRenderer;

final class UiSmartContribution implements SmartContributionProvider
{
    public function contributionId(): string
    {
        return 'ui.defaults';
    }

    public function contribute(SmartRegistry $registry): void
    {
        $registry->registerRenderer(
            SmartComponentManifest::SIMAI_FRAMEWORK_RENDERER_ID,
            new SfElementBackendRenderer(),
        );
        $registry->registerRenderer(
            SmartComponentManifest::COMPOSITE_RENDERER_ID,
            new CompositeBackendRenderer(),
        );
        foreach ([
            'ui-button',
            'ui-input',
            'ui-textarea',
            'ui-checkbox',
            'ui-dropdown',
            'ui-dataview',
            'ui-pagination',
            'ui-badge',
            'ui-alert',
            'ui-modal',
            'ui-admin-menu',
            'ui-breadcrumbs',
            'ui-icon-button',
            'ui-avatar',
            'ui-tag',
            'ui-toggle',
            'admin-collection',
            'admin-record-editor',
            'dataview-table',
            'dataview-toolbar',
            'dataview-query-options',
        ] as $directory) {
            $registry->registerManifest(SmartComponentManifest::fromJsonFile(
                __DIR__ . '/../../resources/smart/' . $directory . '/manifest.json',
            ));
        }
        foreach ([
            'ui-button',
            'ui-dataview',
            'ui-dropdown',
            'ui-input',
            'ui-pagination',
            'dataview-toolbar',
            'dataview-query-options',
            'dataview-table',
            'admin-collection',
            'admin-record-editor',
        ] as $directory) {
            $registry->registerView(SmartViewDescriptor::fromJsonFile(
                __DIR__ . '/../../resources/smart/' . $directory . '/view/default.json',
            ));
        }
    }
}
