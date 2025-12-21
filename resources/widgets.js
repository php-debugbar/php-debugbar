if (typeof PhpDebugBar === 'undefined') {
    // namespace
    window.PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function ($) {
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
            if (typeof hljs === 'undefined') {
                return htmlize(code);
            }
            if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(code, { language: lang }).value;
            }
            return hljs.highlightAuto(code).value;
        }

        if (typeof hljs === 'object') {
            code.each((i, e) => {
                hljs.highlightElement(e);
            });
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
        const pre = $('<pre />').addClass(csscls('code-block'));
        // Add a newline to prevent <code> element from vertically collapsing too far if the last
        // code line was empty: that creates problems with the horizontal scrollbar being
        // incorrectly positioned - most noticeable when line numbers are shown.
        const codeElement = $('<code />').text(`${code}\n`).appendTo(pre);

        // Format the code
        if (lang) {
            codeElement.addClass(`language-${lang}`);
        }
        highlight(codeElement).removeClass('hljs');

        // Show line numbers in a list
        if (!isNaN(Number.parseFloat(firstLineNumber))) {
            const lineCount = code.split('\n').length;
            const $lineNumbers = $('<ul />').prependTo(pre);
            pre.children().addClass(csscls('numbered-code'));
            for (let i = firstLineNumber; i < firstLineNumber + lineCount; i++) {
                const li = $('<li />').text(i).appendTo($lineNumbers);

                // Add a span with a special class if we are supposed to highlight a line.
                if (highlightedLine === i) {
                    li.addClass(csscls('highlighted-line')).append('<span>&nbsp;</span>');
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
    const ListWidget = PhpDebugBar.Widgets.ListWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('list'),

        initialize(options) {
            if (!options.itemRenderer) {
                options.itemRenderer = this.itemRenderer;
            }
            this.set(options);
        },

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                const data = this.get('data');
                for (let i = 0; i < data.length; i++) {
                    const li = $('<li />').addClass(csscls('list-item')).appendTo(this.$el);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        },

        /**
         * Renders the content of a <li> element
         *
         * @param {jQuery} li The <li> element as a jQuery Object
         * @param {object} value An item from the data array
         */
        itemRenderer(li, value) {
            li.html(renderValue(value));
        }

    });

    // ------------------------------------------------------------------

    /**
     * Displays object property/value paris in a <dl> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    const KVListWidget = PhpDebugBar.Widgets.KVListWidget = ListWidget.extend({

        tagName: 'dl',

        className: csscls('kvlist'),

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                for (const [key, value] of Object.entries(this.get('data'))) {
                    const dt = $('<dt />').addClass(csscls('key')).appendTo(this.$el);
                    const dd = $('<dd />').addClass(csscls('value')).appendTo(this.$el);
                    this.get('itemRenderer')(dt, dd, key, value);
                }
            });
        },

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {jQuery} dt The <dt> element as a jQuery Object
         * @param {jQuery} dd The <dd> element as a jQuery Object
         * @param {string} key Property name
         * @param {object} value Property value
         */
        itemRenderer(dt, dd, key, value) {
            dt.text(key);
            dd.html(htmlize(value));
        }

    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     *
     * Options:
     *  - data
     */
    const VariableListWidget = PhpDebugBar.Widgets.VariableListWidget = KVListWidget.extend({

        className: csscls('kvlist varlist'),

        itemRenderer(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);

            let v = value && value.value || value;
            if (v && v.length > 100) {
                v = `${v.substr(0, 100)}...`;
            }
            let prettyVal = null;
            dd.text(v).click(() => {
                if (window.getSelection().type === 'Range') {
                    return '';
                }
                if (dd.hasClass(csscls('pretty'))) {
                    dd.text(v).removeClass(csscls('pretty'));
                } else {
                    prettyVal = prettyVal || createCodeBlock(value);
                    dd.addClass(csscls('pretty')).empty().append(prettyVal);
                }
            });
        }

    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables whose contents are HTML; this is useful for showing
     * variable output from VarDumper's HtmlDumper.
     *
     * Options:
     *  - data
     */
    const HtmlVariableListWidget = PhpDebugBar.Widgets.HtmlVariableListWidget = KVListWidget.extend({

        className: csscls('kvlist htmlvarlist'),

        itemRenderer(dt, dd, key, value) {
            $('<span />').attr('title', $('<i />').html(key ?? '').text()).html(key ?? '').appendTo(dt);
            dd.html(value && value.value || value);

            if (value && value.xdebug_link) {
                const header = $('<span />').addClass(csscls('filename')).text(value.xdebug_link.filename + (value.xdebug_link.line ? `#${value.xdebug_link.line}` : ''));
                if (value.xdebug_link) {
                    if (value.xdebug_link.ajax) {
                        $(`<a title="${value.xdebug_link.url}"></a>`).on('click', () => {
                            fetch(value.xdebug_link.url);
                        }).addClass(csscls('editor-link')).appendTo(header);
                    } else {
                        $(`<a href="${value.xdebug_link.url}"></a>`).addClass(csscls('editor-link')).appendTo(header);
                    }
                }
                header.appendTo(dd);
            }
        }

    });

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
    const TableVariableListWidget = PhpDebugBar.Widgets.TableVariableListWidget = PhpDebugBar.Widget.extend({

        tagName: 'div',

        className: csscls('tablevarlist'),

        render() {
            this.bindAttr('data', function (data) {
                this.$el.empty();

                if (!this.has('data')) {
                    return;
                }

                this.$table = $('<table />').addClass(csscls('tablevar')).appendTo(this.$el);
                const $header = $('<tr />').addClass(csscls('header')).append('<td />').appendTo(this.$table);
                let key_map = data.key_map || { value: 'Value' };

                if (Array.isArray(key_map)) {
                    key_map = Object.fromEntries(key_map.map(k => [k, null]));
                }

                $.each(key_map, (key, label) => {
                    const colTitle = $('<td />').text(label ?? key).appendTo($header);

                    if (data.badges && data.badges[key]) {
                        $('<span />').text(data.badges[key]).addClass(csscls('badge')).appendTo(colTitle);
                    }
                });

                const self = this;
                $.each(data.data, (key, values) => {
                    const $tr = $('<tr />').addClass(csscls('item')).appendTo(self.$table);
                    $('<td />').addClass(csscls('key')).text(key).appendTo($tr);

                    if (typeof values !== 'object' || values === null) {
                        $('<td />').addClass(csscls('value')).text(values ?? '').appendTo($tr);
                        return;
                    }

                    $.each(key_map, (key) => {
                        $('<td />').addClass(csscls('value')).text(values[key] ?? '').appendTo($tr);
                    });

                    if (values.xdebug_link) {
                        const filename = $('<span />').addClass(csscls('filename')).text(values.xdebug_link.filename + (values.xdebug_link.line ? `#${values.xdebug_link.line}` : '')).appendTo($('<td />').addClass(csscls('editor')).appendTo($tr));
                        if (values.xdebug_link.ajax) {
                            $(`<a title="${values.xdebug_link.url}"></a>`).on('click', () => {
                                fetch(values.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(filename);
                        } else {
                            $(`<a href="${values.xdebug_link.url}"></a>`).addClass(csscls('editor-link')).appendTo(filename);
                        }

                        if (!data.xdebug_link) {
                            data.xdebug_link = true;
                            $header.append($('<td />'));
                        }
                    }
                });

                if (!data.summary) {
                    return;
                }

                const $tr = $('<tr />').addClass(csscls('summary')).appendTo(self.$table);
                $('<td />').addClass(csscls('key')).appendTo($tr);

                if (typeof data.summary !== 'object' || data.summary === null) {
                    $('<td />').addClass(csscls('value')).text(data.summary ?? '').appendTo($tr);
                } else {
                    $.each(key_map, (key) => {
                        $('<td />').addClass(csscls('value')).text(data.summary[key] ?? '').appendTo($tr);
                    });
                }

                if (data.xdebug_link) {
                    $('<td />').appendTo($tr);
                }
            });
        }
    });

    // ------------------------------------------------------------------

    /**
     * Iframe widget
     *
     * Options:
     *  - data
     */
    const IFrameWidget = PhpDebugBar.Widgets.IFrameWidget = PhpDebugBar.Widget.extend({

        tagName: 'iframe',

        className: csscls('iframe'),

        render() {
            this.$el.attr({
                seamless: 'seamless',
                border: '0',
                width: '100%',
                height: '100%'
            });
            this.bindAttr('data', function (url) {
                this.$el.attr('src', url);
            });
        }

    });

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
    const MessagesWidget = PhpDebugBar.Widgets.MessagesWidget = PhpDebugBar.Widget.extend({

        className: csscls('messages'),

        render() {
            const self = this;

            this.$list = new ListWidget({ itemRenderer(li, value) {
                let val;
                if (value.message_html) {
                    val = $('<span />').addClass(csscls('value')).html(value.message_html).appendTo(li);
                } else {
                    let m = value.message;
                    if (m.length > 100) {
                        m = `${m.substr(0, 100)}...`;
                    }

                    val = $('<span />').addClass(csscls('value')).text(m).appendTo(li);
                    if (!value.is_string || value.message.length > 100) {
                        let prettyVal = value.message;
                        if (!value.is_string) {
                            prettyVal = null;
                        }
                        li.css('cursor', 'pointer').click(() => {
                            if (window.getSelection().type === 'Range') {
                                return '';
                            }
                            if (val.hasClass(csscls('pretty'))) {
                                val.text(m).removeClass(csscls('pretty'));
                            } else {
                                prettyVal = prettyVal || createCodeBlock(value.message, 'php');
                                val.addClass(csscls('pretty')).empty().append(prettyVal);
                            }
                        });
                    }
                }
                if (value.xdebug_link) {
                    const header = $('<span />').addClass(csscls('filename')).text(value.xdebug_link.filename + (value.xdebug_link.line ? `#${value.xdebug_link.line}` : ''));
                    if (value.xdebug_link) {
                        if (value.xdebug_link.ajax) {
                            $(`<a title="${value.xdebug_link.url}"></a>`).on('click', () => {
                                fetch(value.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(header);
                        } else {
                            $(`<a href="${value.xdebug_link.url}"></a>`).addClass(csscls('editor-link')).appendTo(header);
                        }
                    }
                    header.prependTo(li);
                }
                if (value.collector) {
                    $('<span />').addClass(csscls('collector')).text(value.collector).prependTo(li);
                }
                if (value.label) {
                    val.addClass(csscls(value.label));
                    $('<span />').addClass(csscls('label')).text(value.label).prependTo(li);
                }
            } });

            this.$list.$el.appendTo(this.$el);
            this.$toolbar = $('<div><i class="phpdebugbar-fa phpdebugbar-fa-search"></i></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            $('<input type="text" name="search" aria-label="Search" placeholder="Search" />')
                .on('change', function () {
                    self.set('search', this.value);
                })
                .appendTo(this.$toolbar);

            this.bindAttr('data', function (data) {
                this.set({ excludelabel: [], excludecollector: [], search: '' });
                this.$toolbar.find(csscls('.filter')).remove();

                const labels = []; const collectors = []; const self = this;
                const createFilterItem = function (type, value) {
                    $('<a />')
                        .addClass(csscls('filter'))
                        .addClass(csscls(type))
                        .text(value)
                        .attr('rel', value)
                        .on('click', function () {
                            self.onFilterClick(this, type);
                        })
                        .appendTo(self.$toolbar);
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

                $('<a />').addClass(csscls('filter')).css('visibility', 'hidden').appendTo(self.$toolbar);
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

                this.$list.set('data', fdata);
            });
        },

        onFilterClick(el, type) {
            $(el).toggleClass(csscls('excluded'));

            const excluded = [];
            this.$toolbar.find(csscls('.filter') + csscls('.excluded') + csscls(`.${type}`)).each(function () {
                excluded.push(this.rel === 'none' || !this.rel ? undefined : this.rel);
            });

            this.set(`exclude${type}`, excluded);
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    const TimelineWidget = PhpDebugBar.Widgets.TimelineWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('timeline'),

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

                this.$el.empty();
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

                        const m = $('<div />').addClass(csscls('measure'));
                        const li = $('<li />');
                        const left = (measure.relative_start * 100 / data.duration).toFixed(2);
                        const width = Math.min((measure.duration * 100 / data.duration).toFixed(2), 100 - left);

                        m.append($('<span />').addClass(csscls('value')).css({
                            left: `${left}%`,
                            width: `${width}%`
                        }));
                        m.append($('<span />').addClass(csscls('label'))
                            .text(measure.label.replace(/\s+/g, ' ') + (measure.duration ? ` (${measure.duration_str}${measure.memory ? `/${measure.memory_str}` : ''})` : '')));

                        if (measure.collector) {
                            $('<span />').addClass(csscls('collector')).text(measure.collector).appendTo(m);
                        }

                        m.appendTo(li);
                        this.$el.append(li);

                        if (measure.params && !$.isEmptyObject(measure.params)) {
                            const table = $('<table><tr><th colspan="2">Params</th></tr></table>').hide().addClass(csscls('params')).appendTo(li);
                            for (const key in measure.params) {
                                if (typeof measure.params[key] !== 'function') {
                                    table.append(`<tr><td class="${csscls('name')}">${key}</td><td class="${csscls('value')
                                    }"><pre><code>${measure.params[key]}</code></pre></td></tr>`);
                                }
                            }
                            li.css('cursor', 'pointer').click(function () {
                                if (window.getSelection().type === 'Range') {
                                    return '';
                                }
                                const table = $(this).find('table');
                                if (table.is(':visible')) {
                                    table.hide();
                                } else {
                                    table.show();
                                }
                            }).on('click', '.sf-dump', (event) => {
                                event.stopPropagation();
                            });
                        }
                    }

                    // convert to array and sort by duration
                    aggregate = $.map(aggregate, (data, label) => {
                        return {
                            label,
                            data
                        };
                    }).sort((a, b) => {
                        return b.data.duration - a.data.duration;
                    });

                    // build table and add
                    const aggregateTable = $('<table></table>').addClass(csscls('params'));
                    $.each(aggregate, (i, aggregate) => {
                        const width = Math.min((aggregate.data.duration * 100 / data.duration).toFixed(2), 100);

                        aggregateTable.append(`<tr><td class="${csscls('name')}">${
                            aggregate.data.count} x ${$('<i />').text(aggregate.label.replace(/\s+/g, ' ')).html()} (${width}%)</td><td class="${csscls('value')}">`
                            + `<div class="${csscls('measure')}">`
                            + `<span class="${csscls('value')}"></span>`
                            + `<span class="${csscls('label')}">${formatDuration(aggregate.data.duration)}${aggregate.data.memory ? `/${formatBytes(aggregate.data.memory)}` : ''}</span>`
                            + '</div></td></tr>');
                        aggregateTable.find(`span.${csscls('value')}:last`).css({ width: `${width}%` });
                    });

                    this.$el.append('<li/>').find('li:last').append(aggregateTable);
                }
            });
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the displaying exceptions
     *
     * Options:
     *  - data
     */
    const ExceptionsWidget = PhpDebugBar.Widgets.ExceptionsWidget = PhpDebugBar.Widget.extend({

        className: csscls('exceptions'),

        render() {
            this.$list = new ListWidget({ itemRenderer(li, e) {
                $('<span />').addClass(csscls('message')).text(e.message).prepend(e.count > 1 ? $('<span />').addClass(csscls('badge')).text(`${e.count}x`) : '').appendTo(li);
                if (e.file) {
                    const header = $('<span />').addClass(csscls('filename')).text(`${e.file}#${e.line}`);
                    if (e.xdebug_link) {
                        if (e.xdebug_link.ajax) {
                            $(`<a title="${e.xdebug_link.url}"></a>`).on('click', () => {
                                fetch(e.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(header);
                        } else {
                            $(`<a href="${e.xdebug_link.url}"></a>`).addClass(csscls('editor-link')).appendTo(header);
                        }
                    }
                    header.appendTo(li);
                }
                if (e.type) {
                    $('<span />').addClass(csscls('type')).text(e.type).appendTo(li);
                }
                if (e.surrounding_lines) {
                    const startLine = (e.line - 3) <= 0 ? 1 : e.line - 3;
                    const pre = createCodeBlock(e.surrounding_lines.join(''), 'php', startLine, e.line).addClass(csscls('file')).appendTo(li);
                    if (!e.stack_trace_html) {
                        // This click event makes the var-dumper hard to use.
                        li.click(() => {
                            if (pre.is(':visible')) {
                                pre.hide();
                            } else {
                                pre.show();
                            }
                        });
                    }
                }
                if (e.stack_trace_html) {
                    const $trace = $('<span />').addClass(csscls('filename')).html(e.stack_trace_html);
                    $trace.find('samp[data-depth="1"]').removeClass('sf-dump-expanded').addClass('sf-dump-compact').parent().find('>.sf-dump-note').html((_, t) => `${t.replace(/^array:/, '<span class="sf-dump-key">Stack Trace:</span> ')} files`);
                    $trace.appendTo(li);
                } else if (e.stack_trace) {
                    e.stack_trace.split('\n').forEach((trace) => {
                        const $traceLine = $('<div />');
                        $('<span />').addClass(csscls('filename')).text(trace).appendTo($traceLine);
                        $traceLine.appendTo(li);
                    });
                }
            } });
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function (data) {
                this.$list.set('data', data);
                if (data.length === 1) {
                    this.$list.$el.children().first().find(csscls('.file')).show();
                }
            });
        }

    });

    /**
     * Displays datasets in a table
     *
     */
    const DatasetWidget = PhpDebugBar.Widgets.DatasetWidget = PhpDebugBar.Widget.extend({

        initialize(options) {
            if (!options.itemRenderer) {
                options.itemRenderer = this.itemRenderer;
            }
            this.set(options);
            this.set('autoshow', null);
            this.set('id', null);
            this.set('sort', localStorage.getItem('debugbar-history-sort') || 'asc');
            this.$el.addClass(csscls('dataset-history'));

            this.renderHead();
        },

        renderHead() {
            this.$el.empty();
            this.$actions = $('<div />').addClass(csscls('dataset-actions')).appendTo(this.$el);

            const self = this;
            const debugbar = self.get('debugbar');

            this.$autoshow = $('<input type=checkbox>')
                .on('click', function () {
                    if (debugbar.ajaxHandler) {
                        debugbar.ajaxHandler.setAutoShow($(this).is(':checked'));
                    }
                    if (debugbar.controls.__settings) {
                        debugbar.controls.__settings.get('widget').set('autoshow', this.autoShow);
                    }
                });

            $('<label>Autoshow</label>')
                .append(this.$autoshow)
                .appendTo(this.$actions);

            this.$clearbtn = $('<a>Clear</a>')
                .appendTo(this.$actions)
                .on('click', () => {
                    self.$table.empty();
                });

            this.$showBtn = $('<a>Show all</a>')
                .appendTo(this.$actions)
                .on('click', () => {
                    self.searchInput.val(null);
                    self.methodInput.val(null);
                    self.set('search', null);
                    self.set('method', null);
                });

            this.methodInput = $('<select name="method" style="width:100px"><option>(method)</option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select>')
                .on('change', function () {
                    self.set('method', this.value);
                })
                .appendTo(this.$actions);

            this.searchInput = $('<input type="text" name="search" aria-label="Search" placeholder="Search" />')
                .on('input', function () {
                    self.set('search', this.value);
                })
                .appendTo(this.$actions);

            this.$table = $('<tbody />');

            $('<table/>')
                .append($('<thead/>')
                    .append($('<tr/>')
                        .append($('<th></th>').css('width', '30px'))
                        .append($('<th>Date ↕</th>').css('width', '175px').click(() => {
                            self.set('sort', self.get('sort') === 'asc' ? 'desc' : 'asc');
                            localStorage.setItem('debugbar-history-sort', self.get('sort'));
                        }))
                        .append($('<th>Method</th>').css('width', '80px'))
                        .append($('<th>URL</th>'))
                        .append($('<th width="40%">Data</th>')))
                )
                .append(this.$table)
                .appendTo(this.$el);
        },

        renderDatasets() {
            this.$table.empty();
            const self = this;
            $.each(this.get('data'), (key, data) => {
                if (!data.__meta) {
                    return;
                }

                self.get('itemRenderer')(self, data);
            });
        },

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
                this.$autoshow.prop('checked', autoshow);
            });
            this.bindAttr('id', function () {
                const id = this.get('id');
                this.$table.find(`.${csscls('active')}`).removeClass(csscls('active'));
                this.$table.find(`tr[data-id=${id}]`).addClass(csscls('active'));
            });
        },

        /**
         * Renders the content of a dataset item
         *
         * @param {object} value An item from the data array
         */
        itemRenderer(widget, data) {
            const meta = data.__meta;

            const $badges = $('<td />');
            const tr = $('<tr />');
            if (widget.get('sort') === 'asc') {
                tr.appendTo(widget.$table);
            } else {
                tr.prependTo(widget.$table);
            }

            const clickHandler = function () {
                const debugbar = widget.get('debugbar');
                debugbar.showDataSet(meta.id, debugbar.datesetTitleFormater.format('', data, meta.suffix, meta.nb));
                widget.$table.find(`.${csscls('active')}`).removeClass(csscls('active'));
                tr.addClass(csscls('active'));

                if ($(this).data('tab')) {
                    debugbar.showTab($(this).data('tab'));
                }
            };

            tr.attr('data-id', meta.id)
                .append($(`<td>#${meta.nb}</td>`).click(clickHandler))
                .append($(`<td>${meta.datetime}</td>`).click(clickHandler))
                .append($(`<td>${meta.method}</td>`).click(clickHandler))
                .append($('<td />').append(meta.uri + (meta.suffix ? ` ${meta.suffix}` : '')).click(clickHandler))
                .css('cursor', 'pointer')
                .addClass(csscls('table-row'));

            const debugbar = widget.get('debugbar');
            $.each(debugbar.dataMap, (key, def) => {
                const d = getDictValue(data, def[0], def[1]);
                if (key.includes(':')) {
                    key = key.split(':');
                    if (key[1] === 'badge' && d > 0) {
                        const control = debugbar.getControl(key[0]);
                        const $a = $('<a>').attr('title', control.get('title')).data('tab', key[0]);
                        if (control.$icon) {
                            $a.append(debugbar.getControl(key[0]).$icon.clone());
                        }
                        if (control.$badge) {
                            $a.append(debugbar.getControl(key[0]).$badge.clone().css('display', 'inline-block').text(d));
                        }
                        $a.appendTo($badges).click(clickHandler);
                    } else if (key[1] === 'tooltip') {
                        debugbar.getControl(key[0]).set('tooltip', d);
                    }
                }
            });
            tr.append($badges);

            if (debugbar.activeDatasetId === meta.id) {
                tr.addClass(csscls('active'));
            }

            const search = widget.get('search');
            const method = widget.get('method');
            if ((search && !meta.uri.includes(search)) || (method && meta.method !== method)) {
                tr.hide();
            }
        }

    });
})(PhpDebugBar.$);
