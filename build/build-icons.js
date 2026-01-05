import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Tabler icons to include
const icons = [
    // Data collector icons
    'adjustments',
    'adjustments-horizontal',
    'arrow-right',
    'arrows-left-right',
    'bolt',
    'bookmark',
    'box',
    'briefcase',
    'bug',
    'calendar',
    'chart-infographic',
    'clock',
    'code',
    'database',
    'file-code',
    'flag',
    'history',
    'inbox',
    'leaf',
    'list',
    'logs',
    'mobiledata',
    'search',
    'server-cog',
    'share-3',
    'tags',
    'x',

    // UI control icons
    'chevron-down',
    'chevron-up',
    'folder-open',
    'brand-php',

    // Widget-specific icons
    'cpu',           // memory/performance
    'table',         // row count
    'link',          // statement ID
    'copy',          // copy to clipboard
    'circle-check',  // copy success confirmation
    'external-link', // editor link
];

const svgDir = path.join(__dirname, '../node_modules/@tabler/icons/icons/outline');
const outputFile = path.join(__dirname, '../resources/icons.css');
const defaultStrokeWidth = 2; // Tabler default stroke width
const brandStrokeWidth = 1; // For brands, use 1

function svgToDataUri(svgContent, strokeWidth) {
    // Remove XML comments
    svgContent = svgContent.replace(/<!--[\s\S]*?-->/g, '');

    // Ensure consistent stroke-width
    svgContent = svgContent.replace(/stroke-width="[^"]*"/g, `stroke-width="${strokeWidth}"`);

    // Remove unnecessary attributes for mask usage (but not stroke-width!)
    svgContent = svgContent.replace(/\s+class="[^"]*"/g, '');
    svgContent = svgContent.replace(/\s+width="[^"]*"/g, '');
    svgContent = svgContent.replace(/\s+height="[^"]*"/g, '');

    // Minify: remove newlines and extra spaces
    svgContent = svgContent.replace(/\s+/g, ' ').trim();

    // URL encode for data URI
    const encoded = encodeURIComponent(svgContent)
        .replace(/'/g, '%27')
        .replace(/"/g, '%22');
    return `data:image/svg+xml,${encoded}`;
}

function generateIconsCSS() {
    let css = `/* Generated file - do not edit manually */\n/* Generated from Tabler Icons */\n\n`;

    // First, define all CSS variables with the SVG data URIs
    css += `:root {\n`;
    for (const icon of icons) {
        const svgPath = path.join(svgDir, `${icon}.svg`);

        if (!fs.existsSync(svgPath)) {
            console.warn(`Warning: SVG file not found for icon "${icon}" at ${svgPath}`);
            continue;
        }

        const svgContent = fs.readFileSync(svgPath, 'utf8');
        let strokeWidth = icon.indexOf('brand-') === 0 ? brandStrokeWidth : defaultStrokeWidth
        const dataUri = svgToDataUri(svgContent, strokeWidth);

        css += `  --debugbar-icon-${icon}: url('${dataUri}');\n`;
    }
    css += `}\n\n`;

    // Then, apply the variables to the icon classes
    for (const icon of icons) {
        const svgPath = path.join(svgDir, `${icon}.svg`);

        if (!fs.existsSync(svgPath)) {
            continue;
        }

        css += `.phpdebugbar-icon-${icon}::before {\n`;
        css += `  -webkit-mask-image: var(--debugbar-icon-${icon});\n`;
        css += `  mask-image: var(--debugbar-icon-${icon});\n`;
        css += `}\n\n`;
    }

    fs.writeFileSync(outputFile, css, 'utf8');
    console.log(`✓ Generated ${outputFile} with ${icons.length} icons`);
}

try {
    generateIconsCSS();
} catch (error) {
    console.error('Error generating icons:', error);
    process.exit(1);
}
