(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    const lazyStore = new Map();
    let lazySeq = 0;

    /**
     * Renders a JSON variable dump as HTML with lazy-rendered collapsed nodes.
     *
     * Generates HTML using Sfdump CSS classes for styling compatibility. Collapsed
     * children are deferred until the user clicks to expand them. A document-level
     * click handler manages toggle/expand for these id-less <pre> elements, while
     * Sfdump continues to handle server-rendered HTML dumps (with IDs) unchanged.
     *
     * Usage:
     *   const renderer = new PhpDebugBar.Widgets.VarDumpRenderer({ expandedDepth: 1 });
     *   const el = renderer.render(jsonData);
     *   container.appendChild(el);
     */
    class VarDumpRenderer {
        constructor(options) {
            this.expandedDepth = (options && options.expandedDepth !== undefined) ? options.expandedDepth : 1;
        }

        render(data) {
            if (data && typeof data === 'object' && '_sd' in data) {
                const pre = document.createElement('pre');
                pre.className = 'sf-dump';
                pre.setAttribute('data-indent-pad', '  ');

                if (typeof data._sd === 'number') {
                    this.expandedDepth = data._sd;
                }
                pre.innerHTML = this.nodeToHtml(data, 0, '') + '\n';

                return pre;
            }

            return data;
        }

        esc(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        nodeToHtml(node, depth, indent) {
            if (!node || typeof node !== 'object') {
                return '<span class=sf-dump-const>null</span>';
            }

            switch (node.t) {
                case 's':
                    return this.scalarToHtml(node);
                case 'r':
                    return this.stringToHtml(node);
                case 'h':
                    return this.hashToHtml(node, depth, indent);
                default:
                    return this.esc(JSON.stringify(node));
            }
        }

        scalarToHtml(node) {
            const st = node.st;
            if (st === 'boolean') {
                return '<span class=sf-dump-const>' + (node.v === true ? 'true' : 'false') + '</span>';
            }
            if (st === 'NULL') {
                return '<span class=sf-dump-const>null</span>';
            }
            if (st === 'integer' || st === 'double') {
                return '<span class=sf-dump-num>' + this.esc(String(node.v)) + '</span>';
            }
            if (st === 'label') {
                return node.v ? '<span class=sf-dump-note>' + this.esc(node.v) + '</span>' : '';
            }
            return this.esc(String(node.v));
        }

        stringToHtml(node) {
            const totalLen = node.len || (node.v.length + (node.cut || 0));
            let html = '"<span class=sf-dump-str title="' + totalLen + ' characters">' + this.esc(node.v) + '</span>';
            if (node.cut > 0) {
                html += '…';
            }
            html += '"';
            return html;
        }

        hashToHtml(node, depth, indent) {
            const children = node.children || [];
            const hasChildren = children.length > 0;
            const ht = node.ht;
            const isObject = (ht === 4);
            const isResource = (ht === 5);
            const isArray = (ht === 1 || ht === 2);
            const closingChar = isArray ? ']' : '}';
            const childIndent = indent + '  ';
            const expanded = depth < this.expandedDepth;

            let html = '';

            // Header
            if (isObject) {
                html += '<span class=sf-dump-note>' + this.esc(String(node.cls || 'object')) + '</span>';
                html += ' {';
                if (node.ref && node.ref.s) {
                    html += '<a class=sf-dump-ref>#' + this.esc(String(node.ref.s)) + '</a>';
                }
            } else if (isResource) {
                html += '<span class=sf-dump-note>' + this.esc(String(node.cls || 'resource')) + '</span>';
                html += ' {';
            } else {
                // Array
                if (node.cls) {
                    html += '<span class=sf-dump-note>array:' + this.esc(String(node.cls)) + '</span> [';
                } else {
                    html += '[';
                }
            }

            // Empty hash
            if (!hasChildren && !node.cut) {
                html += closingChar;
                return html;
            }

            // Cut-only (no expandable children)
            if (!hasChildren && node.cut > 0) {
                html += ' …' + node.cut + closingChar;
                return html;
            }

            // Toggle anchor
            html += '<a class=sf-dump-toggle><span>' + (expanded ? '▼' : '▶') + '</span></a>';

            if (expanded) {
                // Render children eagerly
                html += '<samp data-depth=' + (depth + 1) + ' class=sf-dump-expanded>';

                for (let i = 0; i < children.length; i++) {
                    const entry = children[i];
                    html += '\n' + childIndent;

                    if (entry.kt !== undefined) {
                        html += this.keyToHtml(entry);
                    }
                    if (entry.ref) {
                        html += '<span class=sf-dump-ref>&amp;' + this.esc(String(entry.ref)) + '</span> ';
                    }
                    html += this.nodeToHtml(entry.n, depth + 1, childIndent);
                }

                if (node.cut > 0) {
                    html += '\n' + childIndent + '…' + node.cut;
                }

                html += '\n' + indent + '</samp>';
            } else {
                // Lazy placeholder — store data, emit empty samp
                const id = ++lazySeq;
                lazyStore.set(id, {
                    children: children,
                    cut: node.cut,
                    depth: depth,
                    childIndent: childIndent,
                    indent: indent,
                    renderer: this,
                    expandedDepth: this.expandedDepth
                });
                html += '<samp data-depth=' + (depth + 1) + ' class=sf-dump-compact data-lazy=' + id + '></samp>';
            }

            html += closingChar;
            return html;
        }

        keyToHtml(entry) {
            const kt = entry.kt;
            const k = this.esc(String(entry.k));

            switch (kt) {
                case 'index':
                    return '<span class=sf-dump-index>' + k + '</span> => ';
                case 'key':
                    return '"<span class=sf-dump-key>' + k + '</span>" => ';
                case 'public':
                    if (entry.dyn) {
                        return '+"<span class=sf-dump-public title="Runtime added dynamic property">' + k + '</span>": ';
                    }
                    return '+<span class=sf-dump-public title="Public property">' + k + '</span>: ';
                case 'protected':
                    return '#<span class=sf-dump-protected title="Protected property">' + k + '</span>: ';
                case 'private': {
                    let title = 'Private property';
                    if (entry.kc) {
                        title += ' declared in ' + this.esc(entry.kc);
                    }
                    return '-<span class=sf-dump-private title="' + title + '">' + k + '</span>: ';
                }
                case 'meta':
                    return '<span class=sf-dump-meta>' + k + '</span>: ';
                default:
                    return k + ': ';
            }
        }
    }
    PhpDebugBar.Widgets.VarDumpRenderer = VarDumpRenderer;

    function expandLazy(samp) {
        const id = parseInt(samp.dataset.lazy, 10);
        const data = lazyStore.get(id);
        if (!data) return;

        const renderer = data.renderer;
        const savedDepth = renderer.expandedDepth;
        renderer.expandedDepth = data.expandedDepth;

        let html = '';
        for (let i = 0; i < data.children.length; i++) {
            const entry = data.children[i];
            html += '\n' + data.childIndent;

            if (entry.kt !== undefined) {
                html += renderer.keyToHtml(entry);
            }
            if (entry.ref) {
                html += '<span class=sf-dump-ref>&amp;' + renderer.esc(String(entry.ref)) + '</span> ';
            }
            html += renderer.nodeToHtml(entry.n, data.depth + 1, data.childIndent);
        }

        if (data.cut > 0) {
            html += '\n' + data.childIndent + '…' + data.cut;
        }

        html += '\n' + data.indent;
        samp.innerHTML = html;

        delete samp.dataset.lazy;
        lazyStore.delete(id);
        renderer.expandedDepth = savedDepth;
    }

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('a.sf-dump-toggle');
        if (!toggle) return;
        const pre = toggle.closest('pre.sf-dump');
        if (!pre || pre.id) return; // has id → belongs to Sfdump, skip

        const samp = toggle.nextElementSibling;
        if (!samp || samp.tagName !== 'SAMP') return;

        e.preventDefault();
        const isCompact = samp.classList.contains('sf-dump-compact');

        // Lazy expand if needed
        if (isCompact && samp.dataset.lazy) expandLazy(samp);

        // Ctrl/Meta+click → recursive
        if (e.ctrlKey || e.metaKey) {
            if (isCompact) {
                // Expand all lazy descendants first
                let pending;
                while ((pending = samp.querySelectorAll('[data-lazy]')).length) {
                    pending.forEach(expandLazy);
                }
                // Then expand all compact children
                samp.querySelectorAll('samp.sf-dump-compact').forEach(function (s) {
                    s.classList.replace('sf-dump-compact', 'sf-dump-expanded');
                    const span = s.previousElementSibling && s.previousElementSibling.querySelector('span');
                    if (span) span.textContent = '▼';
                });
            } else {
                // Collapse all expanded children
                samp.querySelectorAll('samp.sf-dump-expanded').forEach(function (s) {
                    s.classList.replace('sf-dump-expanded', 'sf-dump-compact');
                    const span = s.previousElementSibling && s.previousElementSibling.querySelector('span');
                    if (span) span.textContent = '▶';
                });
            }
        }

        // Toggle current
        samp.classList.toggle('sf-dump-compact', !isCompact);
        samp.classList.toggle('sf-dump-expanded', isCompact);
        toggle.querySelector('span').textContent = isCompact ? '▼' : '▶';
    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where values are rendered using VarDumpRenderer.
     * Drop-in replacement for HtmlVariableListWidget when using JsonDataFormatter.
     *
     * Options:
     *  - data
     */
    const jsonVarDumpRenderer = new VarDumpRenderer();

    class JsonVariableListWidget extends PhpDebugBar.Widgets.KVListWidget {
        get className() {
            return csscls('kvlist jsonvarlist');
        }

        itemRenderer(dt, dd, key, value) {
            const span = document.createElement('span');
            span.setAttribute('title', key);
            span.textContent = key;
            dt.appendChild(span);

            const rawValue = (value && value.value !== undefined) ? value.value : value;
            dd.appendChild(jsonVarDumpRenderer.render(rawValue));

            if (value && value.xdebug_link) {
                dd.appendChild(PhpDebugBar.Widgets.editorLink(value.xdebug_link));
            }
        }
    }
    PhpDebugBar.Widgets.JsonVariableListWidget = JsonVariableListWidget;
})();
