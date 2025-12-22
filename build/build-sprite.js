// Build SVG sprite from Tabler icons
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Tabler icons to include (same list as build-icons.js)
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

    // Widget-specific icons
    'cpu',           // memory/performance
    'table',         // row count
    'link',          // statement ID
    'copy',          // copy to clipboard
    'circle-check',  // copy success confirmation
    'external-link', // editor link
];

const svgDir = path.join(__dirname, '../node_modules/@tabler/icons/icons/outline');
const outputFileSvg = path.join(__dirname, '../resources/icons.svg');
const outputFileJs = path.join(__dirname, '../resources/icons.js');

function generateSprite() {
    let svg = `<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">\n`;
    svg += `  <!-- Generated from Tabler Icons - do not edit manually -->\n\n`;

    for (const icon of icons) {
        const svgPath = path.join(svgDir, `${icon}.svg`);

        if (!fs.existsSync(svgPath)) {
            console.warn(`Warning: SVG file not found for icon "${icon}" at ${svgPath}`);
            continue;
        }

        const svgContent = fs.readFileSync(svgPath, 'utf8');

        // Extract the content between <svg> tags
        const viewBoxMatch = svgContent.match(/viewBox="([^"]+)"/);
        const pathsMatch = svgContent.match(/<svg[^>]*>([\s\S]*)<\/svg>/);

        if (!viewBoxMatch || !pathsMatch) {
            console.warn(`Warning: Could not parse SVG for icon "${icon}"`);
            continue;
        }

        const viewBox = viewBoxMatch[1];
        const innerContent = pathsMatch[1]
            .replace(/<!--[\s\S]*?-->/g, '') // Remove comments
            .replace(/\s+/g, ' ')             // Collapse whitespace
            .trim();

        // Create symbol with id
        svg += `  <symbol id="debugbar-icon-${icon}" viewBox="${viewBox}">\n`;
        svg += `    ${innerContent}\n`;
        svg += `  </symbol>\n\n`;
    }

    svg += `</svg>\n`;

    // Write SVG file
    fs.writeFileSync(outputFileSvg, svg, 'utf8');
    console.log(`✓ Generated ${outputFileSvg} with ${icons.length} icons`);

    // Generate JavaScript file with auto-inject code
    const jsContent = `// Generated file - do not edit manually
// Auto-inject SVG sprite
(function() {
    if (typeof document !== 'undefined' && !document.getElementById('phpdebugbar-icons')) {
        const div = document.createElement('div');
        div.id = 'phpdebugbar-icons';
        div.innerHTML = ${JSON.stringify(svg)};
        const target = document.body || document.documentElement;
        if (target.firstChild) {
            target.insertBefore(div.firstChild, target.firstChild);
        } else {
            target.appendChild(div.firstChild);
        }
    }
})();
`;

    fs.writeFileSync(outputFileJs, jsContent, 'utf8');
    console.log(`✓ Generated ${outputFileJs} with inline SVG sprite`);
}

try {
    generateSprite();
} catch (error) {
    console.error('Error generating sprite:', error);
    process.exit(1);
}
