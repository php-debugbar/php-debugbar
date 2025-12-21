import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Icon mappings (our icon name -> Tabler icon name)
const iconMappings = {
    // Data collector icons
    'times': 'x',
    'clock-o': 'clock',
    'list-alt': 'list',
    'arrow-right': 'arrow-right',
    'code': 'code',
    'leaf': 'leaf',
    'bug': 'bug',
    'suitcase': 'briefcase',
    'bolt': 'bolt',
    'cogs': 'server-cog',
    'tasks': 'calendar',
    'bookmark': 'bookmark',
    'flag': 'flag',
    'inbox': 'inbox',
    'cubes': 'box',
    'database': 'database',
    'tags': 'tags',
    'gear': 'adjustments',
    'search': 'search',
    'history': 'history',
    'sliders': 'adjustments-horizontal',

    // UI control icons
    'minimize': 'chevron-down',
    'maximize': 'chevron-up',
    'close': 'x',
    'open': 'folder-open',
    'restore': 'brand-php',
};

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
    for (const [ourIcon, tablerIcon] of Object.entries(iconMappings)) {
        const svgPath = path.join(svgDir, `${tablerIcon}.svg`);

        if (!fs.existsSync(svgPath)) {
            console.warn(`Warning: SVG file not found for icon "${ourIcon}" (Tabler: ${tablerIcon}) at ${svgPath}`);
            continue;
        }

        const svgContent = fs.readFileSync(svgPath, 'utf8');
        const dataUri = svgToDataUri(svgContent);

        css += `  --phpdebugbar-icon-${ourIcon}: url('${dataUri}');\n`;
    }
    css += `}\n\n`;

    // Then, apply the variables to the icon classes
    for (const [ourIcon, tablerIcon] of Object.entries(iconMappings)) {
        const svgPath = path.join(svgDir, `${tablerIcon}.svg`);

        if (!fs.existsSync(svgPath)) {
            continue;
        }

        css += `.phpdebugbar-icon-${ourIcon}::before {\n`;
        css += `  -webkit-mask-image: var(--phpdebugbar-icon-${ourIcon});\n`;
        css += `  mask-image: var(--phpdebugbar-icon-${ourIcon});\n`;
        css += `}\n\n`;
    }

    fs.writeFileSync(outputFile, css, 'utf8');
    console.log(`✓ Generated ${outputFile} with ${Object.keys(iconMappings).length} icons`);
}

try {
    generateIconsCSS();
} catch (error) {
    console.error('Error generating icons:', error);
    process.exit(1);
}
