<?php

$path = __DIR__ . '/../resources/vendor/font-awesome/css/font-awesome.min.css';
$contents = file_get_contents($path);

if (strpos($contents, 'PhpDebugbarFontAwesome') !== false ){
    exit('Already namespaced');
}

// namespace FontAwesome occurrences
$contents = str_replace(['FontAwesome', 'fa-', '.fa'], ['PhpDebugbarFontAwesome', 'phpdebugbar-fa-', '.phpdebugbar-fa'], $contents);

if (file_put_contents($path, $contents)) {
    echo "Updated font-awesome.min.css";
} else {
    echo "No content written";
}


