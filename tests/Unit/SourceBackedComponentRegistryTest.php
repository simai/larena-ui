<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

$lock = FrontendRuntimeLock::bundled();
assert($lock->tag() === null);
assert($lock->pairId() === 'ui-4f5678e8c0ed-pro-cfaab360bb39-chart-58ba480db497-admin-25245d70cde8-editor-52109ce5017f');
assert(str_starts_with($lock->pairId(), 'ui-'));
assert($lock->toArray()['ui']['commit'] === '4f5678e8c0ed77938a4bb880d44086e786ee36ac');
assert($lock->package('pro')['commit'] === 'cfaab360bb397013e3eadec139b60b9d9b9f14e4');

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
// The storage page editor hosts sf-editor only when the lock lists it: the Editor set (editor-52109ce5017f, sf-editor 1.6.2) carries it.
assert($registry->get('sf-editor')['source'] === 'editor/smart/editor' && $registry->get('sf-editor')['package'] === 'editor');
$registry->assertPropsAllowed('sf-editor', ['heading' => 'Page', 'caption' => 'Body', 'label' => 'Editor', 'preferences-key' => 'storage-document']);
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
