/* global phpdebugbar_hljs */
(function () {
    /**
     * @namespace
     */
    PhpDebugBar.Widgets = {};

    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Replaces spaces with &nbsp; and line breaks with <br>
     *
     * @param {string} text
     * @return {string}
     */
    const htmlize = PhpDebugBar.Widgets.htmlize = function (text) {
        return text.replace(/\n/g, '<br>').replace(/\s/g, '&nbsp;');
    };

    /**
     * Returns a string representation of value, using JSON.stringify
     * if it's an object.
     *
     * @param {object} value
     * @param {boolean} prettify Uses htmlize() if true
     * @return {string}
     */
    const renderValue = PhpDebugBar.Widgets.renderValue = function (value, prettify) {
        if (typeof value !== 'string') {
            if (prettify) {
                return htmlize(JSON.stringify(value, undefined, 2));
            }
            return JSON.stringify(value);
        }
        return value;
    };

    /**
     * Highlights a block of code
     *
     * @param  {string} code
     * @param  {string} lang
     * @return {string}
     */
    const highlight = PhpDebugBar.Widgets.highlight = function (code, lang) {
        if (typeof code === 'string') {
            if (typeof phpdebugbar_hljs === 'undefined') {
                return htmlize(code);
            }
            const hljs = phpdebugbar_hljs.default || phpdebugbar_hljs;
            if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(code, { language: lang }).value;
            }
            return hljs.highlightAuto(code).value;
        }

        if (typeof phpdebugbar_hljs === 'object') {
            const hljs = phpdebugbar_hljs.default || phpdebugbar_hljs;
            if (code.nodeType === Node.ELEMENT_NODE) {
                hljs.highlightElement(code);
            } else if (code.length) {
                for (const element of code) {
                    hljs.highlightElement(element);
                }
            }
        }
        return code;
    };

    /**
     * Creates a <pre> element with a block of code
     *
     * @param  {string} code
     * @param  {string} lang
     * @param  {number} [firstLineNumber] If provided, shows line numbers beginning with the given value.
     * @param  {number} [highlightedLine] If provided, the given line number will be highlighted.
     * @return {string}
     */
    const createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function (code, lang, firstLineNumber, highlightedLine) {
        const pre = document.createElement('pre');
        pre.classList.add(csscls('code-block'));

        // Add a newline to prevent <code> element from vertically collapsing too far if the last
        // code line was empty: that creates problems with the horizontal scrollbar being
        // incorrectly positioned - most noticeable when line numbers are shown.
        const codeElement = document.createElement('code');
        codeElement.textContent = `${code}\n`;
        pre.append(codeElement);

        // Format the code
        if (lang) {
            codeElement.classList.add(`language-${lang}`);
        }
        highlight(codeElement);
        codeElement.classList.remove('hljs');

        // Show line numbers in a list
        if (!Number.isNaN(Number.parseFloat(firstLineNumber))) {
            const lineCount = code.split('\n').length;
            const lineNumbers = document.createElement('ul');
            pre.prepend(lineNumbers);
            const children = Array.from(pre.children);
            for (const child of children) {
                child.classList.add(csscls('numbered-code'));
            }
            for (let i = firstLineNumber; i < firstLineNumber + lineCount; i++) {
                const li = document.createElement('li');
                li.textContent = i;
                lineNumbers.append(li);

                // Add a span with a special class if we are supposed to highlight a line.
                if (highlightedLine === i) {
                    li.classList.add(csscls('highlighted-line'));
                    const span = document.createElement('span');
                    span.innerHTML = '&nbsp;';
                    li.append(span);
                }
            }
        }

        return pre;
    };

    const { getDictValue } = PhpDebugBar.utils;

    // ------------------------------------------------------------------
    // Generic widgets
    // ------------------------------------------------------------------

    /**
     * Displays array element in a <ul> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    class ListWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'ul';
        }

        get className() {
            return csscls('list');
        }

        initialize(options) {
            if (!options.itemRenderer) {
                options.itemRenderer = this.itemRenderer;
            }
            this.set(options);
        }

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.el.innerHTML = '';
                if (!this.has('data')) {
                    return;
                }

                const data = this.get('data');
                for (let i = 0; i < data.length; i++) {
                    const li = document.createElement('li');
                    li.classList.add(csscls('list-item'));
                    this.el.append(li);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        }

        /**
         * Renders the content of a <li> element
         *
         * @param {HTMLElement} li The <li> element
         * @param {object} value An item from the data array
         */
        itemRenderer(li, value) {
            li.innerHTML = renderValue(value);
        }
    }
    PhpDebugBar.Widgets.ListWidget = ListWidget;

    // ------------------------------------------------------------------

    /**
     * Displays object property/value paris in a <dl> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    class KVListWidget extends ListWidget {
        get tagName() {
            return 'dl';
        }

        get className() {
            return csscls('kvlist');
        }

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.el.innerHTML = '';
                if (!this.has('data')) {
                    return;
                }

                for (const [key, value] of Object.entries(this.get('data'))) {
                    const dt = document.createElement('dt');
                    dt.classList.add(csscls('key'));
                    this.el.append(dt);

                    const dd = document.createElement('dd');
                    dd.classList.add(csscls('value'));
                    this.el.append(dd);

                    this.get('itemRenderer')(dt, dd, key, value);
                }
            });
        }

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {HTMLElement} dt The <dt> element
         * @param {HTMLElement} dd The <dd> element
         * @param {string} key Property name
         * @param {object} value Property value
         */
        itemRenderer(dt, dd, key, value) {
            dt.textContent = key;
            dd.innerHTML = htmlize(value);
        }
    }
    PhpDebugBar.Widgets.KVListWidget = KVListWidget;

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     *
     * Options:
     *  - data
     */
    class VariableListWidget extends KVListWidget {
        get className() {
            return csscls('kvlist varlist');
        }

        itemRenderer(dt, dd, key, value) {
            const span = document.createElement('span');
            span.setAttribute('title', key);
            span.textContent = key;
            dt.append(span);

            let v = value && value.value || value;
            if (v && v.length > 100) {
                v = `${v.substr(0, 100)}...`;
            }
            let prettyVal = null;
            dd.textContent = v;
            dd.addEventListener('click', () => {
                if (window.getSelection().type === 'Range') {
                    return '';
                }
                if (dd.classList.contains(csscls('pretty'))) {
                    dd.textContent = v;
                    dd.classList.remove(csscls('pretty'));
                } else {
                    prettyVal = prettyVal || createCodeBlock(value);
                    dd.classList.add(csscls('pretty'));
                    dd.innerHTML = '';
                    dd.append(prettyVal);
                }
            });
        }
    }
    PhpDebugBar.Widgets.VariableListWidget = VariableListWidget;

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables whose contents are HTML; this is useful for showing
     * variable output from VarDumper's HtmlDumper.
     *
     * Options:
     *  - data
     */
    class HtmlVariableListWidget extends KVListWidget {
        get className() {
            return csscls('kvlist htmlvarlist');
        }

        itemRenderer(dt, dd, key, value) {
            const tempElement = document.createElement('i');
            tempElement.innerHTML = key ?? '';
            const span = document.createElement('span');
            span.setAttribute('title', tempElement.textContent);
            span.innerHTML = key ?? '';
            dt.append(span);

            dd.innerHTML = value && value.value || value;

            if (value && value.xdebug_link) {
                const header = document.createElement('span');
                header.classList.add(csscls('filename'));
                header.textContent = value.xdebug_link.filename + (value.xdebug_link.line ? `#${value.xdebug_link.line}` : '');

                if (value.xdebug_link) {
                    const link = document.createElement('a');
                    link.classList.add(csscls('editor-link'));

                    if (value.xdebug_link.ajax) {
                        link.setAttribute('title', value.xdebug_link.url);
                        link.addEventListener('click', () => {
                            fetch(value.xdebug_link.url);
                        });
                    } else {
                        link.setAttribute('href', value.xdebug_link.url);
                    }
                    header.append(link);
                }
                dd.append(header);
            }
        }
    }
    PhpDebugBar.Widgets.HtmlVariableListWidget = HtmlVariableListWidget;

    // ------------------------------------------------------------------

    /**
     * Displays array element in a <table> list, columns keys map
     * useful for showing a multiple values table
     *
     * Options:
     *  - data
     *  - key_map: list of keys to be displayed with an optional label
     *             example: {key1: label1, key2: label2} or [key1, key2]
     */
    class TableVariableListWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'div';
        }

        get className() {
            return csscls('tablevarlist');
        }

        render() {
            this.bindAttr('data', function (data) {
                this.el.innerHTML = '';

                if (!this.has('data')) {
                    return;
                }

                this.table = document.createElement('table');
                this.table.classList.add(csscls('tablevar'));
                this.el.append(this.table);

                const header = document.createElement('tr');
                header.classList.add(csscls('header'));
                const headerFirstCell = document.createElement('td');
                header.append(headerFirstCell);
                this.table.append(header);

                let key_map = data.key_map || { value: 'Value' };

                if (Array.isArray(key_map)) {
                    key_map = Object.fromEntries(key_map.map(k => [k, null]));
                }

                for (const [key, label] of Object.entries(key_map)) {
                    const colTitle = document.createElement('td');
                    colTitle.textContent = label ?? key;
                    header.append(colTitle);

                    if (data.badges && data.badges[key]) {
                        const badge = document.createElement('span');
                        badge.textContent = data.badges[key];
                        badge.classList.add(csscls('badge'));
                        colTitle.append(badge);
                    }
                }

                const self = this;
                if (!data.data) {
                    return;
                }
                for (const [key, values] of Object.entries(data.data)) {
                    const tr = document.createElement('tr');
                    tr.classList.add(csscls('item'));
                    self.table.append(tr);

                    const keyCell = document.createElement('td');
                    keyCell.classList.add(csscls('key'));
                    keyCell.textContent = key;
                    tr.append(keyCell);

                    if (typeof values !== 'object' || values === null) {
                        const valueCell = document.createElement('td');
                        valueCell.classList.add(csscls('value'));
                        valueCell.textContent = values ?? '';
                        tr.append(valueCell);
                        continue;
                    }

                    for (const key of Object.keys(key_map)) {
                        const valueCell = document.createElement('td');
                        valueCell.classList.add(csscls('value'));
                        valueCell.textContent = values[key] ?? '';
                        tr.append(valueCell);
                    }

                    if (values.xdebug_link) {
                        const editorCell = document.createElement('td');
                        editorCell.classList.add(csscls('editor'));
                        tr.append(editorCell);

                        const filename = document.createElement('span');
                        filename.classList.add(csscls('filename'));
                        filename.textContent = values.xdebug_link.filename + (values.xdebug_link.line ? `#${values.xdebug_link.line}` : '');
                        editorCell.append(filename);

                        const link = document.createElement('a');
                        link.classList.add(csscls('editor-link'));
                        if (values.xdebug_link.ajax) {
                            link.setAttribute('title', values.xdebug_link.url);
                            link.addEventListener('click', () => {
                                fetch(values.xdebug_link.url);
                            });
                        } else {
                            link.setAttribute('href', values.xdebug_link.url);
                        }
                        filename.append(link);

                        if (!data.xdebug_link) {
                            data.xdebug_link = true;
                            header.append(document.createElement('td'));
                        }
                    }
                }

                if (!data.summary) {
                    return;
                }

                const summaryTr = document.createElement('tr');
                summaryTr.classList.add(csscls('summary'));
                self.table.append(summaryTr);

                const summaryKeyCell = document.createElement('td');
                summaryKeyCell.classList.add(csscls('key'));
                summaryTr.append(summaryKeyCell);

                if (typeof data.summary !== 'object' || data.summary === null) {
                    const summaryValueCell = document.createElement('td');
                    summaryValueCell.classList.add(csscls('value'));
                    summaryValueCell.textContent = data.summary ?? '';
                    summaryTr.append(summaryValueCell);
                } else {
                    for (const key of Object.keys(key_map)) {
                        const summaryValueCell = document.createElement('td');
                        summaryValueCell.classList.add(csscls('value'));
                        summaryValueCell.textContent = data.summary[key] ?? '';
                        summaryTr.append(summaryValueCell);
                    }
                }

                if (data.xdebug_link) {
                    summaryTr.append(document.createElement('td'));
                }
            });
        }
    }
    PhpDebugBar.Widgets.TableVariableListWidget = TableVariableListWidget;

    // ------------------------------------------------------------------

    /**
     * Iframe widget
     *
     * Options:
     *  - data
     */
    class IFrameWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'iframe';
        }

        get className() {
            return csscls('iframe');
        }

        render() {
            this.el.setAttribute('seamless', 'seamless');
            this.el.setAttribute('border', '0');
            this.el.setAttribute('width', '100%');
            this.el.setAttribute('height', '100%');

            this.bindAttr('data', function (url) {
                this.el.setAttribute('src', url);
            });
        }
    }
    PhpDebugBar.Widgets.IFrameWidget = IFrameWidget;

    // ------------------------------------------------------------------
    // Collector specific widgets
    // ------------------------------------------------------------------

    /**
     * Widget for the MessagesCollector
     *
     * Uses ListWidget under the hood
     *
     * Options:
     *  - data
     */
    class MessagesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('messages');
        }

        render() {
            const self = this;

            this.list = new ListWidget({ itemRenderer(li, value) {
                let val;
                if (value.message_html) {
                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    val.innerHTML = value.message_html;
                    li.append(val);

                    // get the id for sfDump
                    const pre = val.querySelector('pre.sf-dump[id]');
                    const sfDumpId = pre ? pre.id : null;

                    // Run sfDump if needed
                    if (sfDumpId && typeof window.Sfdump === 'function') {
                        try {
                            window.Sfdump(sfDumpId, { maxDepth: 0 });
                        } catch (e) {
                            console.error('Sfdump failed:', e);
                        }
                    }
                } else {
                    let m = value.message;
                    if (m.length > 100) {
                        m = `${m.substr(0, 100)}...`;
                    }

                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    val.textContent = m;
                    li.append(val);

                    if (!value.is_string || value.message.length > 100) {
                        let prettyVal = value.message;
                        if (!value.is_string) {
                            prettyVal = null;
                        }
                        li.style.cursor = 'pointer';
                        li.addEventListener('click', () => {
                            if (window.getSelection().type === 'Range') {
                                return '';
                            }
                            if (val.classList.contains(csscls('pretty'))) {
                                val.textContent = m;
                                val.classList.remove(csscls('pretty'));
                            } else {
                                prettyVal = prettyVal || createCodeBlock(value.message, 'php');
                                val.classList.add(csscls('pretty'));
                                val.innerHTML = '';
                                val.append(prettyVal);
                            }
                        });
                    }
                }
                if (value.xdebug_link) {
                    const header = document.createElement('span');
                    header.classList.add(csscls('filename'));
                    header.textContent = value.xdebug_link.filename + (value.xdebug_link.line ? `#${value.xdebug_link.line}` : '');

                    if (value.xdebug_link) {
                        const link = document.createElement('a');
                        link.classList.add(csscls('editor-link'));

                        if (value.xdebug_link.ajax) {
                            link.setAttribute('title', value.xdebug_link.url);
                            link.addEventListener('click', () => {
                                fetch(value.xdebug_link.url);
                            });
                        } else {
                            link.setAttribute('href', value.xdebug_link.url);
                        }
                        header.append(link);
                    }
                    li.prepend(header);
                }
                if (value.collector) {
                    const collector = document.createElement('span');
                    collector.classList.add(csscls('collector'));
                    collector.textContent = value.collector;
                    li.prepend(collector);
                }
                if (value.label) {
                    val.classList.add(csscls(value.label));
                    const label = document.createElement('span');
                    label.classList.add(csscls('label'));
                    label.textContent = value.label;
                    li.prepend(label);
                }
            } });

            this.el.append(this.list.el);

            this.toolbar = document.createElement('div');
            this.toolbar.classList.add(csscls('toolbar'));
            this.toolbar.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-search"></i>';
            this.el.append(this.toolbar);

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.name = 'search';
            searchInput.setAttribute('aria-label', 'Search');
            searchInput.placeholder = 'Search';
            searchInput.addEventListener('change', function () {
                self.set('search', this.value);
            });
            this.toolbar.append(searchInput);

            this.bindAttr('data', function (data) {
                this.set({ excludelabel: [], excludecollector: [], search: '' });

                const filters = this.toolbar.querySelectorAll(`.${csscls('filter')}`);
                for (const filter of filters) {
                    filter.remove();
                }

                const labels = []; const collectors = []; const self = this;
                const createFilterItem = function (type, value) {
                    const link = document.createElement('a');
                    link.classList.add(csscls('filter'));
                    link.classList.add(csscls(type));
                    link.textContent = value;
                    link.setAttribute('rel', value);
                    link.addEventListener('click', function () {
                        self.onFilterClick(this, type);
                    });
                    self.toolbar.append(link);
                };

                data.forEach((item) => {
                    if (!labels.includes(item.label || 'none')) {
                        labels.push(item.label || 'none');
                    }

                    if (!collectors.includes(item.collector || 'none')) {
                        collectors.push(item.collector || 'none');
                    }
                });

                if (labels.length > 1) {
                    labels.forEach(label => createFilterItem('label', label));
                }

                if (collectors.length === 1) {
                    return;
                }

                const spacer = document.createElement('a');
                spacer.classList.add(csscls('filter'));
                spacer.style.visibility = 'hidden';
                self.toolbar.append(spacer);
                collectors.forEach(collector => createFilterItem('collector', collector));
            });

            this.bindAttr(['excludelabel', 'excludecollector', 'search'], function () {
                const excludelabel = this.get('excludelabel') || [];
                const excludecollector = this.get('excludecollector') || [];
                const search = this.get('search');
                let caseless = false;
                const fdata = [];

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                this.get('data').forEach((item) => {
                    const message = caseless ? item.message.toLowerCase() : item.message;

                    if (
                        !excludelabel.includes(item.label || undefined)
                        && !excludecollector.includes(item.collector || undefined)
                        && (!search || message.includes(search))
                    ) {
                        fdata.push(item);
                    }
                });

                this.list.set('data', fdata);
            });
        }

        onFilterClick(el, type) {
            el.classList.toggle(csscls('excluded'));

            const excluded = [];
            const selector = `.${csscls('filter')}.${csscls('excluded')}.${csscls(type)}`;
            const excludedFilters = this.toolbar.querySelectorAll(selector);
            for (const filter of excludedFilters) {
                excluded.push(filter.rel === 'none' || !filter.rel ? undefined : filter.rel);
            }

            this.set(`exclude${type}`, excluded);
        }
    }
    PhpDebugBar.Widgets.MessagesWidget = MessagesWidget;

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    class TimelineWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'ul';
        }

        get className() {
            return csscls('timeline');
        }

        render() {
            this.bindAttr('data', function (data) {
                // ported from php DataFormatter
                const formatDuration = function (seconds) {
                    if (seconds < 0.001) {
                        return `${(seconds * 1000000).toFixed()}μs`;
                    } else if (seconds < 0.1) {
                        return `${(seconds * 1000).toFixed(2)}ms`;
                    } else if (seconds < 1) {
                        return `${(seconds * 1000).toFixed()}ms`;
                    }
                    return `${(seconds).toFixed(2)}s`;
                };

                // ported from php DataFormatter
                const formatBytes = function formatBytes(size) {
                    if (size === 0 || size === null) {
                        return '0B';
                    }

                    const sign = size < 0 ? '-' : '';
                    const absSize2 = Math.abs(size);
                    const base = Math.log(absSize2) / Math.log(1024);
                    const suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    return sign + (Math.round(1024 ** (base - Math.floor(base)) * 100) / 100) + suffixes[Math.floor(base)];
                };

                this.el.innerHTML = '';
                if (data.measures) {
                    let aggregate = {};

                    for (let i = 0; i < data.measures.length; i++) {
                        const measure = data.measures[i];
                        const group = measure.group || measure.label;

                        if (!aggregate[group]) {
                            aggregate[group] = { count: 0, duration: 0, memory: 0 };
                        }

                        aggregate[group].count += 1;
                        aggregate[group].duration += measure.duration;
                        aggregate[group].memory += (measure.memory || 0);

                        const m = document.createElement('div');
                        m.classList.add(csscls('measure'));

                        const li = document.createElement('li');
                        const left = (measure.relative_start * 100 / data.duration).toFixed(2);
                        const width = Math.min((measure.duration * 100 / data.duration).toFixed(2), 100 - left);

                        const valueSpan = document.createElement('span');
                        valueSpan.classList.add(csscls('value'));
                        valueSpan.style.left = `${left}%`;
                        valueSpan.style.width = `${width}%`;
                        m.append(valueSpan);

                        const labelSpan = document.createElement('span');
                        labelSpan.classList.add(csscls('label'));
                        labelSpan.textContent = measure.label.replace(/\s+/g, ' ') + (measure.duration ? ` (${measure.duration_str}${measure.memory ? `/${measure.memory_str}` : ''})` : '');
                        m.append(labelSpan);

                        if (measure.collector) {
                            const collectorSpan = document.createElement('span');
                            collectorSpan.classList.add(csscls('collector'));
                            collectorSpan.textContent = measure.collector;
                            m.append(collectorSpan);
                        }

                        li.append(m);
                        this.el.append(li);

                        if (measure.params && Object.keys(measure.params).length > 0) {
                            const table = document.createElement('table');
                            table.classList.add(csscls('params'));
                            table.style.display = 'none';
                            table.innerHTML = '<tr><th colspan="2">Params</th></tr>';

                            for (const key in measure.params) {
                                if (typeof measure.params[key] !== 'function') {
                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `<td class="${csscls('name')}">${key}</td><td class="${csscls('value')}"><pre><code>${measure.params[key]}</code></pre></td>`;
                                    table.append(tr);
                                }
                            }
                            li.append(table);

                            li.style.cursor = 'pointer';
                            li.addEventListener('click', function () {
                                if (window.getSelection().type === 'Range') {
                                    return '';
                                }
                                const table = this.querySelector('table');
                                if (table.style.display !== 'none') {
                                    table.style.display = 'none';
                                } else {
                                    table.style.display = '';
                                }
                            });

                            li.addEventListener('click', (event) => {
                                if (event.target.closest('.sf-dump')) {
                                    event.stopPropagation();
                                }
                            });
                        }
                    }

                    // convert to array and sort by duration
                    aggregate = Object.entries(aggregate).map(([label, data]) => ({
                        label,
                        data
                    })).sort((a, b) => {
                        return b.data.duration - a.data.duration;
                    });

                    // build table and add
                    const aggregateTable = document.createElement('table');
                    aggregateTable.classList.add(csscls('params'));

                    for (const agg of aggregate) {
                        const width = Math.min((agg.data.duration * 100 / data.duration).toFixed(2), 100);

                        const tempElement = document.createElement('i');
                        tempElement.textContent = agg.label.replace(/\s+/g, ' ');
                        const escapedLabel = tempElement.innerHTML;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td class="${csscls('name')}">${
                            agg.data.count} x ${escapedLabel} (${width}%)</td><td class="${csscls('value')}">`
                            + `<div class="${csscls('measure')}">`
                            + `<span class="${csscls('value')}"></span>`
                            + `<span class="${csscls('label')}">${formatDuration(agg.data.duration)}${agg.data.memory ? `/${formatBytes(agg.data.memory)}` : ''}</span>`
                            + '</div></td>';
                        aggregateTable.append(tr);

                        const lastValueSpan = tr.querySelector(`span.${csscls('value')}`);
                        lastValueSpan.style.width = `${width}%`;
                    }

                    const lastLi = document.createElement('li');
                    lastLi.append(aggregateTable);
                    this.el.append(lastLi);
                }
            });
        }
    }
    PhpDebugBar.Widgets.TimelineWidget = TimelineWidget;

    // ------------------------------------------------------------------

    /**
     * Widget for the displaying exceptions
     *
     * Options:
     *  - data
     */
    class ExceptionsWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('exceptions');
        }

        render() {
            this.list = new ListWidget({ itemRenderer(li, e) {
                const messageSpan = document.createElement('span');
                messageSpan.classList.add(csscls('message'));
                messageSpan.textContent = e.message;

                if (e.count > 1) {
                    const badge = document.createElement('span');
                    badge.classList.add(csscls('badge'));
                    badge.textContent = `${e.count}x`;
                    messageSpan.prepend(badge);
                }
                li.append(messageSpan);

                if (e.file) {
                    const header = document.createElement('span');
                    header.classList.add(csscls('filename'));
                    header.textContent = `${e.file}#${e.line}`;

                    if (e.xdebug_link) {
                        const link = document.createElement('a');
                        link.classList.add(csscls('editor-link'));

                        if (e.xdebug_link.ajax) {
                            link.setAttribute('title', e.xdebug_link.url);
                            link.addEventListener('click', () => {
                                fetch(e.xdebug_link.url);
                            });
                        } else {
                            link.setAttribute('href', e.xdebug_link.url);
                        }
                        header.append(link);
                    }
                    li.append(header);
                }

                if (e.type) {
                    const typeSpan = document.createElement('span');
                    typeSpan.classList.add(csscls('type'));
                    typeSpan.textContent = e.type;
                    li.append(typeSpan);
                }

                if (e.surrounding_lines) {
                    const startLine = (e.line - 3) <= 0 ? 1 : e.line - 3;
                    const pre = createCodeBlock(e.surrounding_lines.join(''), 'php', startLine, e.line);
                    pre.classList.add(csscls('file'));
                    li.append(pre);

                    if (!e.stack_trace_html) {
                        // This click event makes the var-dumper hard to use.
                        li.addEventListener('click', () => {
                            if (pre.style.display !== 'none') {
                                pre.style.display = 'none';
                            } else {
                                pre.style.display = '';
                            }
                        });
                    }
                }

                if (e.stack_trace_html) {
                    const trace = document.createElement('span');
                    trace.classList.add(csscls('filename'));
                    trace.innerHTML = e.stack_trace_html;

                    const samps = trace.querySelectorAll('samp[data-depth="1"]');
                    for (const samp of samps) {
                        samp.classList.remove('sf-dump-expanded');
                        samp.classList.add('sf-dump-compact');

                        const note = samp.parentElement.querySelector('>.sf-dump-note');
                        if (note) {
                            note.innerHTML = `${note.innerHTML.replace(/^array:/, '<span class="sf-dump-key">Stack Trace:</span> ')} files`;
                        }
                    }
                    li.append(trace);
                } else if (e.stack_trace) {
                    e.stack_trace.split('\n').forEach((trace) => {
                        const traceLine = document.createElement('div');
                        const filename = document.createElement('span');
                        filename.classList.add(csscls('filename'));
                        filename.textContent = trace;
                        traceLine.append(filename);
                        li.append(traceLine);
                    });
                }
            } });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                this.list.set('data', data);
                if (data.length === 1) {
                    const firstChild = this.list.el.children[0];
                    if (firstChild) {
                        const file = firstChild.querySelector(`.${csscls('file')}`);
                        if (file) {
                            file.style.display = '';
                        }
                    }
                }
            });
        }
    }
    PhpDebugBar.Widgets.ExceptionsWidget = ExceptionsWidget;

    /**
     * Displays datasets in a table
     *
     */
    class DatasetWidget extends PhpDebugBar.Widget {
        initialize(options) {
            if (!options.itemRenderer) {
                options.itemRenderer = this.itemRenderer;
            }
            this.set(options);
            this.set('autoshow', null);
            this.set('id', null);
            this.set('sort', localStorage.getItem('debugbar-history-sort') || 'asc');
            this.el.classList.add(csscls('dataset-history'));

            this.renderHead();
        }

        renderHead() {
            this.el.innerHTML = '';

            this.actions = document.createElement('div');
            this.actions.classList.add(csscls('dataset-actions'));
            this.el.append(this.actions);

            const self = this;
            const debugbar = self.get('debugbar');

            this.autoshow = document.createElement('input');
            this.autoshow.type = 'checkbox';
            this.autoshow.addEventListener('click', function () {
                if (debugbar.ajaxHandler) {
                    debugbar.ajaxHandler.setAutoShow(this.checked);
                }
                if (debugbar.controls.__settings) {
                    debugbar.controls.__settings.get('widget').set('autoshow', this.autoShow);
                }
            });

            const autoshowLabel = document.createElement('label');
            autoshowLabel.textContent = 'Autoshow';
            autoshowLabel.append(this.autoshow);
            this.actions.append(autoshowLabel);

            this.clearbtn = document.createElement('a');
            this.clearbtn.textContent = 'Clear';
            this.actions.append(this.clearbtn);
            this.clearbtn.addEventListener('click', () => {
                self.table.innerHTML = '';
            });

            this.showBtn = document.createElement('a');
            this.showBtn.textContent = 'Show all';
            this.actions.append(this.showBtn);
            this.showBtn.addEventListener('click', () => {
                self.searchInput.value = null;
                self.methodInput.value = null;
                self.set('search', null);
                self.set('method', null);
            });

            this.methodInput = document.createElement('select');
            this.methodInput.name = 'method';
            this.methodInput.style.width = '100px';
            this.methodInput.innerHTML = '<option>(method)</option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option>';
            this.methodInput.addEventListener('change', function () {
                self.set('method', this.value);
            });
            this.actions.append(this.methodInput);

            this.searchInput = document.createElement('input');
            this.searchInput.type = 'text';
            this.searchInput.name = 'search';
            this.searchInput.setAttribute('aria-label', 'Search');
            this.searchInput.placeholder = 'Search';
            this.searchInput.addEventListener('input', function () {
                self.set('search', this.value);
            });
            this.actions.append(this.searchInput);

            this.table = document.createElement('tbody');

            const tableWrapper = document.createElement('table');
            const thead = document.createElement('thead');
            const theadTr = document.createElement('tr');

            const th1 = document.createElement('th');
            th1.style.width = '30px';
            theadTr.append(th1);

            const th2 = document.createElement('th');
            th2.textContent = 'Date ↕';
            th2.style.width = '175px';
            th2.addEventListener('click', () => {
                self.set('sort', self.get('sort') === 'asc' ? 'desc' : 'asc');
                localStorage.setItem('debugbar-history-sort', self.get('sort'));
            });
            theadTr.append(th2);

            const th3 = document.createElement('th');
            th3.textContent = 'Method';
            th3.style.width = '80px';
            theadTr.append(th3);

            const th4 = document.createElement('th');
            th4.textContent = 'URL';
            theadTr.append(th4);

            const th5 = document.createElement('th');
            th5.setAttribute('width', '40%');
            th5.textContent = 'Data';
            theadTr.append(th5);

            thead.append(theadTr);
            tableWrapper.append(thead);
            tableWrapper.append(this.table);
            this.el.append(tableWrapper);
        }

        renderDatasets() {
            this.table.innerHTML = '';
            const self = this;
            const datasets = this.get('data');
            if (!datasets) {
                return;
            }
            for (const [_, data] of Object.entries(datasets)) {
                if (!data.__meta) {
                    continue;
                }

                self.get('itemRenderer')(self, data);
            }
        }

        render() {
            this.bindAttr('data', function () {
                if (this.get('autoshow') === null && this.get('debugbar').ajaxHandler) {
                    this.set('autoshow', this.get('debugbar').ajaxHandler.autoShow);
                }

                if (!this.has('data')) {
                    return;
                }

                // Render the latest item
                const datasets = this.get('data');
                const data = datasets[Object.keys(datasets)[Object.keys(datasets).length - 1]];
                if (!data.__meta) {
                    return;
                }

                this.get('itemRenderer')(this, data);
            });
            this.bindAttr(['itemRenderer', 'search', 'method', 'sort'], function () {
                this.renderDatasets();
            });
            this.bindAttr('autoshow', function () {
                const autoshow = this.get('autoshow');
                this.autoshow.checked = autoshow;
            });
            this.bindAttr('id', function () {
                const id = this.get('id');
                const activeClass = csscls('active');
                const actives = this.table.querySelectorAll(`.${activeClass}`);
                for (const active of actives) {
                    active.classList.remove(activeClass);
                }
                const targetRow = this.table.querySelector(`tr[data-id="${id}"]`);
                if (targetRow) {
                    targetRow.classList.add(activeClass);
                }
            });
        }

        /**
         * Renders the content of a dataset item
         *
         * @param {object} value An item from the data array
         */
        itemRenderer(widget, data) {
            const meta = data.__meta;

            const badgesTd = document.createElement('td');
            const tr = document.createElement('tr');

            if (widget.get('sort') === 'asc') {
                widget.table.append(tr);
            } else {
                widget.table.prepend(tr);
            }

            const clickHandler = function () {
                const debugbar = widget.get('debugbar');
                debugbar.showDataSet(meta.id, debugbar.datesetTitleFormater.format('', data, meta.suffix, meta.nb));

                const activeClass = csscls('active');
                const actives = widget.table.querySelectorAll(`.${activeClass}`);
                for (const active of actives) {
                    active.classList.remove(activeClass);
                }
                tr.classList.add(activeClass);

                const tab = this.dataset.tab;
                if (tab) {
                    debugbar.showTab(tab);
                }
            };

            tr.setAttribute('data-id', meta.id);
            tr.style.cursor = 'pointer';
            tr.classList.add(csscls('table-row'));

            const nbTd = document.createElement('td');
            nbTd.textContent = `#${meta.nb}`;
            nbTd.addEventListener('click', clickHandler);
            tr.append(nbTd);

            const datetimeTd = document.createElement('td');
            datetimeTd.textContent = meta.datetime;
            datetimeTd.addEventListener('click', clickHandler);
            tr.append(datetimeTd);

            const methodTd = document.createElement('td');
            methodTd.textContent = meta.method;
            methodTd.addEventListener('click', clickHandler);
            tr.append(methodTd);

            const uriTd = document.createElement('td');
            uriTd.textContent = meta.uri + (meta.suffix ? ` ${meta.suffix}` : '');
            uriTd.addEventListener('click', clickHandler);
            tr.append(uriTd);

            const debugbar = widget.get('debugbar');
            for (let [key, def] of Object.entries(debugbar.dataMap)) {
                const d = getDictValue(data, def[0], def[1]);
                if (key.includes(':')) {
                    const parts = key.split(':');
                    key = parts[0];
                    const subkey = parts[1];

                    if (subkey === 'badge' && d > 0) {
                        const control = debugbar.getControl(key);
                        const link = document.createElement('a');
                        link.setAttribute('title', control.get('title'));
                        link.dataset.tab = key;

                        if (control.icon) {
                            link.append(control.icon.cloneNode(true));
                        }
                        if (control.badge) {
                            const badgeClone = control.badge.cloneNode(true);
                            badgeClone.style.display = 'inline-block';
                            badgeClone.textContent = d;
                            link.append(badgeClone);
                        }
                        badgesTd.append(link);
                        link.addEventListener('click', clickHandler);
                    } else if (subkey === 'tooltip') {
                        debugbar.getControl(key).set('tooltip', d);
                    }
                }
            }
            tr.append(badgesTd);

            if (debugbar.activeDatasetId === meta.id) {
                tr.classList.add(csscls('active'));
            }

            const search = widget.get('search');
            const method = widget.get('method');
            if ((search && !meta.uri.includes(search)) || (method && meta.method !== method)) {
                tr.style.display = 'none';
            }
        }
    }
    PhpDebugBar.Widgets.DatasetWidget = DatasetWidget;
})();
