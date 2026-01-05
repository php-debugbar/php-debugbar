<?php

declare(strict_types=1);

/** @var \DebugBar\DebugBar $debugbar */

use DebugBar\DataCollector\HttpCollector;

/** @var HttpCollector $templateCollector */
$httpCollector = $debugbar['http'];

$data = json_decode(<<<JSON
{"downloads":{"total":14881281,"monthly":1559760,"daily":86909},"versions":["3.x-dev","dev-master","v3.0-beta.15","v3.0-beta.14","v3.0-beta.13","v3.0-beta.12","v3.0-beta.11","v3.0-beta.10","v3.0-beta.9","v3.0-beta.8","v3.0-beta.7","v3.0-beta.6","v3.0-beta.5","v3.0-beta.4","v3.0-beta.3","v3.0-beta.2","v3.0-beta.1","2.x-dev","v2.2.6","v2.2.5","v2.2.4","v2.2.3","v2.2.2","v2.2.1","v2.2.0","v2.1.6","v2.1.5","v2.1.4","v2.1.3","v2.1.2","v2.1.1","v2.1.0","v2.0.1","v2.0.0","v2.0.0-beta2","v2.0.0-beta1","1.x-dev","v1.23.6","v1.23.5","v1.23.4","v1.23.3","v1.23.2","v1.23.1","v1.23.0","v1.22.6","v1.22.5","v1.22.4","v1.22.3","v1.22.2","v1.22.1","v1.22.0","v1.21.3","v1.21.2","v1.21.1","v1.21.0","v1.20.2","v1.20.1","v1.20.0","v1.19.1","v1.19.0","v1.18.2","v1.18.1","v1.18.0","v1.17.3","v1.17.2","v1.17.1","v1.17.0","v1.16.5","v1.16.4","v1.16.3","v1.16.2","v1.16.1","v1.16.0","v1.15.1","v1.15.0","v1.14.1","v1.14.0","1.13.1","v1.13.0","v1.12.0","v1.11.1","v1.11.0","v1.10.5","v1.10.4","v1.10.3","v1.10.2","1.10.1","1.10.0","1.9.15","1.9.14","1.9.13","1.9.12","1.9.11","1.9.10","1.9.9","1.9.8","1.9.7","1.9.6","1.9.5","1.9.4","1.9.3","1.9.2","1.9.1","1.9","1.8","1.7.1","1.7","1.6.1","1.6","1.5.1","1.5","1.4","1.3","1.2","1.1","1.0.4","1.0.3","1.0.2","1.0.1","1.0","dev-feat-http-widget","dev-safer-vardumper","dev-feat-icons","dev-tweak-test-dir","dev-feat-move-autoload","dev-faet-globalthis","dev-feat-relax-vardumper","dev-test-windows","dev-feat-tweak-minify","dev-tweak-assets","dev-fix-symfony-statuscode","dev-feat-sfdump-params","dev-feat-template-vardumper","dev-mask-values","dev-mask-public","dev-feat-symfony-http-driver","dev-feat-symfony-integration","dev-fix-resize-handle","dev-feat-release-notes","dev-feat-default-colors","dev-fix-btn-resize","dev-feat-top-bar","dev-fix-settings","dev-fix-render-docs","dev-feat-assethandler","dev-fix-file-check","dev-docs-v3","dev-padding-inset","dev-feat-garbage-collect","dev-feat-mask","dev-webmailcontatos-master","dev-extend-sql-widget","dev-feat-pdo-sort","dev-feat-pdo-backtrace","dev-feat-format-sql","dev-feat-params","dev-feat-hidden","dev-feat-queryformat-bindings","dev-fix-exceptions-trace","dev-feat-vardumper-version","dev-update-docs","dev-feat-docs","dev-feat-hightlight","dev-feat-workflows","dev-fix-options","dev-feat-csp","dev-optimize-equal","dev-feat-rebuild","dev-feat-optimize-render","dev-fix-exception-dump","dev-tweak-backtrace-lmit","dev-fix-vardumper","dev-fix-lint","dev-fix-autoshow","dev-v3-beta","dev-remove-docs","dev-feat-openhandler-actionable","dev-copilot\/sub-pr-670","dev-3.x-dump-dist-assets","dev-3.x-phpstan-level6","dev-3.x-phpstan-level5","dev-3.x-actions","dev-3x-no-serialize","dev-3.x-demo","dev-3.x-strict-types","dev-3.x-per-cs","dev-3.x-analyse","dev-3x-minify-tweak","dev-3.x-tweak-minify","dev-3.x-classes","dev-3.x-nativejs","dev-3.x-templates","dev-3.x-message-icons","dev-3.x-deprecated","dev-3.x-assets","dev-3.x-sprites","dev-feat-widget-icons","dev-tweak-icons","dev-3.x-fetch","dev-feat-build-vendor","dev-feat-eslint","dev-feat-es6","dev-deprecate-doctrine-bridge","dev-test-wait-for-visibility","dev-panther-nodevtools","dev-dark-dumper","dev-barryvdh-patch-4","dev-feat-tab-text-size","dev-feat-ulid","dev-barryvdh-patch-3","dev-sort-widgets","dev-fix-badges","dev-fix-empty-time","dev-feat-group-timeline","dev-feat-link-timeline","dev-fix-openhandler-theme","dev-barryvdh-patch-2","dev-feat-link-indicator","dev-feat-fix-links","dev-Rename-to-php-debugbar","dev-feat-rename-bump-package","dev-dev-keyword","dev-feat-fix-lineheight","dev-feat-hljs","dev-revert-dragging","dev-feat-offset-20","dev-barryvdh-patch-1","dev-feat-dragicon","dev-remove-trace-arg-objects","dev-feat-extended-tooltips","dev-feat-check-copy-result","dev-feat-hide-empty-collectors","dev-feat-subtle-copy","dev-escape-doctrine","dev-feat\/fa-icons","dev-feat-dataset-combine","dev-test-integration","dev-feat-dataset-tab","dev-feat-fetch-only","dev-fix-mail-html","dev-fix-body-check","dev-feat-mailbody","dev-revert-606-patch-32","dev-feat-xdebuglink-varlist","dev-feat-jquery4-deprecations","dev-fix\/interpolate","dev-fix\/vardumper","dev-feat-servertiming","dev-fix-unsafe-headers","dev-reset","dev-feat-dynamic-padding","dev-dev_rebased","dev-util","dev-timedate-memory","dev-dev","dev-serverhandler"],"average":"weekly","date":"2025-01-26"}
JSON);
$httpCollector->addRequest(
    'GET',
    'https://packagist.org/packages/php-debugbar/php-debugbar/stats.json',
    200,
    0.684,
    [
        'response' => $data,
        'headers' => [
            'Data' => 'Mon, 05 Jan 2026 21:07:36 GMT',
            'Content-Type' => 'application/json'
        ]
    ]
);

