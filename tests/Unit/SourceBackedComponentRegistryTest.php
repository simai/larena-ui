<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === 'v5.4.0-local.3');
assert($lock->pairId() === 'sf-v5.4.0-local.3-d1daa951-c843857c');
assert(str_starts_with($lock->pairId(), 'sf-v'));
assert($lock->toArray()['ui']['commit'] === 'd1daa951dd08b94a9f209fd9f31a78d2b3779563');
assert($lock->toArray()['ui_smart']['commit'] === 'c843857cf52408724404768814e9443d4a89e4eb');

$registry = SourceBackedComponentRegistry::bundled();
assert($registry->get('sf-button')['source'] === 'smart/buttons');
assert($registry->get('sf-table')['source'] === 'smart/table');
assert($registry->get('sf-badge')['source'] === 'smart/badges');
assert($registry->get('sf-alert')['source'] === 'smart/alert');
assert($registry->get('sf-pagination')['source'] === 'smart/pagination');
assert($registry->get('sf-input')['source'] === 'smart/inputs');
assert($registry->get('sf-modal')['source'] === 'smart/modal');
assert($registry->get('sf-textarea')['source'] === 'smart/textarea');
assert($registry->get('sf-dropdown')['source'] === 'smart/dropdown');
assert($registry->get('sf-checkbox')['source'] === 'smart/checkbox');
$registry->assertPropsAllowed('sf-button', ['text' => 'Create', 'disabled' => false]);
$registry->assertPropsAllowed('sf-table', ['aria-label' => 'Pages', 'data' => ['columns' => [], 'rows' => []]]);
$registry->assertPropsAllowed('sf-input', ['label' => 'Title', 'required' => true, 'error' => false, 'autocomplete' => 'new-password']);
$registry->assertPropsAllowed('sf-table', ['selectable' => false, 'settings' => false, 'actions' => false]);
try {
    $registry->assertPropsAllowed('sf-table', ['read-only' => 'true']);
    throw new RuntimeException('Expected invented upstream property to fail.');
} catch (InvalidArgumentException $exception) {
    assert(str_contains($exception->getMessage(), 'read-only'));
}
$registry->assertPropsAllowed('sf-modal', ['id' => 'dialog', 'title' => 'Dialog', 'overlay' => true]);
$registry->assertPropsAllowed('sf-dropdown', ['name' => 'locale', 'options' => [['text' => 'English', 'value' => 'en']]]);
$registry->assertPropsAllowed('sf-checkbox', ['name' => 'is_active', 'label' => 'Active', 'checked' => true]);

$failed = false;
try { $registry->assertPropsAllowed('sf-button', ['onclick' => 'unsafe']); } catch (InvalidArgumentException) { $failed = true; }
assert($failed);

echo "SourceBackedComponentRegistryTest passed\n";
