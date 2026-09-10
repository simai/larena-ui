<?php

declare(strict_types=1);

namespace Larena\Ui\Console\Commands;

use Illuminate\Console\Command;
use Larena\Ui\Developer\InstalledSmartAdapterCatalog;

final class UiListCommand extends Command
{
    protected $signature = 'larena:ui:list {--format=table : table or json}';
    protected $description = 'List installed Larena Smart adapters.';

    public function handle(InstalledSmartAdapterCatalog $catalog): int
    {
        $components = $catalog->components();
        if ($this->option('format') === 'json') {
            $this->line(json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $this->table(['ID', 'Kind', 'Version', 'Frontend', 'Adapter'], array_map(static fn (array $item): array => [
            $item['id'], $item['kind'], $item['version'], $item['frontend']['tag'] ?? '-', $item['adapter_available'] ? 'yes' : 'no',
        ], $components));

        return self::SUCCESS;
    }
}
