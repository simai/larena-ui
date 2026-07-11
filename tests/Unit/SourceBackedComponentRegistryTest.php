<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === 'v5.3.2');
assert($lock->pairId() === 'sf5-v5.3.2-7e836d8a-0242425c');
assert($lock->toArray()['ui']['commit'] === '7e836d8a9414d5da553fb1ab0404721e5b48769a');
assert($lock->toArray()['ui_smart']['commit'] === '0242425c8f1e20548a04319422ec2d0584cda1a9');

$registry = SourceBackedComponentRegistry::bundled();
assert($registry->get('sf-button')['source'] === 'smart/buttons');
assert($registry->get('sf-table')['source'] === 'smart/table');
assert($registry->get('sf-badge')['source'] === 'smart/badges');
assert($registry->get('sf-alert')['source'] === 'smart/alert');
assert($registry->get('sf-pagination')['source'] === 'smart/pagination');
assert($registry->get('sf-input')['source'] === 'smart/inputs');
assert($registry->get('sf-modal')['source'] === 'smart/modal');
$registry->assertPropsAllowed('sf-button', ['text' => 'Create', 'disabled' => false]);
$registry->assertPropsAllowed('sf-table', ['aria-label' => 'Pages', 'data' => ['columns' => [], 'rows' => []]]);
$registry->assertPropsAllowed('sf-input', ['label' => 'Title', 'required' => true, 'error' => false]);
$registry->assertPropsAllowed('sf-modal', ['id' => 'dialog', 'title' => 'Dialog', 'overlay' => true]);

$failed = false;
try { $registry->assertPropsAllowed('sf-button', ['onclick' => 'unsafe']); } catch (InvalidArgumentException) { $failed = true; }
assert($failed);

echo "SourceBackedComponentRegistryTest passed\n";
