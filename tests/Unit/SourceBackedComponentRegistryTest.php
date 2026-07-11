<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === 'v5.3.1');
assert($lock->pairId() === 'sf5-v5.3.1-96577eb1-0242425c');
assert($lock->toArray()['ui']['commit'] === '96577eb157ed785d5043d5323d24508613af8462');
assert($lock->toArray()['ui_smart']['commit'] === '0242425c8f1e20548a04319422ec2d0584cda1a9');

$registry = SourceBackedComponentRegistry::bundled();
assert($registry->get('sf-button')['source'] === 'smart/buttons');
assert($registry->get('sf-table')['source'] === 'smart/table');
$registry->assertPropsAllowed('sf-button', ['text' => 'Create', 'disabled' => false]);
$registry->assertPropsAllowed('sf-table', ['aria-label' => 'Pages', 'data' => ['columns' => [], 'rows' => []]]);

$failed = false;
try { $registry->assertPropsAllowed('sf-button', ['onclick' => 'unsafe']); } catch (InvalidArgumentException) { $failed = true; }
assert($failed);

echo "SourceBackedComponentRegistryTest passed\n";
