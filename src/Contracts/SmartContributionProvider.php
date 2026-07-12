<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Registry\SmartRegistry;

interface SmartContributionProvider
{
    public function contributionId(): string;

    public function contribute(SmartRegistry $registry): void;
}
