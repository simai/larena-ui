<?php

declare(strict_types=1);

namespace Larena\Ui\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Larena\Ui\Developer\InstalledSmartAdapterCatalog;

final class UiDescribeCommand extends Command
{
    protected $signature = 'larena:ui:describe {component : Stable Larena adapter ID} {--format=json : json or ai}';
    protected $description = 'Describe one installed Larena Smart adapter.';

    public function handle(InstalledSmartAdapterCatalog $catalog): int
    {
        try {
            $description = $catalog->describe((string) $this->argument('component'), $this->option('format') === 'ai');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
        $this->line(json_encode($description, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
