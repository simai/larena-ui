<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === null);
assert($lock->pairId() === 'ui-7fb26d395af2-pro-db490626ba74-chart-58ba480db497');
assert(str_starts_with($lock->pairId(), 'ui-'));
assert($lock->toArray()['ui']['commit'] === '7fb26d395af28dfcaac2a5df9de254b3067dc4ae');
assert($lock->package('pro')['commit'] === 'db490626ba74caeed832352c7bc005410ca825e2');

$registry = SourceBackedComponentRegistry::bundled();
assert($registry->get('sf-button')['source'] === 'ui/distr/smart/button');
assert($registry->get('sf-view-table')['source'] === 'pro/smart/view-table');
assert($registry->get('sf-view')['source'] === 'pro/smart/view');
assert($registry->get('sf-badge')['source'] === 'ui/distr/smart/badge');
assert($registry->get('sf-alert')['source'] === 'ui/distr/smart/alert');
assert($registry->get('sf-pagination')['source'] === 'ui/distr/smart/pagination');
assert($registry->get('sf-input')['source'] === 'ui/distr/smart/input');
assert($registry->get('sf-modal')['source'] === 'ui/distr/smart/modal');
assert($registry->get('sf-input-textarea')['source'] === 'ui/distr/smart/input-textarea');
assert($registry->get('sf-dropdown')['source'] === 'ui/distr/smart/dropdown');
assert($registry->get('sf-checkbox')['source'] === 'ui/distr/smart/checkbox');
$registry->assertPropsAllowed('sf-button', ['text' => 'Create', 'disabled' => false]);
$registry->assertPropsAllowed('sf-view-table', ['aria-label' => 'Pages', 'data' => ['columns' => [], 'rows' => []]]);
$registry->assertPropsAllowed('sf-view', ['id' => 'cms-records', 'aria-label' => 'Records']);
$registry->assertPropsAllowed('sf-input', ['label' => 'Title', 'required' => true, 'invalid' => false, 'autocomplete' => 'new-password']);
$registry->assertPropsAllowed('sf-view-table', ['selectable' => false, 'settings' => false, 'actions' => false]);
try {
    $registry->assertPropsAllowed('sf-view-table', ['read-only' => 'true']);
    throw new RuntimeException('Expected invented upstream property to fail.');
} catch (InvalidArgumentException $exception) {
    assert(str_contains($exception->getMessage(), 'read-only'));
}
$registry->assertPropsAllowed('sf-modal', ['id' => 'dialog', 'heading' => 'Dialog', 'overlay' => true]);
$registry->assertPropsAllowed('sf-dropdown', ['name' => 'locale', 'options' => [['text' => 'English', 'value' => 'en']]]);
$registry->assertPropsAllowed('sf-checkbox', ['name' => 'is_active', 'label' => 'Active', 'checked' => true]);

$failed = false;
try { $registry->assertPropsAllowed('sf-button', ['onclick' => 'unsafe']); } catch (InvalidArgumentException) { $failed = true; }
assert($failed);

// An old tag (the Framework's rename R3) is read as its new one, and drawn as the new one.
assert(SourceBackedComponentRegistry::currentTag('sf-table') === 'sf-view-table' && SourceBackedComponentRegistry::currentTag('sf-button') === 'sf-button');
assert($registry->get('sf-icon-button')['source'] === 'ui/distr/smart/button-icon');
assert(str_starts_with(\Larena\Ui\Smart::render('sf-icon-button', ['aria-label' => 'Open'], $registry)->html, '<sf-button-icon '));

echo "SourceBackedComponentRegistryTest passed\n";
