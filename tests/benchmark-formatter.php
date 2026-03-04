<?php

/**
 * Benchmark: HtmlDataFormatter vs JsonDataFormatter
 *
 * Compares formatting speed, JSON transport size, and asset overhead.
 *
 * Usage: php tests/benchmark-formatter.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DebugBar\DataFormatter\HtmlDataFormatter;
use DebugBar\DataFormatter\JsonDataFormatter;

// ── Test data ────────────────────────────────────────────────────────

function buildTestData(): array
{
    // 1. Simple scalar
    $scalar = 42;

    // 2. Short string
    $shortString = 'Hello, world!';

    // 3. Small flat array
    $smallArray = ['foo' => 'bar', 'baz' => 123, 'qux' => true];

    // 4. Nested array (3 levels)
    $nestedArray = [];
    for ($i = 0; $i < 10; $i++) {
        $nestedArray["key_$i"] = [
            'name' => "item_$i",
            'value' => $i * 3.14,
            'tags' => ["tag_a_$i", "tag_b_$i"],
            'meta' => ['created' => '2024-01-01', 'active' => $i % 2 === 0],
        ];
    }

    // 5. Object with visibility
    $object = new class {
        public string $name = 'TestObject';
        public int $id = 42;
        protected array $config = ['debug' => true, 'level' => 3];
        private string $secret = 'hidden_value';
        public array $items = ['alpha', 'beta', 'gamma', 'delta', 'epsilon'];
    };

    // 6. Large flat array (100 items)
    $largeArray = [];
    for ($i = 0; $i < 100; $i++) {
        $largeArray["item_$i"] = "value_$i";
    }

    // 7. Deep nesting (5 levels)
    $deepNesting = ['level1' => ['level2' => ['level3' => ['level4' => ['level5' => 'deep_value']]]]];

    // 8. Mixed complex structure
    $complex = [
        'users' => [
            ['id' => 1, 'name' => 'Alice', 'roles' => ['admin', 'user'], 'settings' => ['theme' => 'dark', 'lang' => 'en']],
            ['id' => 2, 'name' => 'Bob', 'roles' => ['user'], 'settings' => ['theme' => 'light', 'lang' => 'fr']],
            ['id' => 3, 'name' => 'Charlie', 'roles' => ['moderator', 'user'], 'settings' => ['theme' => 'dark', 'lang' => 'de']],
        ],
        'config' => ['app_name' => 'MyApp', 'version' => '2.1.0', 'features' => ['logging' => true, 'cache' => true, 'debug' => false]],
        'stats' => ['total_users' => 1500, 'active_today' => 342, 'memory_usage' => 67108864],
    ];

    return [
        'scalar'       => $scalar,
        'short_string' => $shortString,
        'small_array'  => $smallArray,
        'nested_array' => $nestedArray,
        'object'       => $object,
        'large_array'  => $largeArray,
        'deep_nesting' => $deepNesting,
        'complex'      => $complex,
    ];
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

// ── Main ─────────────────────────────────────────────────────────────

$testData = buildTestData();
$iterations = 500;

$htmlFormatter = new HtmlDataFormatter();
$jsonFormatter = new JsonDataFormatter();

$separator = str_repeat('─', 100);
$headerFmt = "%-15s │ %12s │ %12s │ %8s │ %12s │ %12s │ %8s";
$rowFmt    = "%-15s │ %12s │ %12s │ %7s%% │ %12s │ %12s │ %7s%%";

echo "Benchmark: HtmlDataFormatter vs JsonDataFormatter\n";
echo "Iterations per test: $iterations\n";
echo "$separator\n\n";

// ── 1. Formatting speed ──────────────────────────────────────────────

echo "1. FORMATTING SPEED (formatVar)\n";
echo "$separator\n";
printf("$headerFmt\n", 'Test Case', 'HTML median', 'JSON median', 'Diff', 'HTML mean', 'JSON mean', 'Diff');
echo "$separator\n";

$totalHtmlFormat = 0;
$totalJsonFormat = 0;

foreach ($testData as $name => $data) {
    $htmlBench = benchmark(fn() => $htmlFormatter->formatVar($data), $iterations);
    $jsonBench = benchmark(fn() => $jsonFormatter->formatVar($data), $iterations);

    $medianDiff = (($jsonBench['median_ns'] - $htmlBench['median_ns']) / $htmlBench['median_ns']) * 100;
    $meanDiff = (($jsonBench['mean_ns'] - $htmlBench['mean_ns']) / $htmlBench['mean_ns']) * 100;

    $totalHtmlFormat += $htmlBench['mean_ns'];
    $totalJsonFormat += $jsonBench['mean_ns'];

    printf(
        "$rowFmt\n",
        $name,
        formatNs($htmlBench['median_ns']),
        formatNs($jsonBench['median_ns']),
        sprintf('%+.0f', $medianDiff),
        formatNs($htmlBench['mean_ns']),
        formatNs($jsonBench['mean_ns']),
        sprintf('%+.0f', $meanDiff),
    );
}

$totalDiff = (($totalJsonFormat - $totalHtmlFormat) / $totalHtmlFormat) * 100;
echo "$separator\n";
printf(
    "$rowFmt\n",
    'TOTAL',
    '',
    '',
    '',
    formatNs($totalHtmlFormat),
    formatNs($totalJsonFormat),
    sprintf('%+.0f', $totalDiff),
);
echo "\n";

// ── 2. JSON transport size ──────────────────────────────────────────

echo "2. JSON TRANSPORT SIZE (json_encode of formatVar result)\n";
echo "   HTML: string containing HTML markup → JSON-escaped\n";
echo "   JSON: array/scalar structure → JSON-encoded natively\n";
echo "$separator\n";
$sizeFmt = "%-15s │ %12s │ %12s │ %8s";
printf("$sizeFmt\n", 'Test Case', 'HTML encoded', 'JSON encoded', 'Diff');
echo "$separator\n";

$totalHtmlTransport = 0;
$totalJsonTransport = 0;

foreach ($testData as $name => $data) {
    $htmlOut = $htmlFormatter->formatVar($data);
    $jsonOut = $jsonFormatter->formatVar($data);

    $htmlEncoded = strlen(json_encode($htmlOut));
    $jsonEncoded = strlen(json_encode($jsonOut));
    $diff = (($jsonEncoded - $htmlEncoded) / $htmlEncoded) * 100;

    $totalHtmlTransport += $htmlEncoded;
    $totalJsonTransport += $jsonEncoded;

    printf(
        "$sizeFmt\n",
        $name,
        formatBytes($htmlEncoded),
        formatBytes($jsonEncoded),
        sprintf('%+.0f%%', $diff),
    );
}

$totalTransportDiff = (($totalJsonTransport - $totalHtmlTransport) / $totalHtmlTransport) * 100;
echo "$separator\n";
printf("$sizeFmt\n", 'TOTAL', formatBytes($totalHtmlTransport), formatBytes($totalJsonTransport), sprintf('%+.0f%%', $totalTransportDiff));
echo "\n";

// ── 3. Batch simulation ─────────────────────────────────────────────

echo "3. BATCH SIMULATION (typical page: format all test cases + json_encode everything)\n";
echo "$separator\n";

$batchIterations = 200;

$htmlBatch = benchmark(function () use ($htmlFormatter, $testData) {
    $collected = [];
    foreach ($testData as $name => $data) {
        $collected[$name] = $htmlFormatter->formatVar($data);
    }
    json_encode($collected);
}, $batchIterations);

$jsonBatch = benchmark(function () use ($jsonFormatter, $testData) {
    $collected = [];
    foreach ($testData as $name => $data) {
        $collected[$name] = $jsonFormatter->formatVar($data);
    }
    json_encode($collected);
}, $batchIterations);

$batchDiff = (($jsonBatch['median_ns'] - $htmlBatch['median_ns']) / $htmlBatch['median_ns']) * 100;

printf("HTML batch median: %s\n", formatNs($htmlBatch['median_ns']));
printf("JSON batch median: %s\n", formatNs($jsonBatch['median_ns']));
printf("Difference:        %+.0f%%\n", $batchDiff);
echo "\n";

// Batch output sizes
$htmlCollected = [];
$jsonCollected = [];
foreach ($testData as $name => $data) {
    $htmlCollected[$name] = $htmlFormatter->formatVar($data);
    $jsonCollected[$name] = $jsonFormatter->formatVar($data);
}
$htmlPayload = json_encode($htmlCollected);
$jsonPayload = json_encode($jsonCollected);
$payloadDiff = ((strlen($jsonPayload) - strlen($htmlPayload)) / strlen($htmlPayload)) * 100;

printf("HTML payload size:  %s\n", formatBytes(strlen($htmlPayload)));
printf("JSON payload size:  %s\n", formatBytes(strlen($jsonPayload)));
printf("Difference:         %+.0f%%\n", $payloadDiff);
echo "\n";

// ── 4. Symfony asset overhead ────────────────────────────────────────

echo "4. ASSET OVERHEAD\n";
echo "$separator\n";

$htmlAssets = $htmlFormatter->getAssets();
$jsonAssets = $jsonFormatter->getAssets();

// Both formatters now use inline_head for Sfdump JS+CSS
if (isset($htmlAssets['inline_head']['html_var_dumper'])) {
    $htmlInlineSize = strlen($htmlAssets['inline_head']['html_var_dumper']);
    printf("HTML inline_head (Sfdump JS+CSS): %s\n", formatBytes($htmlInlineSize));
}

if (isset($jsonAssets['inline_head']['html_var_dumper'])) {
    $jsonInlineSize = strlen($jsonAssets['inline_head']['html_var_dumper']);
    printf("JSON inline_head (Sfdump JS+CSS): %s\n", formatBytes($jsonInlineSize));
    echo "  → Same Sfdump bundle, deduplicated by shared 'html_var_dumper' key\n";
}

// JSON widget.js (client-side renderer)
$jsonBasePath = $jsonAssets['base_path'] ?? '';
$jsFile = $jsonBasePath . '/' . ($jsonAssets['js'] ?? '');
$jsSize = file_exists($jsFile) ? filesize($jsFile) : 0;
printf("JSON widget.js:                   %s\n", formatBytes($jsSize));
echo "  → Static file, cached by the browser\n";

echo "\n$separator\n";
echo "Done.\n";
