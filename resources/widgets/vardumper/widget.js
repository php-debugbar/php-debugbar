(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Renders a JSON variable dump as an interactive expandable/collapsible tree.
     * Uses the same sf-dump-* CSS classes as Symfony's HtmlDumper for consistent styling.
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
                    pre.className = 'sf-dump';
                    pre.textContent = data;
                    return pre;
                }
            }

            // Dump node (from Symfony VarDumper)
            if (data && typeof data === 'object' && data.t) {
                const pre = document.createElement('pre');
                pre.className = 'sf-dump';
                this.renderNode(pre, data, 0);
                return pre;
            }

            // Plain JSON value (from simple value fast path)
            const pre = document.createElement('pre');
            pre.className = 'sf-dump';
            this.renderPlainValue(pre, data, 0);
            return pre;
        }

        renderNode(parent, node, depth) {
            if (!node || typeof node !== 'object') {
                const span = document.createElement('span');
                span.className = 'sf-dump-const';
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
                span.className = 'sf-dump-const';
                span.textContent = node.v === true ? 'true' : 'false';
            } else if (st === 'NULL') {
                span.className = 'sf-dump-const';
                span.textContent = 'null';
            } else if (st === 'integer' || st === 'double') {
                span.className = 'sf-dump-num';
                span.textContent = String(node.v);
            } else if (st === 'label') {
                // Labels are annotations (e.g., from SourceContextProvider)
                if (node.v) {
                    span.className = 'sf-dump-note';
                    span.textContent = node.v;
                    parent.appendChild(span);
                }
                return;
            } else {
                span.textContent = String(node.v);
            }

            parent.appendChild(span);
        }

        renderString(parent, node) {
            const span = document.createElement('span');
            span.className = 'sf-dump-str';

            if (node.cut > 0) {
                span.appendChild(document.createTextNode('"' + node.v + '"'));
                const ellipsis = document.createElement('span');
                ellipsis.className = 'sf-dump-ellipsis';
                const totalLen = node.len || (node.v.length + node.cut);
                ellipsis.textContent = '\u2026' + totalLen;
                span.appendChild(ellipsis);
            } else {
                span.textContent = '"' + node.v + '"';
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

            // Header
            const header = document.createElement('span');

            if (isObject) {
                const cls = document.createElement('span');
                cls.className = 'sf-dump-note';
                cls.textContent = String(node.cls || 'object');
                header.appendChild(cls);

                if (node.ref && node.ref.s) {
                    const refId = document.createElement('span');
                    refId.className = 'sf-dump-ref';
                    refId.textContent = ' {#' + node.ref.s;
                    header.appendChild(refId);
                } else {
                    header.appendChild(document.createTextNode(' {'));
                }
            } else if (isResource) {
                const cls = document.createElement('span');
                cls.className = 'sf-dump-note';
                cls.textContent = String(node.cls || 'resource');
                header.appendChild(cls);
                header.appendChild(document.createTextNode(' {'));
            } else {
                // Array: show "array:N [" when count > 0, just "[" for empty
                if (node.cls) {
                    const cls = document.createElement('span');
                    cls.className = 'sf-dump-note';
                    cls.textContent = 'array:' + node.cls;
                    header.appendChild(cls);
                    header.appendChild(document.createTextNode(' ['));
                } else {
                    header.appendChild(document.createTextNode('['));
                }
            }

            const closingChar = isArray ? ']' : '}';

            parent.appendChild(header);

            if (!hasChildren && !node.cut) {
                // Empty hash: array:0 [] or ClassName {}
                parent.appendChild(document.createTextNode(closingChar));
                return;
            }

            if (!hasChildren && node.cut > 0) {
                // Cut-only (no expandable children): render compact inline
                // e.g. array:12 [ …12] or ClassName {#id …12}
                const cutSpan = document.createElement('span');
                cutSpan.className = 'sf-dump-ellipsis';
                cutSpan.textContent = ' \u2026' + node.cut;
                parent.appendChild(cutSpan);
                parent.appendChild(document.createTextNode(closingChar));
                return;
            }

            // Toggle arrow
            const arrow = document.createElement('a');
            arrow.className = 'sf-dump-toggle';
            arrow.href = '#';
            arrow.textContent = expanded ? '\u25BC' : '\u25B6'; // ▼ or ▶

            header.appendChild(arrow);

            // Toggle container
            const toggle = document.createElement('samp');
            toggle.className = expanded ? 'sf-dump-expanded' : 'sf-dump-compact';

            // Children container
            const childrenEl = document.createElement('samp');
            childrenEl.className = 'sf-dump-children';
            if (!expanded) {
                childrenEl.hidden = true;
            }

            // Render each child entry
            for (let i = 0; i < children.length; i++) {
                const entry = children[i];
                const line = document.createElement('span');
                line.className = 'sf-dump-child';

                // Key
                if (entry.kt !== undefined) {
                    this.renderKey(line, entry);
                }

                // Hard reference indicator
                if (entry.ref) {
                    const ref = document.createElement('span');
                    ref.className = 'sf-dump-ref';
                    ref.textContent = '&' + entry.ref + ' ';
                    line.appendChild(ref);
                }

                // Value
                this.renderNode(line, entry.n, depth + 1);

                line.appendChild(document.createTextNode('\n'));
                childrenEl.appendChild(line);
            }

            // Cut indicator (inside expanded children)
            if (node.cut > 0) {
                const cutLine = document.createElement('span');
                cutLine.className = 'sf-dump-child';
                const cutSpan = document.createElement('span');
                cutSpan.className = 'sf-dump-ellipsis';
                cutSpan.textContent = '\u2026' + node.cut;
                cutLine.appendChild(cutSpan);
                cutLine.appendChild(document.createTextNode('\n'));
                childrenEl.appendChild(cutLine);
            }

            // Closing bracket inside expanded view (on its own line, at parent indent)
            childrenEl.appendChild(document.createTextNode(closingChar));

            toggle.appendChild(childrenEl);

            // Collapsed summary: " …]" or " …}" (includes closing bracket)
            const summary = document.createElement('span');
            summary.className = 'sf-dump-summary';
            if (expanded) {
                summary.hidden = true;
            }
            summary.textContent = ' \u2026' + closingChar;
            toggle.appendChild(summary);

            parent.appendChild(toggle);

            // Click handler for expand/collapse
            let isExpanded = expanded;
            const toggleHandler = function (e) {
                e.stopPropagation();
                e.preventDefault();
                isExpanded = !isExpanded;
                childrenEl.hidden = !isExpanded;
                summary.hidden = isExpanded;
                arrow.textContent = isExpanded ? '\u25BC' : '\u25B6'; // ▼ or ▶
            };
            header.style.cursor = 'pointer';
            header.addEventListener('click', toggleHandler);
            summary.style.cursor = 'pointer';
            summary.addEventListener('click', toggleHandler);
        }

        renderPlainValue(parent, value, depth) {
            if (value === null) {
                const span = document.createElement('span');
                span.className = 'sf-dump-const';
                span.textContent = 'null';
                parent.appendChild(span);
            } else if (typeof value === 'boolean') {
                const span = document.createElement('span');
                span.className = 'sf-dump-const';
                span.textContent = value ? 'true' : 'false';
                parent.appendChild(span);
            } else if (typeof value === 'number') {
                const span = document.createElement('span');
                span.className = 'sf-dump-num';
                span.textContent = String(value);
                parent.appendChild(span);
            } else if (typeof value === 'string') {
                const span = document.createElement('span');
                span.className = 'sf-dump-str';
                span.textContent = '"' + value + '"';
                parent.appendChild(span);
            } else if (Array.isArray(value)) {
                this.renderPlainHash(parent, value, depth, true);
            } else if (typeof value === 'object') {
                this.renderPlainHash(parent, value, depth, false);
            }
        }

        renderPlainHash(parent, value, depth, isArray) {
            const keys = isArray ? null : Object.keys(value);
            const count = isArray ? value.length : keys.length;
            const expanded = depth < this.expandedDepth;
            const closingChar = isArray ? ']' : '}';

            // Header
            const header = document.createElement('span');
            if (isArray && count > 0) {
                const cls = document.createElement('span');
                cls.className = 'sf-dump-note';
                cls.textContent = 'array:' + count;
                header.appendChild(cls);
                header.appendChild(document.createTextNode(' ['));
            } else {
                header.appendChild(document.createTextNode(isArray ? '[' : '{'));
            }
            parent.appendChild(header);

            if (count === 0) {
                parent.appendChild(document.createTextNode(closingChar));
                return;
            }

            // Toggle arrow
            const arrow = document.createElement('a');
            arrow.className = 'sf-dump-toggle';
            arrow.href = '#';
            arrow.textContent = expanded ? '\u25BC' : '\u25B6';
            header.appendChild(arrow);

            // Toggle container
            const toggle = document.createElement('samp');

            // Children container
            const childrenEl = document.createElement('samp');
            childrenEl.className = 'sf-dump-children';
            if (!expanded) {
                childrenEl.hidden = true;
            }

            // Render entries
            const items = isArray ? value : keys;
            for (let i = 0; i < items.length; i++) {
                const line = document.createElement('span');
                line.className = 'sf-dump-child';

                const keySpan = document.createElement('span');
                if (isArray) {
                    keySpan.className = 'sf-dump-index';
                    keySpan.textContent = String(i);
                    line.appendChild(keySpan);
                    line.appendChild(document.createTextNode(' => '));
                    this.renderPlainValue(line, value[i], depth + 1);
                } else {
                    keySpan.className = 'sf-dump-key';
                    keySpan.textContent = '"' + items[i] + '"';
                    line.appendChild(keySpan);
                    line.appendChild(document.createTextNode(' => '));
                    this.renderPlainValue(line, value[items[i]], depth + 1);
                }

                line.appendChild(document.createTextNode('\n'));
                childrenEl.appendChild(line);
            }

            // Closing bracket
            childrenEl.appendChild(document.createTextNode(closingChar));
            toggle.appendChild(childrenEl);

            // Collapsed summary
            const summary = document.createElement('span');
            summary.className = 'sf-dump-summary';
            if (expanded) {
                summary.hidden = true;
            }
            summary.textContent = ' \u2026' + closingChar;
            toggle.appendChild(summary);

            parent.appendChild(toggle);

            // Click handler for expand/collapse
            let isExpanded = expanded;
            const toggleHandler = function (e) {
                e.stopPropagation();
                e.preventDefault();
                isExpanded = !isExpanded;
                childrenEl.hidden = !isExpanded;
                summary.hidden = isExpanded;
                arrow.textContent = isExpanded ? '\u25BC' : '\u25B6';
            };
            header.style.cursor = 'pointer';
            header.addEventListener('click', toggleHandler);
            summary.style.cursor = 'pointer';
            summary.addEventListener('click', toggleHandler);
        }

        renderKey(parent, entry) {
            const span = document.createElement('span');
            const kt = entry.kt;

            switch (kt) {
                case 'index':
                    span.className = 'sf-dump-index';
                    span.textContent = String(entry.k);
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(' => '));
                    break;
                case 'key':
                    span.className = 'sf-dump-key';
                    span.textContent = '"' + entry.k + '"';
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(' => '));
                    break;
                case 'public':
                    span.className = 'sf-dump-public';
                    span.textContent = '+' + entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'protected':
                    span.className = 'sf-dump-protected';
                    span.textContent = '#' + entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'private':
                    span.className = 'sf-dump-private';
                    span.textContent = '-' + entry.k;
                    if (entry.kc) {
                        span.setAttribute('title', 'Declared in ' + entry.kc);
                    }
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                case 'meta':
                    span.className = 'sf-dump-meta';
                    span.textContent = entry.k;
                    parent.appendChild(span);
                    parent.appendChild(document.createTextNode(': '));
                    break;
                default:
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
