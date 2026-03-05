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

                const savedDepth = this.expandedDepth;
                if (typeof data._sd === 'number') {
                    this.expandedDepth = data._sd;
                }
                pre.innerHTML = this.nodeToHtml(data, 0, '') + '\n';
                this.expandedDepth = savedDepth;

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
            const s = node.s;
            if (s === 'b') {
                return '<span class=sf-dump-const>' + (node.v === true ? 'true' : 'false') + '</span>';
            }
            if (s === 'n') {
                return '<span class=sf-dump-const>null</span>';
            }
            if (s === 'i' || s === 'd') {
                return '<span class=sf-dump-num>' + this.esc(String(node.v)) + '</span>';
            }
            if (s === 'l') {
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
            const children = node.c || [];
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
            let ref = '';
            if (isObject) {
                if (node.cls) {
                    html += '<span class=sf-dump-note>' + this.esc(String(node.cls)) + '</span> ';
                }
                html += '{';
                if (node.ref && node.ref.s) {
                    ref = '<span class=sf-dump-ref>#' + this.esc(String(node.ref.s)) + '</span> ';
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
                if (ref) html += ref;
                html += closingChar;
                return html;
            }

            // Cut-only (no expandable children)
            if (!hasChildren && node.cut > 0) {
                if (ref) html += ref;
                html += ' …' + node.cut + closingChar;
                return html;
            }

            // Toggle anchor (includes ref if present)
            html += '<a class=sf-dump-toggle>' + ref + '<span>' + (expanded ? '▼' : '▶') + '</span></a>';

            if (expanded) {
                // Render children eagerly
                html += '<samp data-depth=' + (depth + 1) + ' class=sf-dump-expanded>';
                html += this.childrenToHtml(children, node.cut, depth, childIndent, indent, ht);
                html += '</samp>';
            } else {
                // Lazy placeholder — store data, emit empty samp
                const id = ++lazySeq;
                lazyStore.set(id, {
                    children: children,
                    cut: node.cut,
                    depth: depth,
                    childIndent: childIndent,
                    indent: indent,
                    ht: ht,
                    renderer: this,
                    expandedDepth: this.expandedDepth
                });
                html += '<samp data-depth=' + (depth + 1) + ' class=sf-dump-compact data-lazy=' + id + '></samp>';
            }

            html += closingChar;
            return html;
        }

        childrenToHtml(children, cut, depth, childIndent, indent, ht) {
            let html = '';
            for (let i = 0; i < children.length; i++) {
                const entry = children[i];
                html += '\n' + childIndent;

                // Infer missing kt from parent hash type
                let kt = entry.kt;
                if (kt === undefined) {
                    if (entry.k !== undefined || ht === 2) {
                        if (ht === 2) kt = 'i';               // HASH_INDEXED
                        else if (ht === 5) kt = 'meta';        // HASH_RESOURCE
                        else if (ht === 4) kt = 'pub';         // HASH_OBJECT default
                        else kt = (typeof entry.k === 'number') ? 'i' : 'k';  // HASH_ASSOC
                    }
                }

                // Infer missing k from loop index (HASH_INDEXED)
                const k = (entry.k !== undefined) ? entry.k : i;

                if (kt !== undefined) {
                    html += this.keyToHtml(kt, k, entry);
                }
                if (entry.ref) {
                    html += '<span class=sf-dump-ref>&amp;' + this.esc(String(entry.ref)) + '</span> ';
                }
                html += this.nodeToHtml(entry.n, depth + 1, childIndent);
            }
            if (cut > 0) {
                html += '\n' + childIndent + '…' + cut;
            }
            html += '\n' + indent;
            return html;
        }

        keyToHtml(kt, key, entry) {
            const k = this.esc(String(key));

            switch (kt) {
                case 'i':
                    return '<span class=sf-dump-index>' + k + '</span> => ';
                case 'k':
                    return '"<span class=sf-dump-key>' + k + '</span>" => ';
                case 'pub':
                    if (entry.dyn) {
                        return '+"<span class=sf-dump-public title="Runtime added dynamic property">' + k + '</span>": ';
                    }
                    return '+<span class=sf-dump-public title="Public property">' + k + '</span>: ';
                case 'pro':
                    return '#<span class=sf-dump-protected title="Protected property">' + k + '</span>: ';
                case 'pri': {
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
        delete samp.dataset.lazy;

        const data = lazyStore.get(id);
        if (!data) return;
        lazyStore.delete(id);

        const renderer = data.renderer;
        const savedDepth = renderer.expandedDepth;
        renderer.expandedDepth = data.expandedDepth;

        samp.innerHTML = renderer.childrenToHtml(data.children, data.cut, data.depth, data.childIndent, data.indent, data.ht);

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
                    const arrow = s.previousElementSibling && s.previousElementSibling.lastElementChild;
                    if (arrow) arrow.textContent = '▼';
                });
            } else {
                // Collapse all expanded children
                samp.querySelectorAll('samp.sf-dump-expanded').forEach(function (s) {
                    s.classList.replace('sf-dump-expanded', 'sf-dump-compact');
                    const arrow = s.previousElementSibling && s.previousElementSibling.lastElementChild;
                    if (arrow) arrow.textContent = '▶';
                });
            }
        }

        // Toggle current
        samp.classList.toggle('sf-dump-compact', !isCompact);
        samp.classList.toggle('sf-dump-expanded', isCompact);
        toggle.lastElementChild.textContent = isCompact ? '▼' : '▶';
    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where values are rendered using VarDumpRenderer.
     * Drop-in replacement for HtmlVariableListWidget when using JsonDataFormatter.
     *
     * Options:
     *  - data
     */

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
            PhpDebugBar.Widgets.renderValueInto(dd, rawValue);

            if (value && value.xdebug_link) {
                dd.appendChild(PhpDebugBar.Widgets.editorLink(value.xdebug_link));
            }
        }
    }
    PhpDebugBar.Widgets.JsonVariableListWidget = JsonVariableListWidget;
})();
