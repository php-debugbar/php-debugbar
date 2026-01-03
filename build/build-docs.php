<?php

use DebugBar\Bridge\Symfony\SymfonyMailCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\TemplateCollector;
use DebugBar\StandardDebugBar;

include __DIR__ . '/../vendor/autoload.php';

$debugbar = new StandardDebugBar();
$debugbar->addCollector(new PdoCollector());
$debugbar->addCollector(new TemplateCollector());
$debugbar->addCollector(new SymfonyMailCollector());

$debugbarRenderer = $debugbar->getJavascriptRenderer()
    ->setBaseUrl('../resources')
    ->setAjaxHandlerEnableTab(true)
    ->setTheme('light');

$debugbarRenderer->setOpenHandlerUrl('open.php');

$debugbar['messages']->addMessage('Hello world', 'success');
$debugbar['messages']->addMessage('Warning: demo version', 'warning');
$debugbar['messages']->addMessage(['demo' => ['is_demo' => true]]);
$debugbar['messages']->addMessage('This does not actually run on the server', 'error');

require __DIR__ . '/../demo/collectors/pdo.php';
require __DIR__ . '/../demo/collectors/symfony_mailer.php';
require __DIR__ . '/../demo/collectors/templates.php';
require __DIR__ . '/../demo/collectors/counter.php';


$content = $debugbarRenderer->getAssets('inline_head');
$content[] = $debugbarRenderer->render();

$generatedScripts = implode("\n", $content);

// Read the main.html template
$templatePath = __DIR__ . '/../docs/overrides/main.html';
$template = file_get_contents($templatePath);

// Replace the scripts block content between specific markers
$startMarker = "<!-- Start Debugbar -->";
$endMarker = "<!-- End Debugbar -->";

// Find the positions
$startPos = strpos($template, $startMarker);
$endPos = strpos($template, $endMarker);

if ($startPos !== false && $endPos !== false) {
    $startPos += strlen($startMarker);

    // Replace the content between markers
    $newTemplate = substr($template, 0, $startPos)
        . "\n" . $generatedScripts . "\n"
        . substr($template, $endPos);

    // Write back to the file
    file_put_contents($templatePath, $newTemplate);

    echo "✓ Updated docs/overrides/main.html with generated debugbar scripts\n";
} else {
    echo "✗ Could not find script markers in main.html\n";
    exit(1);
}

// Copy dist folder to docs/assets/dist
$distSource = __DIR__ . '/../dist';
$distDest = __DIR__ . '/../docs/assets/dist';

if (!is_dir($distSource)) {
    echo "✗ dist folder not found at $distSource\n";
    exit(1);
}

// Create docs/assets directory if it doesn't exist
if (!is_dir(__DIR__ . '/../docs/assets')) {
    mkdir(__DIR__ . '/../docs/assets', 0755, true);
}

// Remove existing dist folder if it exists
if (is_dir($distDest)) {
    deleteDirectory($distDest);
}

// Copy dist folder
copyDirectory($distSource, $distDest);

echo "✓ Copied dist folder to docs/assets/dist\n";

function copyDirectory($source, $dest) {
    mkdir($dest, 0755, true);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $destPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
        if ($item->isDir()) {
            mkdir($destPath, 0755, true);
        } else {
            copy($item, $destPath);
        }
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item);
        } else {
            unlink($item);
        }
    }

    rmdir($dir);
}