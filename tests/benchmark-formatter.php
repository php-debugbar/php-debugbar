<?php

/**
 * Benchmark: CliDumper (baseline) vs HtmlDataFormatter vs JsonDataFormatter
 *
 * Compares formatting speed, JSON transport size, and asset overhead.
 * CliDumper (VarCloner + CliDumper) serves as the Symfony baseline.
 *
 * Usage: php tests/benchmark-formatter.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DebugBar\DataFormatter\HtmlDataFormatter;
use DebugBar\DataFormatter\JsonDataFormatter;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

// ── Test data ────────────────────────────────────────────────────────

/**
 * Build a tree of indexed arrays: each node has $width children, nested $depth levels deep.
 */
function buildIndexedTree(int $depth, int $width): array
{
    if ($depth <= 1) {
        return array_fill(0, $width, 'leaf');
    }
    $arr = [];
    for ($i = 0; $i < $width; $i++) {
        $arr[] = buildIndexedTree($depth - 1, $width);
    }
    return $arr;
}

/**
 * Build a tree of objects with public properties, nested $depth levels deep.
 */
function buildObjectTree(int $depth, int $width): object
{
    $obj = new \stdClass();
    for ($i = 0; $i < $width; $i++) {
        $prop = "prop_$i";
        $obj->$prop = "val_{$depth}_{$i}";
    }
    if ($depth > 1) {
        $obj->child = buildObjectTree($depth - 1, $width);
    }
    return $obj;
}

function buildTestData(): array
{
    $smallArray = ['foo' => 'bar', 'baz' => 123, 'qux' => true];

    $nestedArray = [];
    for ($i = 0; $i < 10; $i++) {
        $nestedArray["key_$i"] = [
            'name' => "item_$i",
            'value' => $i * 3.14,
            'tags' => ["tag_a_$i", "tag_b_$i"],
            'meta' => ['created' => '2024-01-01', 'active' => $i % 2 === 0],
        ];
    }

    $object = new class {
        public string $name = 'TestObject';
        public int $id = 42;
        protected array $config = ['debug' => true, 'level' => 3];
        /** @phpstan-ignore property.onlyWritten */
        private string $secret = 'hidden_value';
        public array $items = ['alpha', 'beta', 'gamma', 'delta', 'epsilon'];
    };

    $largeArray = [];
    for ($i = 0; $i < 100; $i++) {
        $largeArray["item_$i"] = "value_$i";
    }

    $deepNesting = ['level1' => ['level2' => ['level3' => ['level4' => ['level5' => 'deep_value']]]]];

    $complex = [
        'users' => [
            ['id' => 1, 'name' => 'Alice', 'roles' => ['admin', 'user'], 'settings' => ['theme' => 'dark', 'lang' => 'en']],
            ['id' => 2, 'name' => 'Bob', 'roles' => ['user'], 'settings' => ['theme' => 'light', 'lang' => 'fr']],
            ['id' => 3, 'name' => 'Charlie', 'roles' => ['moderator', 'user'], 'settings' => ['theme' => 'dark', 'lang' => 'de']],
        ],
        'config' => ['app_name' => 'MyApp', 'version' => '2.1.0', 'features' => ['logging' => true, 'cache' => true, 'debug' => false]],
        'stats' => ['total_users' => 1500, 'active_today' => 342, 'memory_usage' => 67108864],
    ];

    $result = [
        'small_array'  => $smallArray,
        'nested_array' => $nestedArray,
        'object'       => $object,
        'large_array'  => $largeArray,
        'deep_nesting' => $deepNesting,
        'complex'      => $complex,
    ];

    for ($d = 2; $d <= 6; $d++) {
        $result["idx_depth$d"] = buildIndexedTree($d, 3);
    }

    // Object tree
    $result['obj_depth4'] = buildObjectTree(4, 3);

    // Mixed: plain array with object at level 1
    $obj = new \stdClass();
    $obj->name = 'Alice';
    $obj->age = 30;
    $result['mixed_obj_l1'] = ['name' => 'test', 'count' => 3, 'user' => $obj, 'tags' => ['a', 'b']];

    // Mixed: plain array with object at level 2
    $result['mixed_obj_l2'] = [
        'data' => ['user' => $obj, 'status' => 'active'],
        'meta' => ['version' => 1],
    ];

    // Mixed: large plain array with object at the end
    $mixed = [];
    for ($i = 0; $i < 50; $i++) {
        $mixed["item_$i"] = "value_$i";
    }
    $mixed['obj'] = $obj;
    $result['mixed_obj_end'] = $mixed;

    return $result;
}

// ── Benchmark runner ─────────────────────────────────────────────────

function benchmark(callable $fn, int $iterations): array
{
    // Warmup
    for ($i = 0; $i < min(10, $iterations); $i++) {
        $fn();
    }

    $times = [];
    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $fn();
        $times[] = hrtime(true) - $start;
    }

    sort($times);
    $count = count($times);

    return [
        'median_ns' => $times[(int) ($count / 2)],
        'mean_ns'   => array_sum($times) / $count,
        'min_ns'    => $times[0],
        'max_ns'    => $times[$count - 1],
        'p95_ns'    => $times[(int) ($count * 0.95)],
    ];
}

function formatNs(float $ns): string
{
    if ($ns < 1000) {
        return round($ns) . ' ns';
    }
    if ($ns < 1_000_000) {
        return round($ns / 1000, 1) . ' µs';
    }
    return round($ns / 1_000_000, 2) . ' ms';
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 2) . ' MB';
}

function diffPercent(float $value, float $base): string
{
    return sprintf('%+.0f%%', (($value - $base) / $base) * 100);
}

// ── Main ─────────────────────────────────────────────────────────────

$testData = buildTestData();
$iterations = 500;
$maxDepth = 6;

$htmlFormatter = new HtmlDataFormatter();
$htmlFormatter->mergeClonerOptions(['max_depth' => $maxDepth]);
$htmlFormatter->mergeDumperOptions(['expanded_depth' => 0]);

$jsonFormatter = new JsonDataFormatter();
$jsonFormatter->mergeClonerOptions(['max_depth' => $maxDepth]);

$cloner = new VarCloner();
$cliDumper = new CliDumper();
$cliDumper->setColors(false);

$separator = str_repeat('─', 100);

echo "Benchmark: CliDumper (baseline) vs HtmlDataFormatter vs JsonDataFormatter\n";
echo "Iterations per test: $iterations\n";
echo "$separator\n\n";

// ── 1. Formatting speed ──────────────────────────────────────────────

echo "1. FORMATTING SPEED\n";
echo "   CLI:  VarCloner + CliDumper (Symfony baseline)\n";
echo "   HTML: HtmlDataFormatter (server-rendered HTML)\n";
echo "   JSON: JsonDataFormatter (client-rendered, plain arrays + dump nodes)\n";
echo "$separator\n";

$headerFmt = "%-15s │ %12s │ %12s %8s │ %12s %8s";
$rowFmt    = "%-15s │ %12s │ %12s %8s │ %12s %8s";

printf("$headerFmt\n", 'Test Case', 'CLI', 'HTML', 'vs CLI', 'JSON', 'vs CLI');
echo "$separator\n";

$totalCli = 0;
$totalHtml = 0;
$totalJson = 0;

foreach ($testData as $name => $data) {
    $cliBench = benchmark(function () use ($cloner, $cliDumper, $data, $maxDepth): void {
        $cliDumper->dump($cloner->cloneVar($data)->withMaxDepth($maxDepth), true);
    }, $iterations);
    $htmlBench = benchmark(fn() => $htmlFormatter->formatVar($data), $iterations);
    $jsonBench = benchmark(fn() => $jsonFormatter->formatVar($data), $iterations);

    $totalCli += $cliBench['mean_ns'];
    $totalHtml += $htmlBench['mean_ns'];
    $totalJson += $jsonBench['mean_ns'];

    printf(
        "$rowFmt\n",
        $name,
        formatNs($cliBench['median_ns']),
        formatNs($htmlBench['median_ns']),
        diffPercent($htmlBench['median_ns'], $cliBench['median_ns']),
        formatNs($jsonBench['median_ns']),
        diffPercent($jsonBench['median_ns'], $cliBench['median_ns']),
    );
}

echo "$separator\n";
printf(
    "$rowFmt\n",
    'TOTAL (mean)',
    formatNs($totalCli),
    formatNs($totalHtml),
    diffPercent($totalHtml, $totalCli),
    formatNs($totalJson),
    diffPercent($totalJson, $totalCli),
);
echo "\n";

// ── 2. JSON transport size ──────────────────────────────────────────

echo "2. TRANSPORT SIZE (json_encode of formatted result)\n";
echo "   All formatters produce output that gets json_encode'd for transport.\n";
echo "$separator\n";
printf("$headerFmt\n", 'Test Case', 'CLI', 'HTML', 'vs CLI', 'JSON', 'vs CLI');
echo "$separator\n";

$totalCliSize = 0;
$totalHtmlSize = 0;
$totalJsonSize = 0;

foreach ($testData as $name => $data) {
    $cliSize = strlen(json_encode($cliDumper->dump($cloner->cloneVar($data)->withMaxDepth($maxDepth), true)));
    $htmlSize = strlen(json_encode($htmlFormatter->formatVar($data)));
    $jsonSize = strlen(json_encode($jsonFormatter->formatVar($data)));

    $totalCliSize += $cliSize;
    $totalHtmlSize += $htmlSize;
    $totalJsonSize += $jsonSize;

    printf(
        "$rowFmt\n",
        $name,
        formatBytes($cliSize),
        formatBytes($htmlSize),
        diffPercent($htmlSize, $cliSize),
        formatBytes($jsonSize),
        diffPercent($jsonSize, $cliSize),
    );
}

echo "$separator\n";
printf(
    "$rowFmt\n",
    'TOTAL',
    formatBytes($totalCliSize),
    formatBytes($totalHtmlSize),
    diffPercent($totalHtmlSize, $totalCliSize),
    formatBytes($totalJsonSize),
    diffPercent($totalJsonSize, $totalCliSize),
);
echo "\n";

// ── 3. Batch simulation ─────────────────────────────────────────────

echo "3. BATCH SIMULATION (typical page: format all test cases + json_encode)\n";
echo "$separator\n";

$batchIterations = 200;

$cliBatch = benchmark(static function () use ($cloner, $cliDumper, $testData, $maxDepth): void {
    $collected = [];
    foreach ($testData as $name => $data) {
        $collected[$name] = $cliDumper->dump($cloner->cloneVar($data)->withMaxDepth($maxDepth), true);
    }
    json_encode($collected);
}, $batchIterations);

$htmlBatch = benchmark(static function () use ($htmlFormatter, $testData): void {
    $collected = [];
    foreach ($testData as $name => $data) {
        $collected[$name] = $htmlFormatter->formatVar($data);
    }
    json_encode($collected);
}, $batchIterations);

$jsonBatch = benchmark(static function () use ($jsonFormatter, $testData): void {
    $collected = [];
    foreach ($testData as $name => $data) {
        $collected[$name] = $jsonFormatter->formatVar($data);
    }
    json_encode($collected);
}, $batchIterations);

$batchFmt = "%-20s  %12s  %8s";
printf("$batchFmt\n", 'Formatter', 'Median', 'vs CLI');
echo "$separator\n";
printf("$batchFmt\n", 'CLI (baseline)', formatNs($cliBatch['median_ns']), '—');
printf("$batchFmt\n", 'HTML', formatNs($htmlBatch['median_ns']), diffPercent($htmlBatch['median_ns'], $cliBatch['median_ns']));
printf("$batchFmt\n", 'JSON', formatNs($jsonBatch['median_ns']), diffPercent($jsonBatch['median_ns'], $cliBatch['median_ns']));
echo "\n";

// Batch payload sizes
$cliCollected = [];
$htmlCollected = [];
$jsonCollected = [];
foreach ($testData as $name => $data) {
    $cliCollected[$name] = $cliDumper->dump($cloner->cloneVar($data)->withMaxDepth($maxDepth), true);
    $htmlCollected[$name] = $htmlFormatter->formatVar($data);
    $jsonCollected[$name] = $jsonFormatter->formatVar($data);
}
$cliPayload = json_encode($cliCollected);
$htmlPayload = json_encode($htmlCollected);
$jsonPayload = json_encode($jsonCollected);

printf("$batchFmt\n", 'Payload size', '', 'vs CLI');
echo "$separator\n";
printf("$batchFmt\n", 'CLI (baseline)', formatBytes(strlen($cliPayload)), '—');
printf("$batchFmt\n", 'HTML', formatBytes(strlen($htmlPayload)), diffPercent(strlen($htmlPayload), strlen($cliPayload)));
printf("$batchFmt\n", 'JSON', formatBytes(strlen($jsonPayload)), diffPercent(strlen($jsonPayload), strlen($cliPayload)));
echo "\n";

// ── 4. Asset overhead ────────────────────────────────────────────────

echo "4. ASSET OVERHEAD\n";
echo "$separator\n";

$htmlAssets = $htmlFormatter->getAssets();
$jsonAssets = $jsonFormatter->getAssets();

echo "CLI:  no assets (text only, no browser rendering)\n";

if (isset($htmlAssets['inline_css']['html_var_dumper'])) {
    printf("HTML: inline_css (Sfdump CSS):    %s  (inline, sent every page)\n", formatBytes(strlen($htmlAssets['inline_css']['html_var_dumper'])));
}
if (isset($htmlAssets['inline_js']['html_var_dumper'])) {
    printf("HTML: inline_js (Sfdump JS):      %s  (inline, sent every page)\n", formatBytes(strlen($htmlAssets['inline_js']['html_var_dumper'])));
}

$cssFile = __DIR__ . '/../resources/' . ($jsonAssets['css'] ?? 'vardumper.css');
$jsFile = __DIR__ . '/../resources/' . ($jsonAssets['js'] ?? 'vardumper.js');
printf("JSON: vardumper.css:               %s  (static, cached by browser)\n", file_exists($cssFile) ? formatBytes(filesize($cssFile)) : 'n/a');
printf("JSON: vardumper.js:                %s  (static, cached by browser)\n", file_exists($jsFile) ? formatBytes(filesize($jsFile)) : 'n/a');

echo "\n$separator\n";
echo "Done.\n";
