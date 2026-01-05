// Build minified debugbar.min.js and debugbar.min.css
import * as esbuild from 'esbuild';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const distDir = path.join(__dirname, '../dist');

// Minify JavaScript files
async function minifyJS() {
    console.log('Building debugbar.min.js...');

    const tempFile = path.join(distDir, 'debugbar.js');

    try {
        // Minify using esbuild
        await esbuild.build({
            entryPoints: [path.join(distDir, 'debugbar.js')],
            outfile: path.join(distDir, 'debugbar.min.js'),
            minify: true,
            minifyIdentifiers: false,
            keepNames: true,
            treeShaking: false,
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

    const tempFile = path.join(distDir, 'debugbar.css');

    try {
        // Minify using esbuild
        await esbuild.build({
            entryPoints: [tempFile],
            outfile: path.join(distDir, 'debugbar.min.css'),
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
