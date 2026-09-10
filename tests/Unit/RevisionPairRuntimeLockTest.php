<?php

declare(strict_types=1);
require_once __DIR__.'/../bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;

$data = FrontendRuntimeLock::bundled()->toArray();
$data['tag'] = $data['ui']['tag'] = $data['ui_smart']['tag'] = null;
$data['pair_id'] = 'ui-'.substr($data['ui']['commit'], 0, 12).'-smart-'.substr($data['ui_smart']['commit'], 0, 12);
$data['framework_registry']['compatibility_id'] = $data['pair_id'];
$data['framework_registry']['source']['files'] = 2; // registry and documentation source
$data['bundle_id'] = $data['pair_id'].'-registry-'.substr($data['framework_registry']['file_sha256'], 0, 8).'-exact-git-tree-v2';
$lock = FrontendRuntimeLock::fromArray($data);
assert($lock->pairId() === $data['pair_id']);
assert($lock->publicationExpectation()['sources'][2]['files'] === 2);
foreach ([
    static function (&$d) { $d['ui']['commit'] = str_repeat('f', 40); },
    static function (&$d) { $d['ui_smart']['commit'] = str_repeat('f', 40); },
    static function (&$d) { $d['framework_registry']['compatibility_id'] .= 'x'; },
    static function (&$d) { $d['publication_profile'] = 'verified-release-artifact-v1'; },
    static function (&$d) { $d['framework_registry']['source']['files'] = 0; },
] as $mutate) {
    $changed = $data;
    $mutate($changed);
    $rejected = false;
    try {
        FrontendRuntimeLock::fromArray($changed);
    } catch (RuntimeException) {
        $rejected = true;
    }
    if (!$rejected) {
        throw new LogicException('Invalid lock accepted');
    }
}
echo "RevisionPairRuntimeLockTest passed.\n";
