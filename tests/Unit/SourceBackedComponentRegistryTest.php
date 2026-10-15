<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === null);
assert($lock->pairId() === 'ui-376117eac4fe-larena-773c54ef7d4f');
assert(str_starts_with($lock->pairId(), 'ui-'));
assert($lock->toArray()['ui']['commit'] === '376117eac4fec7a187364c144a54fc8a6012749a');
assert($lock->toArray()['larena']['commit'] === '773c54ef7d4ff0d1b6056ddd3809c3846c675644');

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
