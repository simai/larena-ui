<?php

declare(strict_types=1);

// The SIMAI Chart set's tags render on the server (2026-11-08): a chart's lists and objects (items, names, colors,
// hidden series ...) go in its settings island, its scalar settings stay attributes, and its files come from the
// chart package's Smart tree.

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeAssetResolver;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Smart;

$lock = FrontendRuntimeLock::bundled();
$passport = ['sf-chart-bar', 'sf-chart-box', 'sf-chart-candlestick', 'sf-chart-funnel', 'sf-chart-gauge', 'sf-chart-heatmap',
    'sf-chart-line', 'sf-chart-map', 'sf-chart-metric', 'sf-chart-parts', 'sf-chart-pie', 'sf-chart-radar', 'sf-chart-sankey',
    'sf-chart-scatter', 'sf-chart-sparkline', 'sf-chart-sunburst', 'sf-chart-timeline', 'sf-chart-treemap', 'sf-chart-waterfall'];
$chartTags = array_keys(array_filter($lock->toArray()['components'], static fn (array $c): bool => ($c['package'] ?? null) === 'chart'));
// The set's shared core (Chart D18) is locked beside the tags: no element, loaded before every chart that uses it.
assert(in_array('sf-chart-core', $chartTags, true));
$core = $lock->component('sf-chart-core');
assert($core['javascript'] === 'chart/smart/chart-core/js/chart-core.min.js' && $core['css'] === null && $core['attributes'] === []);
assert(in_array('sf-chart-core', $lock->component('sf-chart-line')['requires'], true));
$chartTags = array_values(array_diff($chartTags, ['sf-chart-core']));
sort($chartTags);
assert($chartTags === $passport, 'every tag of the SIMAI Chart set is locked, from the chart package');
foreach ($chartTags as $tag) {
    $component = $lock->component($tag);
    $folder = substr($tag, 3);
    assert($component['javascript'] === "chart/smart/{$folder}/js/{$folder}.min.js", $tag);
    assert(str_starts_with((string) $component['css'], "chart/smart/{$folder}/css/"), $tag);
    // Every tag renders bare: it is known and its attributes are its manifest's.
    assert(str_contains(Smart::render($tag, ['label' => 'Chart'])->html, '<' . $tag . ' '), $tag);
}

$line = Smart::render('sf-chart-line', [
    'label' => 'Records created per day',
    'x' => 'day', 'y' => 'count', 'y-zero' => true, 'area' => false, 'x-scale' => 'time',
    'items' => [['day' => '2026-11-01', 'count' => 3], ['day' => '2026-11-02', 'count' => 0]],
    'names' => ['count' => 'Records </script><b>'],
    'colors' => [],
    'hidden-series' => [],
]);
$html = $line->html;
assert(str_starts_with($html, '<sf-chart-line data-larena-smart-runtime="' . $lock->pairId() . '"'));
assert(str_contains($html, 'label="Records created per day"') && str_contains($html, ' y-zero') && !str_contains($html, ' area'));
assert(str_contains($html, 'x-scale="time"'));
assert(!str_contains($html, ' items=') && !str_contains($html, ' names=') && !str_contains($html, ' colors='), 'no list or object attribute');
preg_match('#<script type="application/json" data-sf-settings>(.*?)</script>#', $html, $match);
assert(isset($match[1]), 'the settings island is printed');
assert(substr_count($html, '<script') === 1 && !str_contains($html, '</script><b>'), 'the text cannot close the island');
$settings = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
assert($settings['items'] === [['day' => '2026-11-01', 'count' => 3], ['day' => '2026-11-02', 'count' => 0]]);
assert($settings['names'] === ['count' => 'Records </script><b>'] && $settings['hidden-series'] === []);
assert(str_contains($match[1], '"colors":{}'), 'an empty object stays an object');
assert($line->isSafe());

// The chart's files and those of the empty state it draws, in dependency order.
$keys = array_column(FrontendRuntimeAssetResolver::bundled()->resolve(Smart::assetGraph('sf-chart-line')), 'relative_path', 'asset_key');
assert($keys['simai.framework.sf_chart_line.js'] === 'chart/smart/chart-line/js/chart-line.min.js');
assert($keys['simai.framework.sf_empty_state.js'] === 'ui/distr/smart/empty-state/js/empty-state.min.js');
assert(array_search('simai.framework.sf_empty_state.js', array_keys($keys), true) < array_search('simai.framework.sf_chart_line.js', array_keys($keys), true));

// A list where an object belongs, an object where a list belongs, a scalar for either: refused.
$refusal = static function (array $props): string {
    try {
        Smart::render('sf-chart-line', ['label' => 'x', ...$props]);
    } catch (InvalidArgumentException $error) {
        return $error->getMessage();
    }

    return '';
};
foreach ([['names', ['a', 'b']], ['items', ['a' => 1]], ['items', 'x'], ['colors', 'red']] as [$key, $value]) {
    assert($refusal([$key => $value]) === 'ui_smart_island_setting_invalid:sf-chart-line:' . $key, $key);
}
assert($refusal(['rows' => []]) === 'ui_smart_prop_unknown:sf-chart-line:rows');

// A gauge's bands and a metric without lists.
$gauge = Smart::render('sf-chart-gauge', ['label' => 'Disk', 'bands' => [['to' => 80, 'tone' => 'success']]])->html;
assert(str_contains($gauge, '"bands":[{"to":80,"tone":"success"}]'));
assert(!str_contains(Smart::render('sf-chart-metric', ['label' => 'Records'])->html, 'data-sf-settings'));

echo "Chart smart tests passed.\n";
