<?php

declare(strict_types=1);

namespace Larena\Ui\Registry;

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartContributionProvider;
use Larena\Ui\Runtime\SfElementBackendRenderer;

final class UiSmartContribution implements SmartContributionProvider
{
    public function contributionId(): string
    {
        return 'ui.defaults';
    }

    public function contribute(SmartRegistry $registry): void
    {
        $registry->registerRenderer('ui.sf_element', new SfElementBackendRenderer());
        foreach (['ui-button', 'ui-dataview', 'ui-input'] as $directory) {
            $registry->registerManifest(SmartComponentManifest::fromJsonFile(
                __DIR__ . '/../../resources/smart/' . $directory . '/manifest.json',
            ));
        }
    }
}
