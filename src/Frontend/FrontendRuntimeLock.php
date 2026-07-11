<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use RuntimeException;

final readonly class FrontendRuntimeLock
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    public static function bundled(): self
    {
        $path = dirname(__DIR__, 2) . '/resources/sf5/runtime-lock.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        $lock = new self($data);
        $lock->assertValid();
        return $lock;
    }

    public function pairId(): string { return (string) $this->data['pair_id']; }
    public function tag(): string { return (string) $this->data['tag']; }

    /** @return array<string, mixed> */
    public function component(string $tag): array
    {
        $component = $this->data['components'][$tag] ?? null;
        if (!is_array($component)) {
            throw new RuntimeException('ui_frontend_component_not_locked:' . $tag);
        }
        return $component;
    }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->data; }

    /** @return list<array{repository:string,commit:string,tree:string,mount:string,sha256:string}> */
    public function publicationSources(string $uiRepository, string $smartRepository): array
    {
        return [
            $this->source($this->data['ui'], $uiRepository),
            $this->source($this->data['ui_smart'], $smartRepository),
        ];
    }

    /** @return array{repository:string,commit:string,tree:string,mount:string,sha256:string} */
    private function source(mixed $data, string $repository): array
    {
        if (!is_array($data)) {
            throw new RuntimeException('ui_frontend_runtime_source_invalid');
        }
        return [
            'repository' => $repository,
            'commit' => (string) $data['commit'],
            'tree' => (string) $data['tree'],
            'mount' => (string) $data['mount'],
            'sha256' => (string) $data['sha256'],
        ];
    }

    private function assertValid(): void
    {
        if (($this->data['schema'] ?? null) !== 'larena.ui.frontend_runtime_lock.v1'
            || !preg_match('/^[a-z0-9][a-z0-9._-]+$/', (string) ($this->data['pair_id'] ?? ''))
            || !preg_match('/^v\d+\.\d+\.\d+$/', (string) ($this->data['tag'] ?? ''))
        ) {
            throw new RuntimeException('ui_frontend_runtime_lock_invalid');
        }
        foreach (['ui', 'ui_smart'] as $source) {
            $value = $this->data[$source] ?? null;
            if (!is_array($value)
                || !preg_match('/^[a-f0-9]{40}$/', (string) ($value['commit'] ?? ''))
                || !preg_match('/^[a-f0-9]{64}$/', (string) ($value['sha256'] ?? ''))
            ) {
                throw new RuntimeException('ui_frontend_runtime_source_invalid:' . $source);
            }
        }
    }
}
