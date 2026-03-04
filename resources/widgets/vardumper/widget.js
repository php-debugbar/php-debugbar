(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    let dumpId = 0;

    /**
     * Renders a JSON variable dump as Sfdump()-compatible HTML.
     *
     * Generates HTML matching Symfony's HtmlDumper output format, then relies on
     * Sfdump() (loaded via inline_head) for all interactivity (expand/collapse,
     * search, cross-references).
     *
     * Usage:
     *   const renderer = new PhpDebugBar.Widgets.VarDumpRenderer({ expandedDepth: 1 });
     *   const el = renderer.render(jsonData);
     *   container.appendChild(el);
     *   // Then PhpDebugBar.utils.sfDump(container) activates Sfdump on all pre.sf-dump[id]
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
                    pre.id = 'sf-dump-' + (++dumpId);
                    pre.setAttribute('data-indent-pad', '  ');
                    pre.textContent = data;
                    return pre;
                }
            }

            const pre = document.createElement('pre');
            pre.className = 'sf-dump';
            pre.id = 'sf-dump-' + (++dumpId);
            pre.setAttribute('data-indent-pad', '  ');

            let html;
            if (data && typeof data === 'object' && data.t) {
                html = this.nodeToHtml(data, 0, '');
            } else {
                html = this.plainToHtml(data, 0, '');
            }

            pre.innerHTML = html + '\n';
            return pre;
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
            const sampClass = expanded ? 'sf-dump-expanded' : 'sf-dump-compact';

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

            // Children in <samp>
            html += '<samp data-depth=' + (depth + 1) + ' class=' + sampClass + '>';

            for (let i = 0; i < children.length; i++) {
                const entry = children[i];
                html += '\n' + childIndent;

                // Key
                if (entry.kt !== undefined) {
                    html += this.keyToHtml(entry);
                }

                // Hard reference
                if (entry.ref) {
                    html += '<span class=sf-dump-ref>&amp;' + this.esc(String(entry.ref)) + '</span> ';
                }

                // Value
                html += this.nodeToHtml(entry.n, depth + 1, childIndent);
            }

            // Cut indicator
            if (node.cut > 0) {
                html += '\n' + childIndent + '…' + node.cut;
            }

            html += '\n' + indent + '</samp>' + closingChar;
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

        plainToHtml(value, depth, indent) {
            if (value === null) {
                return '<span class=sf-dump-const>null</span>';
            }
            if (typeof value === 'boolean') {
                return '<span class=sf-dump-const>' + (value ? 'true' : 'false') + '</span>';
            }
            if (typeof value === 'number') {
                return '<span class=sf-dump-num>' + value + '</span>';
            }
            if (typeof value === 'string') {
                return '"<span class=sf-dump-str title="' + value.length + ' characters">' + this.esc(value) + '</span>"';
            }
            if (Array.isArray(value)) {
                return this.plainHashToHtml(value, depth, indent, true);
            }
            if (typeof value === 'object') {
                return this.plainHashToHtml(value, depth, indent, false);
            }
            return this.esc(String(value));
        }

        plainHashToHtml(value, depth, indent, isArray) {
            const keys = isArray ? null : Object.keys(value);
            const count = isArray ? value.length : keys.length;
            const closingChar = isArray ? ']' : '}';
            const expanded = depth < this.expandedDepth;
            const sampClass = expanded ? 'sf-dump-expanded' : 'sf-dump-compact';
            const childIndent = indent + '  ';

            let html = '';

            // Header
            if (isArray && count > 0) {
                html += '<span class=sf-dump-note>array:' + count + '</span> [';
            } else {
                html += isArray ? '[' : '{';
            }

            if (count === 0) {
                html += closingChar;
                return html;
            }

            html += '<samp data-depth=' + (depth + 1) + ' class=' + sampClass + '>';

            const items = isArray ? value : keys;
            for (let i = 0; i < items.length; i++) {
                html += '\n' + childIndent;

                if (isArray) {
                    html += '<span class=sf-dump-index>' + i + '</span> => ';
                    html += this.plainToHtml(value[i], depth + 1, childIndent);
                } else {
                    html += '"<span class=sf-dump-key>' + this.esc(items[i]) + '</span>" => ';
                    html += this.plainToHtml(value[items[i]], depth + 1, childIndent);
                }
            }

            html += '\n' + indent + '</samp>' + closingChar;
            return html;
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
