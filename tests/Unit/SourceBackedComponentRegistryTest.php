<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === null);
assert($lock->pairId() === 'ui-c408b1c544f9-larena-9f3e80fb16e3');
assert(str_starts_with($lock->pairId(), 'ui-'));
assert($lock->toArray()['ui']['commit'] === 'c408b1c544f94b19b20a0bd2ae953e5bb67a6d50');
assert($lock->package('larena')['commit'] === '9f3e80fb16e3d66d860ddea8828f6640a0ac5ca4');

$registry = SourceBackedComponentRegistry::bundled();
assert($registry->get('sf-button')['source'] === 'ui/distr/smart/buttons');
assert($registry->get('sf-table')['source'] === 'larena/smart/table');
assert($registry->get('sf-data-view')['source'] === 'larena/smart/data-view');
assert($registry->get('sf-badge')['source'] === 'ui/distr/smart/badges');
assert($registry->get('sf-alert')['source'] === 'ui/distr/smart/alert');
assert($registry->get('sf-pagination')['source'] === 'ui/distr/smart/pagination');
assert($registry->get('sf-input')['source'] === 'ui/distr/smart/inputs');
assert($registry->get('sf-modal')['source'] === 'ui/distr/smart/modal');
assert($registry->get('sf-textarea')['source'] === 'ui/distr/smart/textarea');
assert($registry->get('sf-dropdown')['source'] === 'ui/distr/smart/dropdown');
assert($registry->get('sf-checkbox')['source'] === 'ui/distr/smart/checkbox');
$registry->assertPropsAllowed('sf-button', ['text' => 'Create', 'disabled' => false]);
$registry->assertPropsAllowed('sf-table', ['aria-label' => 'Pages', 'data' => ['columns' => [], 'rows' => []]]);
$registry->assertPropsAllowed('sf-data-view', ['id' => 'cms-records', 'aria-label' => 'Records']);
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
