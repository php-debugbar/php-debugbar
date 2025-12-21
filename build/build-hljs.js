// Build custom highlight.js bundle with specific languages
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import phpTemplate from 'highlight.js/lib/languages/php-template';
import javascript from 'highlight.js/lib/languages/javascript';
import sql from 'highlight.js/lib/languages/sql';
import shell from 'highlight.js/lib/languages/shell';
import css from 'highlight.js/lib/languages/css';
import plaintext from 'highlight.js/lib/languages/plaintext';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';

hljs.registerLanguage('php', php);
hljs.registerLanguage('php-template', phpTemplate);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('shell', shell);
hljs.registerLanguage('css', css);
hljs.registerLanguage('plaintext', plaintext);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('yaml', yaml);

// Configure to use custom CSS class prefix
hljs.configure({ classPrefix: 'phpdebugbar-hljs-' });

export default hljs;
