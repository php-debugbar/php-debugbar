(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    // Enable sf-dump-compact CSS (hide collapsed nodes). Symfony's Sfdump sets
    // this for HTML dumps; the JSON vardumper needs it too.
    document.documentElement.classList.add('sf-js-enabled');

    const lazyStore = new Map();
    let lazySeq = 0;
    const escMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
    const escRe = /[&<>"]/g;

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

                const savedDepth = this.expandedDepth;
                if (typeof data._sd === 'number') {
                    this.expandedDepth = data._sd;
                }
                pre.innerHTML = this.nodeToHtml(data, 0) + '\n';
                this.expandedDepth = savedDepth;

                return pre;
            }

            return data;
        }

        renderPlain(value) {
            const pre = document.createElement('pre');
            pre.className = 'sf-dump';
            const savedDepth = this.expandedDepth;
            this.expandedDepth = 0;
            pre.innerHTML = this.plainToHtml(value, 0) + '\n';
            this.expandedDepth = savedDepth;
            return pre;
        }

        plainToHtml(value, depth) {
            if (value === null) return '<span class=sf-dump-const>null</span>';
            switch (typeof value) {
                case 'boolean':
                    return '<span class=sf-dump-const>' + value + '</span>';
                case 'number':
                    return '<span class=sf-dump-num>' + this.esc(String(value)) + '</span>';
                case 'string':
                    return '"<span class=sf-dump-str>' + this.esc(value) + '</span>"';
                case 'object': {
                    const isIndexed = Array.isArray(value);
                    const keys = isIndexed ? null : Object.keys(value);
                    const len = isIndexed ? value.length : keys.length;

                    if (len === 0) return '[]';

                    const expanded = depth < this.expandedDepth;
                    let html = '<span class=sf-dump-note>array:' + len + '</span> [';
                    html += '<a class=sf-dump-toggle><span>' + (expanded ? '▼' : '▶') + '</span></a>';

                    // Preview
                    const previewParts = [];
                    const maxPreview = Math.min(len, 8);
                    for (let i = 0; i < maxPreview; i++) {
                        const k = isIndexed ? i : keys[i];
                        const v = isIndexed ? value[i] : value[keys[i]];
                        const pv = v === null ? 'null'
                            : typeof v === 'string' ? '"' + this.esc(v.length > 40 ? v.substring(0, 40) + '…' : v) + '"'
                            : typeof v === 'boolean' ? String(v)
                            : typeof v === 'number' ? String(v)
                            : '[…]';
                        previewParts.push(isIndexed ? pv : this.esc(String(k)) + ': ' + pv);
                    }
                    let preview = previewParts.join(', ');
                    if (len > maxPreview) preview += ', …';
                    html += '<span class="sf-dump-preview' + (expanded ? ' sf-dump-hidden' : '') + '"> ' + preview + ' ]</span>';

                    if (expanded) {
                        html += '<samp class=sf-dump-expanded>';
                        html += this.plainChildrenToHtml(value, isIndexed, keys, depth);
                        html += '</samp>';
                    } else {
                        const id = ++lazySeq;
                        lazyStore.set(id, { plain: value, isArr: isIndexed, keys, depth, renderer: this, expandedDepth: this.expandedDepth });
                        html += '<samp class=sf-dump-compact data-lazy=' + id + '></samp>';
                    }
                    html += '<span class="sf-dump-close' + (expanded ? '' : ' sf-dump-hidden') + '">]</span>';
                    return html;
                }
                default:
                    return this.esc(String(value));
            }
        }

        plainChildrenToHtml(value, isIndexed, keys, depth) {
            const len = isIndexed ? value.length : keys.length;
            let html = '';
            for (let i = 0; i < len; i++) {
                if (i > 0) html += '\n';
                if (isIndexed) {
                    html += '<span class=sf-dump-index>' + i + '</span> => ';
                    html += this.plainToHtml(value[i], depth + 1);
                } else {
                    html += '"<span class=sf-dump-key>' + this.esc(keys[i]) + '</span>" => ';
                    html += this.plainToHtml(value[keys[i]], depth + 1);
                }
            }
            return html;
        }

        esc(s) {
            return String(s).replace(escRe, m => escMap[m]);
        }

        nodeToHtml(node, depth) {
            if (!node || typeof node !== 'object') {
                return '<span class=sf-dump-const>null</span>';
            }

            switch (node.t) {
                case 's':
                    return this.scalarToHtml(node);
                case 'r':
                    return this.stringToHtml(node);
                case 'h':
                    return this.hashToHtml(node, depth);
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

        hashToHtml(node, depth) {
            const children = node.c || [];
            const hasChildren = children.length > 0;
            const ht = node.ht;
            const isObject = (ht === 4);
            const isResource = (ht === 5);
            const isArray = (ht === 1 || ht === 2);
            const closingChar = isArray ? ']' : '}';
            const expanded = depth < this.expandedDepth;

            let html = '';

            // Header
            let ref = '';
            if (isObject) {
                if (node.cls) {
                    html += '<span class=sf-dump-note>' + this.esc(String(node.cls)) + '</span> ';
                }
                html += '{';
                const refId = typeof node.ref === 'number' ? node.ref : node.ref?.s;
                if (refId) {
                    ref = '<span class=sf-dump-ref>#' + this.esc(String(refId)) + '</span> ';
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

            // Inline preview (visible when collapsed)
            const preview = this.previewHtml(children, node.cut, ht);
            html += '<span class="sf-dump-preview' + (expanded ? ' sf-dump-hidden' : '') + '"> ' + preview + ' ' + closingChar + '</span>';

            if (expanded) {
                // Render children eagerly
                html += '<samp class=sf-dump-expanded>';
                html += this.childrenToHtml(children, node.cut, depth, ht);
                html += '</samp>';
            } else {
                // Lazy placeholder — store data, emit empty samp
                const id = ++lazySeq;
                lazyStore.set(id, {
                    children,
                    cut: node.cut,
                    depth,
                    ht,
                    renderer: this,
                    expandedDepth: this.expandedDepth
                });
                html += '<samp class=sf-dump-compact data-lazy=' + id + '></samp>';
            }

            html += '<span class="sf-dump-close' + (expanded ? '' : ' sf-dump-hidden') + '">' + closingChar + '</span>';
            return html;
        }

        previewHtml(children, cut, ht) {
            const maxItems = 8;
            const parts = [];
            const isIndexed = (ht === 2);

            for (let i = 0; i < Math.min(children.length, maxItems); i++) {
                const entry = children[i];
                const val = this.previewValue(entry.n);

                if (isIndexed) {
                    parts.push(val);
                } else {
                    const key = (entry.k !== undefined) ? entry.k : i;
                    parts.push(this.esc(String(key)) + ': ' + val);
                }
            }

            let result = parts.join(', ');
            if (children.length > maxItems || cut > 0) {
                result += ', …';
            }
            return result;
        }

        previewValue(node) {
            if (!node || typeof node !== 'object') return '<span class=sf-dump-const>null</span>';

            switch (node.t) {
                case 's':
                    return this.scalarToHtml(node);
                case 'r': {
                    const str = node.v.length > 40 ? node.v.substring(0, 40) + '…' : node.v;
                    return '"<span class=sf-dump-str>' + this.esc(str) + '</span>"';
                }
                case 'h':
                    if (node.ht === 1 || node.ht === 2) return '[…]';
                    if (node.ht === 4 && node.cls) return this.esc(node.cls) + ' {…}';
                    return '{…}';
                default:
                    return '…';
            }
        }

        childrenToHtml(children, cut, depth, ht) {
            // Compute default key type once for all children of this hash
            const defaultKt = ht === 2 ? 'i' : ht === 5 ? 'meta' : ht === 4 ? 'pub' : undefined;
            let html = '';

            for (let i = 0; i < children.length; i++) {
                const entry = children[i];
                const kt = entry.kt ?? ((entry.k !== undefined || ht === 2) ? (defaultKt ?? ((typeof entry.k === 'number') ? 'i' : 'k')) : undefined);
                const k = (entry.k !== undefined) ? entry.k : i;

                if (i > 0) html += '\n';
                if (kt !== undefined) {
                    html += this.keyToHtml(kt, k, entry);
                }
                if (entry.ref) {
                    html += '<span class=sf-dump-ref>&amp;' + this.esc(String(entry.ref)) + '</span> ';
                }
                html += this.nodeToHtml(entry.n, depth + 1);
            }
            if (cut > 0) {
                html += '\n…' + cut;
            }
            return html;
        }

        keyToHtml(kt, key, entry) {
            const k = this.esc(String(key));

            // Compact format: single prefix field for object/resource visibility
            if (entry.p !== undefined) {
                switch (entry.p) {
                    case '+':
                        return '+"<span class=sf-dump-public title="Runtime added dynamic property">' + k + '</span>": ';
                    case '~':
                        return '<span class=sf-dump-meta>' + k + '</span>: ';
                    case '*':
                        return '#<span class=sf-dump-protected title="Protected property">' + k + '</span>: ';
                    case '':
                        return '-<span class=sf-dump-private title="Private property">' + k + '</span>: ';
                    default:
                        return '-<span class=sf-dump-private title="Private property declared in ' + this.esc(entry.p) + '">' + k + '</span>: ';
                }
            }

            // Legacy format (deprecated: kt/kc/dyn fields)
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

        if (data.plain !== undefined) {
            samp.innerHTML = renderer.plainChildrenToHtml(data.plain, data.isArr, data.keys, data.depth);
        } else {
            samp.innerHTML = renderer.childrenToHtml(data.children, data.cut, data.depth, data.ht);
        }

        renderer.expandedDepth = savedDepth;
    }

    function togglePreview(samp, expanding) {
        const preview = samp.previousElementSibling;
        const close = samp.nextElementSibling;
        if (preview) preview.classList.toggle('sf-dump-hidden', expanding);
        if (close) close.classList.toggle('sf-dump-hidden', !expanding);
    }

    document.addEventListener('click', function (e) {
        // Clicking the toggle or the preview triggers expand/collapse
        const toggle = e.target.closest('a.sf-dump-toggle') || e.target.closest('.sf-dump-preview')?.previousElementSibling;
        if (!toggle) return;

        const pre = toggle.closest('pre.sf-dump');
        if (!pre || pre.id) return; // has id → belongs to Sfdump, skip

        // Structure: toggle > preview > samp > close
        const samp = toggle.nextElementSibling?.nextElementSibling;
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
                    const toggleEl = s.previousElementSibling && s.previousElementSibling.previousElementSibling;
                    if (toggleEl && toggleEl.classList.contains('sf-dump-toggle')) toggleEl.lastElementChild.textContent = '▼';
                    togglePreview(s, true);
                });
            } else {
                // Collapse all expanded children
                samp.querySelectorAll('samp.sf-dump-expanded').forEach(function (s) {
                    s.classList.replace('sf-dump-expanded', 'sf-dump-compact');
                    const toggleEl = s.previousElementSibling && s.previousElementSibling.previousElementSibling;
                    if (toggleEl && toggleEl.classList.contains('sf-dump-toggle')) toggleEl.lastElementChild.textContent = '▶';
                    togglePreview(s, false);
                });
            }
        }

        // Toggle current
        samp.classList.toggle('sf-dump-compact', !isCompact);
        samp.classList.toggle('sf-dump-expanded', isCompact);
        toggle.lastElementChild.textContent = isCompact ? '▼' : '▶';
        togglePreview(samp, isCompact);
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
