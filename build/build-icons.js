import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Icon mappings (FA icon name -> our icon name)
const iconMappings = {
    'xmark': 'times',           // FA7 renamed times to xmark
    'clock': 'clock-o',         // FA7 renamed clock-o to clock
    'list-alt': 'list-alt',
    'arrow-right': 'arrow-right',
    'code': 'code',
    'leaf': 'leaf',
    'bug': 'bug',
    'suitcase': 'suitcase',
    'bolt': 'bolt',
    'cogs': 'cogs',
    'tasks': 'tasks',
    'bookmark': 'bookmark',
    'flag': 'flag',
    'inbox': 'inbox',
    'cubes': 'cubes',
    'database': 'database',
    'tags': 'tags',
    'gear': 'gear',
    'magnifying-glass': 'search',  // FA7 renamed search to magnifying-glass
    'clock-rotate-left': 'history',
    'sliders': 'sliders',
};

const svgDir = path.join(__dirname, '../node_modules/@fortawesome/fontawesome-free/svgs/solid');
const outputFile = path.join(__dirname, '../resources/icons.css');

function svgToDataUri(svgContent) {
    // Remove XML comments
    svgContent = svgContent.replace(/<!--[\s\S]*?-->/g, '');
    // Minify: remove newlines and extra spaces
    svgContent = svgContent.replace(/\s+/g, ' ').trim();
    // URL encode for data URI
    const encoded = encodeURIComponent(svgContent)
        .replace(/'/g, '%27')
        .replace(/"/g, '%22');
    return `data:image/svg+xml,${encoded}`;
}

function generateIconsCSS() {
    let css = `/* Generated file - do not edit manually */\n/* Generated from Font Awesome icons */\n\n`;

    // First, define all CSS variables with the SVG data URIs
    css += `:root {\n`;
    for (const [faIcon, ourIcon] of Object.entries(iconMappings)) {
        const svgPath = path.join(svgDir, `${faIcon}.svg`);

        if (!fs.existsSync(svgPath)) {
            console.warn(`Warning: SVG file not found for icon "${faIcon}" at ${svgPath}`);
            continue;
        }

        const svgContent = fs.readFileSync(svgPath, 'utf8');
        const dataUri = svgToDataUri(svgContent);

        css += `  --phpdebugbar-icon-${ourIcon}: url('${dataUri}');\n`;
    }
    css += `}\n\n`;

    // Then, apply the variables to the icon classes
    for (const [faIcon, ourIcon] of Object.entries(iconMappings)) {
        const svgPath = path.join(svgDir, `${faIcon}.svg`);

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
