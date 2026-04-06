(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    const lazyStore = new Map();
    let lazySeq = 0;
    const escMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
    const escRe = /[&<>"]/g;

    /**
     * Renders JSON variable dumps as interactive HTML trees.
     *
     * Handles three value types:
     *  - Scalars/strings: rendered inline with syntax coloring
     *  - Arrays (plain JSON): rendered as collapsible [ ] trees
     *  - Objects/resources (_vd metadata): rendered as collapsible { } trees with visibility
     *
     * Collapsed nodes are lazy-rendered on first expand.
     */
    class VarDumpRenderer {
        constructor(options) {
            this.expandedDepth = (options && options.expandedDepth !== undefined) ? options.expandedDepth : 0;
        }

        render(value) {
            const pre = document.createElement('pre');
            pre.className = 'sf-dump';
            const savedDepth = this.expandedDepth;
            this.expandedDepth = 0;
            pre.innerHTML = this.toHtml(value, 0) + '\n';
            this.expandedDepth = savedDepth;
            return pre;
        }

        // ── Main dispatcher ──────────────────────────────────────────

        toHtml(value, depth) {
            if (value === null) return '<span class=sf-dump-const>null</span>';
            switch (typeof value) {
                case 'boolean':
                    return '<span class=sf-dump-const>' + value + '</span>';
                case 'number':
                    return '<span class=sf-dump-num>' + this.esc(String(value)) + '</span>';
                case 'string':
                    return '"<span class=sf-dump-str>' + this.esc(value) + '</span>"';
                case 'object':
                    return this.containerToHtml(value, depth);
                default:
                    return this.esc(String(value));
            }
        }

        containerToHtml(value, depth) {
            const isIndexed = Array.isArray(value);
            const vd = !isIndexed && value._vd;
            const cut = !isIndexed && value._cut || 0;
            const keys = isIndexed ? null : Object.keys(value);
            // Filter meta keys for non-indexed — inline to avoid allocation when no meta
            const propKeys = !isIndexed && (vd || cut) ? keys.filter(k => k !== '_vd' && k !== '_cut') : keys;
            const len = isIndexed ? value.length : propKeys.length;
            const total = len + cut;

            if (vd) return this.objectToHtml(value, propKeys, vd, cut, total, depth);
            return this.arrayToHtml(value, isIndexed, propKeys, cut, total, depth);
        }

        // ── Array rendering ──────────────────────────────────────────

        arrayToHtml(value, isIndexed, keys, cut, total, depth) {
            if (total === 0) return '[]';

            const expanded = depth < this.expandedDepth;
            let html = '<span class=sf-dump-note>array:' + total + '</span> [';
            html += '<a class=sf-dump-toggle><span>' + (expanded ? '▼' : '▶') + '</span></a>';

            // Preview
            html += '<span class="sf-dump-preview' + (expanded ? ' sf-dump-hidden' : '') + '"> ';
            html += this.arrayPreview(value, isIndexed, keys, cut) + ' ]</span>';

            if (expanded) {
                html += '<samp class=sf-dump-expanded>';
                html += this.arrayChildren(value, isIndexed, keys, cut, depth);
                html += '</samp>';
            } else {
                const id = ++lazySeq;
                lazyStore.set(id, { v: value, arr: isIndexed, k: keys, c: cut, d: depth, r: this, ed: this.expandedDepth });
                html += '<samp class=sf-dump-compact data-lazy=' + id + '></samp>';
            }
            html += '<span class="sf-dump-close' + (expanded ? '' : ' sf-dump-hidden') + '">]</span>';
            return html;
        }

        arrayPreview(value, isIndexed, keys, cut) {
            const len = isIndexed ? value.length : keys.length;
            const max = Math.min(len, 8);
            const parts = [];
            for (let i = 0; i < max; i++) {
                const k = isIndexed ? i : keys[i];
                const v = isIndexed ? value[i] : value[keys[i]];
                parts.push(isIndexed ? this.previewValue(v) : this.esc(String(k)) + ': ' + this.previewValue(v));
            }
            let result = parts.join(', ');
            if (len > max || cut > 0) result += ', …';
            return result;
        }

        arrayChildren(value, isIndexed, keys, cut, depth) {
            const len = isIndexed ? value.length : keys.length;
            let html = '';
            for (let i = 0; i < len; i++) {
                if (i > 0) html += '\n';
                if (isIndexed) {
                    html += '<span class=sf-dump-index>' + i + '</span> => ';
                    html += this.toHtml(value[i], depth + 1);
                } else {
                    html += '"<span class=sf-dump-key>' + this.esc(keys[i]) + '</span>" => ';
                    html += this.toHtml(value[keys[i]], depth + 1);
                }
            }
            if (cut > 0) html += '\n…' + cut;
            return html;
        }

        // ── Object/resource rendering ────────────────────────────────

        objectToHtml(value, keys, vd, cut, total, depth) {
            const ref = vd[1] || 0;
            const cls = vd[2] || null;
            const prefixes = vd[3] || null;
            const isResource = (vd[0] === 5);

            if (total === 0 && !ref) return (cls ? this.esc(cls) + ' ' : '') + '{}';

            const expanded = depth < this.expandedDepth;
            let html = '';
            let refHtml = '';

            if (isResource) {
                html += '<span class=sf-dump-note>' + this.esc(cls || 'resource') + '</span> {';
            } else {
                if (cls) html += '<span class=sf-dump-note>' + this.esc(cls) + '</span> ';
                html += '{';
                if (ref) refHtml = '<span class=sf-dump-ref>#' + ref + '</span> ';
            }

            if (total === 0) return html + refHtml + '}';

            html += '<a class=sf-dump-toggle>' + refHtml + '<span>' + (expanded ? '▼' : '▶') + '</span></a>';

            // Preview
            html += '<span class="sf-dump-preview' + (expanded ? ' sf-dump-hidden' : '') + '"> ';
            const max = Math.min(keys.length, 8);
            const parts = [];
            for (let i = 0; i < max; i++) {
                parts.push(this.esc(keys[i]) + ': ' + this.previewValue(value[keys[i]]));
            }
            let preview = parts.join(', ');
            if (keys.length > max || cut > 0) preview += ', …';
            html += preview + ' }</span>';

            if (expanded) {
                html += '<samp class=sf-dump-expanded>';
                html += this.objectChildren(value, keys, prefixes, cut, depth);
                html += '</samp>';
            } else {
                const id = ++lazySeq;
                lazyStore.set(id, { v: value, obj: true, k: keys, p: prefixes, c: cut, d: depth, r: this, ed: this.expandedDepth });
                html += '<samp class=sf-dump-compact data-lazy=' + id + '></samp>';
            }
            html += '<span class="sf-dump-close' + (expanded ? '' : ' sf-dump-hidden') + '">}</span>';
            return html;
        }

        objectChildren(value, keys, prefixes, cut, depth) {
            let html = '';
            for (let i = 0; i < keys.length; i++) {
                if (i > 0) html += '\n';
                const p = prefixes ? prefixes[i] : null;
                const k = this.esc(keys[i]);
                if (!p) {
                    html += '+<span class=sf-dump-public title="Public property">' + k + '</span>: ';
                } else if (p === '+') {
                    html += '+"<span class=sf-dump-public title="Runtime added dynamic property">' + k + '</span>": ';
                } else if (p === '~') {
                    html += '<span class=sf-dump-meta>' + k + '</span>: ';
                } else if (p === '*') {
                    html += '#<span class=sf-dump-protected title="Protected property">' + k + '</span>: ';
                } else {
                    html += '-<span class=sf-dump-private title="Private property declared in ' + this.esc(p) + '">' + k + '</span>: ';
                }
                html += this.toHtml(value[keys[i]], depth + 1);
            }
            if (cut > 0) html += '\n…' + cut;
            return html;
        }

        // ── Preview (collapsed inline summary) ──────────────────────

        previewValue(v) {
            if (v === null) return 'null';
            if (typeof v === 'string') return '"' + this.esc(v.length > 40 ? v.substring(0, 40) + '…' : v) + '"';
            if (typeof v === 'boolean') return String(v);
            if (typeof v === 'number') return String(v);
            if (typeof v === 'object') {
                if (v._vd) return (v._vd[2] || '') + ' {…}';
                return Array.isArray(v) ? '[…]' : '{…}';
            }
            return '…';
        }

        // ── Utilities ────────────────────────────────────────────────

        esc(s) {
            return String(s).replace(escRe, m => escMap[m]);
        }
    }
    PhpDebugBar.Widgets.VarDumpRenderer = VarDumpRenderer;

    // ── Lazy expand ──────────────────────────────────────────────────

    function expandLazy(samp) {
        const id = +samp.dataset.lazy;
        delete samp.dataset.lazy;

        const data = lazyStore.get(id);
        if (!data) return;
        lazyStore.delete(id);

        const renderer = data.r;
        const savedDepth = renderer.expandedDepth;
        renderer.expandedDepth = data.ed;

        if (data.obj) {
            samp.innerHTML = renderer.objectChildren(data.v, data.k, data.p, data.c, data.d);
        } else {
            samp.innerHTML = renderer.arrayChildren(data.v, data.arr, data.k, data.c, data.d);
        }

        renderer.expandedDepth = savedDepth;
    }

    // ── Toggle expand/collapse ───────────────────────────────────────

    function togglePreview(samp, expanding) {
        const preview = samp.previousElementSibling;
        const close = samp.nextElementSibling;
        if (preview) preview.classList.toggle('sf-dump-hidden', expanding);
        if (close) close.classList.toggle('sf-dump-hidden', !expanding);
    }

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('a.sf-dump-toggle') || e.target.closest('.sf-dump-preview')?.previousElementSibling;
        if (!toggle) return;

        const pre = toggle.closest('pre.sf-dump');
        if (!pre || pre.id) return;

        const samp = toggle.nextElementSibling?.nextElementSibling;
        if (!samp || samp.tagName !== 'SAMP') return;

        e.preventDefault();
        const isCompact = samp.classList.contains('sf-dump-compact');

        if (isCompact && samp.dataset.lazy) expandLazy(samp);

        if (e.ctrlKey || e.metaKey) {
            if (isCompact) {
                let pending;
                while ((pending = samp.querySelectorAll('[data-lazy]')).length) {
                    pending.forEach(expandLazy);
                }
                samp.querySelectorAll('samp.sf-dump-compact').forEach(function (s) {
                    s.classList.replace('sf-dump-compact', 'sf-dump-expanded');
                    const t = s.previousElementSibling?.previousElementSibling;
                    if (t && t.classList.contains('sf-dump-toggle')) t.lastElementChild.textContent = '▼';
                    togglePreview(s, true);
                });
            } else {
                samp.querySelectorAll('samp.sf-dump-expanded').forEach(function (s) {
                    s.classList.replace('sf-dump-expanded', 'sf-dump-compact');
                    const t = s.previousElementSibling?.previousElementSibling;
                    if (t && t.classList.contains('sf-dump-toggle')) t.lastElementChild.textContent = '▶';
                    togglePreview(s, false);
                });
            }
        }

        samp.classList.toggle('sf-dump-compact', !isCompact);
        samp.classList.toggle('sf-dump-expanded', isCompact);
        toggle.lastElementChild.textContent = isCompact ? '▼' : '▶';
        togglePreview(samp, isCompact);
    });

    // ── JsonVariableListWidget ───────────────────────────────────────

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
