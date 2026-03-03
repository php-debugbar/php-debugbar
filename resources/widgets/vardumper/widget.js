(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Renders a JSON variable dump as an interactive expandable/collapsible tree.
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
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    const pre = document.createElement('pre');
                    pre.className = 'vd-dump';
                    pre.textContent = data;
                    return pre;
                }
            }

            const pre = document.createElement('pre');
            pre.className = 'vd-dump';
            this.renderNode(pre, data, 0);
            return pre;
        }

        renderNode(parent, node, depth) {
            if (!node || typeof node !== 'object') {
                const span = document.createElement('span');
                span.className = 'vd-scalar vd-null';
                span.textContent = 'null';
                parent.appendChild(span);
                return;
            }

            switch (node.t) {
                case 's':
                    this.renderScalar(parent, node);
                    break;
                case 'r':
                    this.renderString(parent, node);
                    break;
                case 'h':
                    this.renderHash(parent, node, depth);
                    break;
                default:
                    parent.appendChild(document.createTextNode(JSON.stringify(node)));
            }
        }

        renderScalar(parent, node) {
            const span = document.createElement('span');
            const st = node.st;

            if (st === 'boolean') {
                span.className = 'vd-scalar vd-const';
                span.textContent = node.v ? 'true' : 'false';
            } else if (st === 'NULL') {
                span.className = 'vd-scalar vd-const';
                span.textContent = 'null';
            } else if (st === 'integer' || st === 'double') {
                span.className = 'vd-scalar vd-num';
                span.textContent = String(node.v);
            } else if (st === 'label') {
                // Labels are annotations (e.g., from SourceContextProvider)
                if (node.v) {
                    span.className = 'vd-scalar vd-note';
                    span.textContent = node.v;
                    parent.appendChild(span);
                }
                return;
            } else {
                span.className = 'vd-scalar';
                span.textContent = String(node.v);
            }

            parent.appendChild(span);
        }

        renderString(parent, node) {
            const span = document.createElement('span');
            span.className = 'vd-str';

            const quote = document.createElement('span');
            quote.className = 'vd-quote';
            quote.textContent = '"';

            span.appendChild(quote.cloneNode(true));

            const text = document.createElement('span');
            text.textContent = node.v;
            span.appendChild(text);

            span.appendChild(quote.cloneNode(true));

            if (node.cut > 0) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'vd-ellipsis';
                const totalLen = node.len || (node.v.length + node.cut);
                ellipsis.textContent = '\u2026' + totalLen;
                span.appendChild(ellipsis);
            }

            parent.appendChild(span);
        }

        renderHash(parent, node, depth) {
            const children = node.children || [];
            const hasChildren = children.length > 0;
            const expanded = depth < this.expandedDepth;
            const ht = node.ht;

            // Hash type labels:
            // 1 = HASH_ASSOC (array), 2 = HASH_INDEXED (array), 4 = HASH_OBJECT, 5 = HASH_RESOURCE
            const isObject = (ht === 4);
            const isResource = (ht === 5);
            const isArray = (ht === 1 || ht === 2);

            // Reference info for already-seen objects
            if (node.ref && node.ref.c === 0) {
                // Soft ref with zero count means it's a reference to an already-dumped object
                const refSpan = document.createElement('span');
                refSpan.className = 'vd-ref';
                const label = isObject ? (node.cls || 'object') : (isResource ? (node.cls || 'resource') : 'array');
                refSpan.textContent = label + ' {#' + node.ref.s + ' \u2026}';
                parent.appendChild(refSpan);
                return;
            }

            // Header
            const header = document.createElement('span');
            header.className = 'vd-hash-header';

            if (isObject) {
                const cls = document.createElement('span');
                cls.className = 'vd-note';
                cls.textContent = String(node.cls || 'object');
                header.appendChild(cls);

                if (node.ref && node.ref.s) {
                    const refId = document.createElement('span');
                    refId.className = 'vd-ref';
                    refId.textContent = ' {#' + node.ref.s;
                    header.appendChild(refId);
                } else {
                    header.appendChild(document.createTextNode(' {'));
                }
            } else if (isResource) {
                const cls = document.createElement('span');
                cls.className = 'vd-note';
                cls.textContent = String(node.cls || 'resource');
                header.appendChild(cls);
                header.appendChild(document.createTextNode(' {'));
            } else {
                // Array
                header.appendChild(document.createTextNode('array:' + (node.cls || 0) + ' ['));
            }

            parent.appendChild(header);

            if (!hasChildren && !node.cut) {
                // Empty hash
                const closing = document.createElement('span');
                closing.textContent = isArray ? ']' : '}';
                parent.appendChild(closing);
                return;
            }

            // Toggle arrow
            const arrow = document.createElement('a');
            arrow.className = 'vd-arrow';
            arrow.href = '#';
            arrow.addEventListener('click', function (e) { e.preventDefault(); });

            const arrowSpan = document.createElement('span');
            arrowSpan.textContent = expanded ? '\u25BC' : '\u25B6'; // ▼ or ▶
            arrow.appendChild(arrowSpan);

            header.appendChild(arrow);

            // Toggle container
            const toggle = document.createElement('samp');
            toggle.className = 'vd-toggle';

            if (hasChildren) {
                if (expanded) {
                    toggle.classList.add('vd-expanded');
                } else {
                    toggle.classList.add('vd-collapsed');
                }
            }

            // Children container
            const childrenEl = document.createElement('samp');
            childrenEl.className = 'vd-children';
            if (!expanded) {
                childrenEl.hidden = true;
            }

            // Render each child entry
            for (let i = 0; i < children.length; i++) {
                const entry = children[i];
                const line = document.createElement('span');
                line.className = 'vd-child';

                // Key
                if (entry.kt !== undefined) {
                    this.renderKey(line, entry);
                }

                // Hard reference indicator
                if (entry.ref) {
                    const ref = document.createElement('span');
                    ref.className = 'vd-ref';
                    ref.textContent = '&' + entry.ref + ' ';
                    line.appendChild(ref);
                }

                // Value
                this.renderNode(line, entry.n, depth + 1);

                line.appendChild(document.createTextNode('\n'));
                childrenEl.appendChild(line);
            }

            // Cut indicator
            if (node.cut > 0) {
                const cutLine = document.createElement('span');
                cutLine.className = 'vd-child';
                const cutSpan = document.createElement('span');
                cutSpan.className = 'vd-ellipsis';
                cutSpan.textContent = '\u2026' + node.cut;
                cutLine.appendChild(cutSpan);
                cutLine.appendChild(document.createTextNode('\n'));
                childrenEl.appendChild(cutLine);
            }

            toggle.appendChild(childrenEl);

            // Collapsed summary (shown when collapsed)
            const summary = document.createElement('span');
            summary.className = 'vd-summary';
            if (expanded) {
                summary.hidden = true;
            }
            summary.textContent = ' \u2026';
            toggle.appendChild(summary);

            parent.appendChild(toggle);

            // Click handler for expand/collapse
            if (hasChildren || node.cut > 0) {
                const toggleHandler = function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const isExpanded = toggle.classList.contains('vd-expanded');
                    if (isExpanded) {
                        toggle.classList.remove('vd-expanded');
                        toggle.classList.add('vd-collapsed');
                        childrenEl.hidden = true;
                        summary.hidden = false;
                        arrowSpan.textContent = '\u25B6'; // ▶
                    } else {
                        toggle.classList.remove('vd-collapsed');
                        toggle.classList.add('vd-expanded');
                        childrenEl.hidden = false;
                        summary.hidden = true;
                        arrowSpan.textContent = '\u25BC'; // ▼
                    }
                };
                header.style.cursor = 'pointer';
                header.addEventListener('click', toggleHandler);
                summary.style.cursor = 'pointer';
                summary.addEventListener('click', toggleHandler);
            }

            // Closing bracket
            const closing = document.createElement('span');
            closing.className = 'vd-closing';
            closing.textContent = isArray ? ']' : '}';
            parent.appendChild(closing);
        }

        renderKey(parent, entry) {
            const span = document.createElement('span');
            const kt = entry.kt;

            switch (kt) {
                case 'index':
                    span.className = 'vd-key vd-index';
                    span.textContent = String(entry.k);
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(' => '));
                    break;
                case 'key':
                    span.className = 'vd-key vd-assoc-key';
                    span.textContent = '"' + entry.k + '"';
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(' => '));
                    break;
                case 'public':
                    span.className = 'vd-key vd-public';
                    span.textContent = '+' + entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'protected':
                    span.className = 'vd-key vd-protected';
                    span.textContent = '#' + entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'private':
                    span.className = 'vd-key vd-private';
                    span.textContent = '-' + entry.k;
                    if (entry.kc) {
                        span.setAttribute('title', 'Declared in ' + entry.kc);
                    }
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'meta':
                    span.className = 'vd-key vd-meta';
                    span.textContent = entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                default:
                    span.className = 'vd-key';
                    span.textContent = String(entry.k);
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
            }
        }
    }
    PhpDebugBar.Widgets.VarDumpRenderer = VarDumpRenderer;

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
            const renderer = new VarDumpRenderer();
            try {
                dd.appendChild(renderer.render(rawValue));
            } catch (e) {
                dd.textContent = String(rawValue);
            }

            if (value && value.xdebug_link) {
                dd.appendChild(PhpDebugBar.Widgets.editorLink(value.xdebug_link));
            }
        }
    }
    PhpDebugBar.Widgets.JsonVariableListWidget = JsonVariableListWidget;
})();
