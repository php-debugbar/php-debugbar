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
    'flag',
    'history',
    'inbox',
    'leaf',
    'list',
    'logs',
    'search',
    'server-cog',
    'tags',
    'x',

    // UI control icons
    'chevron-down',
    'chevron-up',
    'folder-open',
    'brand-php',
];

const svgDir = path.join(__dirname, '../node_modules/@tabler/icons/icons/outline');
const outputFile = path.join(__dirname, '../resources/icons.css');
const strokeWidth = 2; // Tabler default stroke width

function svgToDataUri(svgContent) {
    // Remove XML comments
    svgContent = svgContent.replace(/<!--[\s\S]*?-->/g, '');

    // Ensure consistent stroke-width (Tabler icons already have stroke-width="2")
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
    let css = `/* Generated file - do not edit manually */\n/* Generated from Tabler Icons (stroke-width: ${strokeWidth}) */\n\n`;

    // First, define all CSS variables with the SVG data URIs
    css += `:root {\n`;
    for (const icon of icons) {
        const svgPath = path.join(svgDir, `${icon}.svg`);

        if (!fs.existsSync(svgPath)) {
            console.warn(`Warning: SVG file not found for icon "${icon}" at ${svgPath}`);
            continue;
        }

        const svgContent = fs.readFileSync(svgPath, 'utf8');
        const dataUri = svgToDataUri(svgContent);

        css += `  --phpdebugbar-icon-${icon}: url('${dataUri}');\n`;
    }
    css += `}\n\n`;

    // Then, apply the variables to the icon classes
    for (const icon of icons) {
        const svgPath = path.join(svgDir, `${icon}.svg`);

        if (!fs.existsSync(svgPath)) {
            continue;
        }

        css += `.phpdebugbar-icon-${icon}::before {\n`;
        css += `  -webkit-mask-image: var(--phpdebugbar-icon-${icon});\n`;
        css += `  mask-image: var(--phpdebugbar-icon-${icon});\n`;
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
