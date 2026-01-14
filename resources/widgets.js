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
     * @param  {string|null} lang
     * @return {string}
     */
    const highlight = PhpDebugBar.Widgets.highlight = function (code, lang) {
        if (typeof phpdebugbar_hljs === 'undefined') {
            return htmlize(code);
        }

        const hljs = phpdebugbar_hljs;
        if (lang && hljs.getLanguage(lang)) {
            return hljs.highlight(code, { language: lang }).value;
        }

        return hljs.highlightAuto(code).value;
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
        codeElement.innerHTML = highlight(`${code}\n`, lang);
        pre.append(codeElement);

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
                } else {
                    const m = value.message;

                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    val.textContent = m;
                    val.classList.add(csscls('truncated'));
                    li.append(val);

                    if (!value.is_string || val.scrollWidth > val.clientWidth) {
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
                                val.classList.add(csscls('truncated'));
                            } else {
                                prettyVal = prettyVal || createCodeBlock(value.message, 'php');
                                val.classList.add(csscls('pretty'));
                                val.classList.remove(csscls('truncated'));
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
                if (value.context && Object.keys(value.context).length > 0) {
                    const contextCount = document.createElement('span');
                    contextCount.setAttribute('title', 'Context');
                    contextCount.classList.add(csscls('context-count'));
                    contextCount.textContent = Object.keys(value.context).length;
                    li.prepend(contextCount);

                    const contextTable = document.createElement('table');
                    contextTable.classList.add(csscls('params'));
                    contextTable.hidden = true;
                    contextTable.innerHTML = '<tr><th colspan="2">Context</th></tr>';

                    for (const key in value.context) {
                        if (typeof value.context[key] !== 'function') {
                            const tr = document.createElement('tr');
                            const td1 = document.createElement('td');
                            td1.classList.add(csscls('name'));
                            td1.textContent = key;
                            tr.append(td1);

                            const td2 = document.createElement('td');
                            td2.classList.add(csscls('value'));
                            td2.innerHTML = value.context[key];
                            tr.append(td2);

                            contextTable.append(tr);
                        }
                    }
                    li.append(contextTable);

                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        contextTable.hidden = !contextTable.hidden;
                    });
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
                            table.hidden = true;
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
                                if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                                    return '';
                                }
                                const table = this.querySelector('table');
                                table.hidden = !table.hidden;
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
                    pre.hidden = true;
                    li.append(pre);

                    // This click event makes the var-dumper hard to use.
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        pre.hidden = !pre.hidden;
                    });
                }

                if (e.stack_trace_html) {
                    const trace = document.createElement('span');
                    trace.classList.add(csscls('filename'));
                    trace.innerHTML = e.stack_trace_html;

                    const samps = trace.querySelectorAll('samp[data-depth="1"]');
                    for (const samp of samps) {
                        samp.classList.remove('sf-dump-expanded');
                        samp.classList.add('sf-dump-compact');

                        const note = samp.parentElement.querySelector(':scope > .sf-dump-note');
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
                            file.hidden = false;
                        }
                    }
                }
            });
        }
    }
    PhpDebugBar.Widgets.ExceptionsWidget = ExceptionsWidget;

    // ------------------------------------------------------------------

    /**
     * Dataset Switcher Widget
     *
     * Displays a compact badge showing the current request with a dropdown
     * to switch between different datasets
     */
    class DatasetWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('datasets-switcher-widget');
        }

        initialize(options) {
            this.set(options);

            const self = this;
            const debugbar = this.get('debugbar');

            // Create badge
            this.badge = document.createElement('div');
            this.badge.classList.add(csscls('datasets-badge'));
            this.badge.hidden = true;

            this.badgeCount = document.createElement('span');
            this.badgeCount.classList.add(csscls('datasets-badge-count'));
            this.badge.append(this.badgeCount);

            this.badgeUrl = document.createElement('span');
            this.badgeUrl.classList.add(csscls('datasets-badge-url'));
            this.badge.append(this.badgeUrl);

            // Create dropdown panel
            this.panel = document.createElement('div');
            this.panel.classList.add(csscls('datasets-panel'));
            this.panel.hidden = true;
            // Copy theme from debugbar to panel for CSS variable inheritance
            if (debugbar.el) {
                this.panel.setAttribute('data-theme', debugbar.el.getAttribute('data-theme'));
            }

            // Panel toolbar with filters
            const toolbar = document.createElement('div');
            toolbar.classList.add(csscls('datasets-panel-toolbar'));

            // Autoshow checkbox
            const autoshowLabel = document.createElement('label');
            autoshowLabel.classList.add(csscls('datasets-autoshow'));
            this.autoshowCheckbox = document.createElement('input');
            this.autoshowCheckbox.type = 'checkbox';
            // Get initial value from localStorage or ajaxHandler or default to true
            const storedAutoShow = localStorage.getItem('phpdebugbar-ajaxhandler-autoshow');
            this.autoshowCheckbox.checked = storedAutoShow !== null
                ? storedAutoShow === '1'
                : (debugbar.ajaxHandler ? debugbar.ajaxHandler.autoShow : true);
            this.autoshowCheckbox.addEventListener('change', function () {
                if (debugbar.ajaxHandler) {
                    debugbar.ajaxHandler.setAutoShow(this.checked);
                }
                // Update settings widget if it exists
                if (debugbar.controls.__settings) {
                    debugbar.controls.__settings.get('widget').set('autoshow', this.checked);
                }
                // Update dataset tab widget if it exists
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.checked);
                }
            });
            autoshowLabel.append(this.autoshowCheckbox);
            autoshowLabel.append(document.createTextNode(' Autoshow'));
            toolbar.append(autoshowLabel);

            // Refresh button
            this.refreshBtn = document.createElement('a');
            this.refreshBtn.classList.add(csscls('datasets-refresh-btn'));
            this.refreshBtn.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-refresh"></i>';
            this.refreshBtn.title = 'Auto-scan for new datasets';
            this.isScanning = false;
            this.refreshBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent panel from closing
                if (this.isScanning) {
                    // Stop scanning
                    this.isScanning = false;
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Auto-scan for new datasets';
                } else {
                    // Start scanning
                    this.isScanning = true;
                    this.refreshBtn.classList.add(csscls('active'));
                    this.refreshBtn.title = 'Stop auto-scanning';
                    // Start first scan immediately
                    this.scanForNewDatasets();
                }
            });
            toolbar.append(this.refreshBtn);

            // Clear button
            const clearBtn = document.createElement('a');
            clearBtn.classList.add(csscls('datasets-clear-btn'));
            clearBtn.textContent = 'Clear';
            clearBtn.addEventListener('click', () => {
                const currentId = debugbar.activeDatasetId;
                const currentDataset = debugbar.datasets[currentId];
                debugbar.datasets = {};
                if (currentDataset) {
                    debugbar.addDataSet(currentDataset, currentId, currentDataset.__meta.suffix, true);
                }
                this.panel.hidden = true;
            });
            toolbar.append(clearBtn);

            // Search input
            this.searchInput = document.createElement('input');
            this.searchInput.type = 'search';
            this.searchInput.placeholder = 'Search';
            this.searchInput.classList.add(csscls('datasets-search'));
            this.searchInput.addEventListener('input', () => {
                self.applySearchFilter();
            });
            toolbar.append(this.searchInput);

            this.panel.append(toolbar);

            this.list = document.createElement('div');
            this.list.classList.add(csscls('datasets-list'));
            this.panel.append(this.list);

            this.el.append(this.badge);
            document.body.append(this.panel);

            // Position panel relative to badge
            const positionPanel = () => {
                const badgeRect = this.badge.getBoundingClientRect();

                // Calculate available space above and below the badge
                const spaceAbove = badgeRect.top;
                const spaceBelow = window.innerHeight - badgeRect.bottom;
                const showBelow = spaceBelow > spaceAbove;

                this.panel.style.position = 'fixed';
                this.panel.style.right = `${window.innerWidth - badgeRect.right}px`;
                this.panel.style.left = 'auto';

                if (showBelow) {
                    // Show panel below badge
                    this.panel.style.top = `${badgeRect.bottom}px`;
                    this.panel.style.bottom = 'auto';
                    this.panel.style.maxHeight = `${spaceBelow}px`;
                } else {
                    // Show panel above badge (no gap)
                    this.panel.style.bottom = `${window.innerHeight - badgeRect.top}px`;
                    this.panel.style.top = 'auto';
                    this.panel.style.maxHeight = `${spaceAbove}px`;
                }

                this.refreshBtn.hidden = !debugbar.openHandler;
            };

            // Toggle panel on click
            this.badge.addEventListener('click', (e) => {
                if (e.target !== this.panel && !this.panel.contains(e.target)) {
                    if (this.panel.hidden) {
                        positionPanel();
                    }
                    this.panel.hidden = !this.panel.hidden;
                }
            });

            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.badge.contains(e.target) && !this.panel.contains(e.target)) {
                    this.panel.hidden = true;
                }
            });
        }

        render() {
            // Bind to data changes
            this.bindAttr('data', function () {
                this.updateBadge();
            });

            this.bindAttr('activeId', function () {
                this.updateBadge();
            });

            // Bind to autoshow changes from settings
            this.bindAttr('autoshow', function () {
                if (this.autoshowCheckbox) {
                    this.autoshowCheckbox.checked = this.get('autoshow');
                }
            });
        }

        updateBadge() {
            const debugbar = this.get('debugbar');
            const datasets = this.get('data') || debugbar.datasets;
            const activeId = this.get('activeId') || debugbar.activeDatasetId;

            if (!datasets) {
                this.badge.hidden = true;
                return;
            }

            const datasetCount = Object.keys(datasets).length;

            if (datasetCount >= 1) {
                this.badge.hidden = false;

                // Update the current URL display
                const currentDataset = datasets[activeId];
                if (currentDataset && currentDataset.__meta) {
                    const uri = currentDataset.__meta.uri || '';
                    const method = currentDataset.__meta.method || 'GET';
                    this.badgeUrl.textContent = `${method} ${uri}`;
                }

                // Only show count badge if more than 1 request
                if (datasetCount > 1) {
                    this.badgeCount.textContent = datasetCount;
                    this.badgeCount.hidden = false;
                } else {
                    this.badgeCount.hidden = true;
                }

                // Clear and rebuild the panel list
                this.list.innerHTML = '';

                // Get all datasets and sort by utime (latest on top)
                const datasetIds = Object.keys(datasets).sort((a, b) => {
                    const utimeA = datasets[a].__meta?.utime || 0;
                    const utimeB = datasets[b].__meta?.utime || 0;
                    return utimeB - utimeA; // Descending order (latest first)
                });

                for (const datasetId of datasetIds) {
                    const dataset = datasets[datasetId];
                    const item = document.createElement('div');
                    item.classList.add(csscls('datasets-list-item'));

                    // Store data attributes for filtering
                    const uri = dataset.__meta.uri || '';
                    const method = dataset.__meta.method || 'GET';
                    item.setAttribute('data-url', uri);
                    item.setAttribute('data-method', method);

                    if (datasetId === activeId) {
                        item.classList.add(csscls('active'));
                    }

                    // Request number
                    const nb = document.createElement('span');
                    nb.classList.add(csscls('datasets-item-nb'));
                    nb.textContent = `#${dataset.__meta.nb}`;
                    item.append(nb);

                    // Time
                    const time = document.createElement('span');
                    time.classList.add(csscls('datasets-item-time'));
                    time.textContent = dataset.__meta.datetime ? dataset.__meta.datetime.split(' ')[1] : '';
                    item.append(time);

                    // Request info
                    const request = document.createElement('div');
                    request.classList.add(csscls('datasets-item-request'));

                    const methodSpan = document.createElement('span');
                    methodSpan.classList.add(csscls('datasets-item-method'));
                    methodSpan.textContent = method;
                    request.append(methodSpan);

                    const url = document.createElement('span');
                    url.classList.add(csscls('datasets-item-url'));
                    url.textContent = uri;
                    request.append(url);

                    if (dataset.__meta.suffix) {
                        const suffix = document.createElement('span');
                        suffix.classList.add(csscls('datasets-item-suffix'));
                        suffix.textContent = ` ${dataset.__meta.suffix}`;
                        request.append(suffix);
                    }

                    item.append(request);

                    // Data badges (icons with counts)
                    const badges = document.createElement('div');
                    badges.classList.add(csscls('datasets-item-badges'));

                    for (const [key, def] of Object.entries(debugbar.dataMap)) {
                        const d = getDictValue(dataset, def[0], def[1]);
                        if (key.includes(':')) {
                            const parts = key.split(':');
                            const controlKey = parts[0];
                            const subkey = parts[1];

                            if (subkey === 'badge' && d > 0) {
                                const control = debugbar.getControl(controlKey);
                                if (control) {
                                    const badge = document.createElement('span');
                                    badge.classList.add(csscls('datasets-item-badge'));
                                    badge.setAttribute('title', control.get('title'));
                                    badge.dataset.tab = controlKey;

                                    if (control.icon) {
                                        const iconClone = control.icon.cloneNode(true);
                                        iconClone.style.width = '12px';
                                        iconClone.style.height = '12px';
                                        badge.append(iconClone);
                                    }

                                    const count = document.createElement('span');
                                    count.textContent = d;
                                    badge.append(count);

                                    badges.append(badge);

                                    // Click badge to show that tab
                                    badge.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        debugbar.showDataSet(datasetId);
                                        debugbar.showTab(controlKey);
                                        this.panel.hidden = true;
                                    });
                                }
                            }
                        }
                    }
                    item.append(badges);

                    // Click handler
                    item.addEventListener('click', () => {
                        debugbar.showDataSet(datasetId);
                        this.panel.hidden = true;
                    });

                    this.list.append(item);
                }

                // Reapply search filter if there's a search value
                this.applySearchFilter();
            } else {
                this.badge.hidden = true;
            }
        }

        applySearchFilter() {
            const searchValue = this.searchInput.value.toLowerCase().trim();
            const items = this.list.querySelectorAll(`.${csscls('datasets-list-item')}`);

            for (const item of items) {
                if (searchValue === '') {
                    item.hidden = false;
                } else {
                    const url = item.getAttribute('data-url').toLowerCase();
                    const method = item.getAttribute('data-method').toLowerCase();
                    const searchText = `${method} ${url}`;

                    // Split search terms by spaces and check if ALL terms match
                    const searchTerms = searchValue.split(/\s+/).filter(term => term.length > 0);
                    const matches = searchTerms.every(term => searchText.includes(term));

                    item.hidden = !matches;
                }
            }
        }

        scanForNewDatasets() {
            const debugbar = this.get('debugbar');
            if (!this.isScanning || !debugbar.openHandler)
                return;

            const datasets = debugbar.datasets;

            const latestUtime = Object.values(datasets)
                .reduce((max, d) => Math.max(max, d.__meta?.utime || 0), 0);

            const scheduleNextScan = () => {
                if (this.isScanning) {
                    setTimeout(() => this.scanForNewDatasets(), 1000);
                }
            };

            debugbar.openHandler.find({}, 0, (data, err) => {
                // Abort on explicit error argument
                if (err) {
                    console.error('scanForNewDatasets: find() failed', err);
                    this.isScanning = false;
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Error scanning';
                    return;
                }

                try {
                    const newDatasets = data.filter(
                        meta => meta.utime > latestUtime && !datasets[meta.id]
                    );

                    const loadNext = (index = 0) => {
                        if (index >= newDatasets.length) {
                            scheduleNextScan();
                            return;
                        }

                        const { id } = newDatasets[index];
                        debugbar.loadDataSet(
                            id,
                            '(scan)',
                            () => loadNext(index + 1),
                            this.autoshowCheckbox.checked
                        );
                    };

                    newDatasets.length ? loadNext() : scheduleNextScan();
                } catch (error) {
                    console.error('scanForNewDatasets: unexpected error', error);
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Error scanning';
                    this.isScanning = false;
                }
            });
        }
    }
    PhpDebugBar.Widgets.DatasetWidget = DatasetWidget;
})();
