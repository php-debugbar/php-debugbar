#!/usr/bin/env node
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const path = join(__dirname, '../resources/vendor/font-awesome/css/font-awesome.min.css');
let contents = readFileSync(path, 'utf8');

if (contents.includes('PhpDebugbarFontAwesome')) {
    console.log('Already namespaced');
    process.exit(0);
}

// namespace FontAwesome occurrences
contents = contents
    .replace(/FontAwesome/g, 'PhpDebugbarFontAwesome')
    .replace(/fa-/g, 'phpdebugbar-fa-')
    .replace(/\.fa\b/g, '.phpdebugbar-fa');

writeFileSync(path, contents, 'utf8');
console.log('Updated font-awesome.min.css');
