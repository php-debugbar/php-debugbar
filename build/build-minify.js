// Build minified debugbar.min.js and debugbar.min.css
import * as esbuild from 'esbuild';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const resourcesDir = path.join(__dirname, '../resources');
const outputDir = path.join(__dirname, '../dist');

// Files to concatenate and minify (order matters!)
const jsFiles = [
    'debugbar.js',
    'widgets.js',
    'openhandler.js',
    'widgets/sqlqueries/widget.js',
    'widgets/templates/widget.js',
    'widgets/mails/widget.js',
    'vendor/highlightjs/highlight.pack.js'
];

const cssFiles = [
    'debugbar.css',
    'icons.css',
    'widgets.css',
    'openhandler.css',
    'highlight.css',
    'widgets/sqlqueries/widget.css',
    'widgets/templates/widget.css',
    'widgets/mails/widget.css'
];

// Minify JavaScript files
async function minifyJS() {
    console.log('Building debugbar.min.js...');

    // Concatenate all JS files
    const jsContent = jsFiles
        .map(file => fs.readFileSync(path.join(resourcesDir, file), 'utf8'))
        .join('\n\n');

    // Write to temp file
    const tempFile = path.join(resourcesDir, 'debugbar.temp.js');
    fs.writeFileSync(tempFile, jsContent);

    try {
        // Minify using esbuild
        await esbuild.build({
            entryPoints: [tempFile],
            outfile: path.join(outputDir, 'debugbar.min.js'),
            minify: true,
            target: 'es2015',
            format: 'iife',
            bundle: false
        });

        console.log('✓ debugbar.min.js created successfully');
    } finally {
        // Clean up temp file
        fs.unlinkSync(tempFile);
    }
}

// Minify CSS files
async function minifyCSS() {
    console.log('Building debugbar.min.css...');

    // Concatenate all CSS files
    const cssContent = cssFiles
        .map(file => fs.readFileSync(path.join(resourcesDir, file), 'utf8'))
        .join('\n\n');

    // Write to temp file
    const tempFile = path.join(resourcesDir, 'debugbar.temp.css');
    fs.writeFileSync(tempFile, cssContent);

    try {
        // Minify using esbuild
        await esbuild.build({
            entryPoints: [tempFile],
            outfile: path.join(outputDir, 'debugbar.min.css'),
            minify: true,
            loader: {
                '.css': 'css'
            }
        });

        console.log('✓ debugbar.min.css created successfully');
    } finally {
        // Clean up temp file
        fs.unlinkSync(tempFile);
    }
}

// Run both builds
async function build() {
    try {
        await minifyJS();
        await minifyCSS();
        console.log('\n✓ All builds completed successfully!');
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
}

build();
