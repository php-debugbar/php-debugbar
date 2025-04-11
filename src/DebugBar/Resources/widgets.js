if (typeof(PhpDebugBar) == 'undefined') {
    // namespace
    var PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function($) {

    /**
     * @namespace
     */
    PhpDebugBar.Widgets = {};

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Replaces spaces with &nbsp; and line breaks with <br>
     *
     * @param {String} text
     * @return {String}
     */
    var htmlize = PhpDebugBar.Widgets.htmlize = function(text) {
        return text.replace(/\n/g, '<br>').replace(/\s/g, "&nbsp;")
    };

    /**
     * Returns a string representation of value, using JSON.stringify
     * if it's an object.
     *
     * @param {Object} value
     * @param {Boolean} prettify Uses htmlize() if true
     * @return {String}
     */
    var renderValue = PhpDebugBar.Widgets.renderValue = function(value, prettify) {
        if (typeof(value) !== 'string') {
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
     * @param  {String} code
     * @param  {String} lang
     * @return {String}
     */
    var highlight = PhpDebugBar.Widgets.highlight = function(code, lang) {
        if (typeof(code) === 'string') {
            if (typeof(hljs) === 'undefined') {
                return htmlize(code);
            }
            if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(code, {language: lang}).value;
            }
            return hljs.highlightAuto(code).value;
        }

        if (typeof(hljs) === 'object') {
            code.each(function(i, e) { hljs.highlightElement(e); });
        }
        return code;
    };

    /**
     * Creates a <pre> element with a block of code
     *
     * @param  {String} code
     * @param  {String} lang
     * @param  {Number} [firstLineNumber] If provided, shows line numbers beginning with the given value.
     * @param  {Number} [highlightedLine] If provided, the given line number will be highlighted.
     * @return {String}
     */
    var createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function(code, lang, firstLineNumber, highlightedLine) {
        var pre = $('<pre />').addClass(csscls('code-block'));
        // Add a newline to prevent <code> element from vertically collapsing too far if the last
        // code line was empty: that creates problems with the horizontal scrollbar being
        // incorrectly positioned - most noticeable when line numbers are shown.
        var codeElement = $('<code />').text(code + '\n').appendTo(pre);

        // Format the code
        if (lang) {
            codeElement.addClass("language-" + lang);
        }
        highlight(codeElement).removeClass('hljs');

        // Show line numbers in a list
        if (!isNaN(parseFloat(firstLineNumber))) {
            var lineCount = code.split('\n').length;
            var $lineNumbers = $('<ul />').prependTo(pre);
            pre.children().addClass(csscls('numbered-code'));
            for (var i = firstLineNumber; i < firstLineNumber + lineCount; i++) {
                var li = $('<li />').text(i).appendTo($lineNumbers);

                // Add a span with a special class if we are supposed to highlight a line.
                if (highlightedLine === i) {
                    li.addClass(csscls('highlighted-line')).append('<span>&nbsp;</span>');
                }
            }
        }

        return pre;
    };

    var getDictValue = PhpDebugBar.utils.getDictValue = function(dict, key, default_value) {
        var d = dict, parts = key.split('.');
        for (var i = 0; i < parts.length; i++) {
            if (!d[parts[i]]) {
                return default_value;
            }
            d = d[parts[i]];
        }
        return d;
    }

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
    var ListWidget = PhpDebugBar.Widgets.ListWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('list'),

        initialize: function(options) {
            if (!options['itemRenderer']) {
                options['itemRenderer'] = this.itemRenderer;
            }
            this.set(options);
        },

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var data = this.get('data');
                for (var i = 0; i < data.length; i++) {
                    var li = $('<li />').addClass(csscls('list-item')).appendTo(this.$el);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        },

        /**
         * Renders the content of a <li> element
         *
         * @param {jQuery} li The <li> element as a jQuery Object
         * @param {Object} value An item from the data array
         */
        itemRenderer: function(li, value) {
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
    var KVListWidget = PhpDebugBar.Widgets.KVListWidget = ListWidget.extend({

        tagName: 'dl',

        className: csscls('kvlist'),

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var self = this;
                $.each(this.get('data'), function(key, value) {
                    var dt = $('<dt />').addClass(csscls('key')).appendTo(self.$el);
                    var dd = $('<dd />').addClass(csscls('value')).appendTo(self.$el);
                    self.get('itemRenderer')(dt, dd, key, value);
                });
            });
        },

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {jQuery} dt The <dt> element as a jQuery Object
         * @param {jQuery} dd The <dd> element as a jQuery Object
         * @param {String} key Property name
         * @param {Object} value Property value
         */
        itemRenderer: function(dt, dd, key, value) {
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
    var VariableListWidget = PhpDebugBar.Widgets.VariableListWidget = KVListWidget.extend({

        className: csscls('kvlist varlist'),

        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);

            var v = value && value.value || value;
            if (v && v.length > 100) {
                v = v.substr(0, 100) + "...";
            }
            var prettyVal = null;
            dd.text(v).click(function() {
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
    var HtmlVariableListWidget = PhpDebugBar.Widgets.HtmlVariableListWidget = KVListWidget.extend({

        className: csscls('kvlist htmlvarlist'),

        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', $('<i />').html(key ?? '').text()).html(key ?? '').appendTo(dt);
            dd.html(value && value.value || value);

            if (value && value.xdebug_link) {
                var header = $('<span />').addClass(csscls('filename')).text(value.xdebug_link.filename + ( value.xdebug_link.line ? "#" + value.xdebug_link.line : ''));
                if (value.xdebug_link) {
                    if (value.xdebug_link.ajax) {
                        $('<a title="' + value.xdebug_link.url + '"></a>').on('click', function () {
                            $.ajax(value.xdebug_link.url);
                        }).addClass(csscls('editor-link')).appendTo(header);
                    } else {
                        $('<a href="' + value.xdebug_link.url + '"></a>').addClass(csscls('editor-link')).appendTo(header);
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
    var TableVariableListWidget = PhpDebugBar.Widgets.TableVariableListWidget =  PhpDebugBar.Widget.extend({

        tagName: 'div',

        className: csscls('tablevarlist'),

        render: function() {
            this.bindAttr('data', function(data) {
                this.$el.empty();

                if (!this.has('data')) {
                    return;
                }

                this.$table = $('<table />').addClass(csscls('tablevar')).appendTo(this.$el);
                var $header = $('<tr />').addClass(csscls('header')).append('<td />').appendTo(this.$table);
                var key_map = data.key_map || {value: 'Value'};

                if (Array.isArray(key_map)) {
                    key_map = Object.fromEntries(key_map.map(k => [k, null]));
                }

                $.each(key_map, function(key, label) {
                    var colTitle = $('<td />').text(label ?? key).appendTo($header);

                    if (data.badges && data.badges[key]) {
                        $('<span />').text(data.badges[key]).addClass(csscls('badge')).appendTo(colTitle);
                    }
                });

                var self = this;
                $.each(data.data, function(key, values) {
                    var $tr = $('<tr />').addClass(csscls('item')).appendTo(self.$table);
                    $('<td />').addClass(csscls('key')).text(key).appendTo($tr);

                    if (typeof values !== 'object' || values === null) {
                        $('<td />').addClass(csscls('value')).text(values ?? '').appendTo($tr);
                        return;
                    }

                    $.each(key_map, function(key) {
                        $('<td />').addClass(csscls('value')).text(values[key] ?? '').appendTo($tr);
                    });

                    if (values.xdebug_link) {
                        var filename = $('<span />').addClass(csscls('filename'))
                            .text(values.xdebug_link.filename + ( values.xdebug_link.line ? "#" + values.xdebug_link.line : ''))
                            .appendTo($('<td />').addClass(csscls('editor')).appendTo($tr));
                        if (values.xdebug_link.ajax) {
                            $('<a title="' + values.xdebug_link.url + '"></a>').on('click', function () {
                                $.ajax(values.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(filename);
                        } else {
                            $('<a href="' + values.xdebug_link.url + '"></a>').addClass(csscls('editor-link')).appendTo(filename);
                        }

                        if (!data.xdebug_link) {
                            data.xdebug_link = true;
                            $header.append($('<td />'));
                        }
                    }
                });

                if (!data.summary) return;

                var $tr = $('<tr />').addClass(csscls('summary')).appendTo(self.$table);
                $('<td />').addClass(csscls('key')).appendTo($tr);

                if (typeof data.summary !== 'object' || data.summary === null) {
                    $('<td />').addClass(csscls('value')).text(data.summary ?? '').appendTo($tr);
                } else {
                    $.each(key_map, function(key) {
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
    var IFrameWidget = PhpDebugBar.Widgets.IFrameWidget = PhpDebugBar.Widget.extend({

        tagName: 'iframe',

        className: csscls('iframe'),

        render: function() {
            this.$el.attr({
                seamless: "seamless",
                border: "0",
                width: "100%",
                height: "100%"
            });
            this.bindAttr('data', function(url) { this.$el.attr('src', url); });
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
    var MessagesWidget = PhpDebugBar.Widgets.MessagesWidget = PhpDebugBar.Widget.extend({

        className: csscls('messages'),

        render: function() {
            var self = this;

            this.$list = new ListWidget({ itemRenderer: function(li, value) {
                if (value.message_html) {
                    var val = $('<span />').addClass(csscls('value')).html(value.message_html).appendTo(li);
                } else {
                    var m = value.message;
                    if (m.length > 100) {
                        m = m.substr(0, 100) + "...";
                    }

                    var val = $('<span />').addClass(csscls('value')).text(m).appendTo(li);
                    if (!value.is_string || value.message.length > 100) {
                        var prettyVal = value.message;
                        if (!value.is_string) {
                            prettyVal = null;
                        }
                        li.css('cursor', 'pointer').click(function () {
                            if (window.getSelection().type == "Range") {
                                return''
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
                    var header = $('<span />').addClass(csscls('filename')).text(value.xdebug_link.filename + ( value.xdebug_link.line ? "#" + value.xdebug_link.line : ''));
                    if (value.xdebug_link) {
                        if (value.xdebug_link.ajax) {
                            $('<a title="' + value.xdebug_link.url + '"></a>').on('click', function () {
                                $.ajax(value.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(header);
                        } else {
                            $('<a href="' + value.xdebug_link.url + '"></a>').addClass(csscls('editor-link')).appendTo(header);
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
            }});

            this.$list.$el.appendTo(this.$el);
            this.$toolbar = $('<div><i class="phpdebugbar-fa phpdebugbar-fa-search"></i></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            $('<input type="text" name="search" aria-label="Search" placeholder="Search" />')
                .on('change', function() { self.set('search', this.value); })
                .appendTo(this.$toolbar);

            this.bindAttr('data', function(data) {
                this.set({excludelabel: [], excludecollector: [], search: ''});
                this.$toolbar.find(csscls('.filter')).remove();

                var labels = [], collectors = [], self = this,
                    createFilterItem = function (type, value) {
                        $('<a />')
                            .addClass(csscls('filter')).addClass(csscls(type))
                            .text(value).attr('rel', value)
                            .on('click', function() { self.onFilterClick(this, type); })
                            .appendTo(self.$toolbar)
                    };

                data.forEach(function (item) {
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

            this.bindAttr(['excludelabel', 'excludecollector', 'search'], function() {
                var excludelabel = this.get('excludelabel') || [],
                    excludecollector = this.get('excludecollector') || [],
                    search = this.get('search'),
                    caseless = false,
                    fdata = [];

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                this.get('data').forEach(function (item) {
                    var message = caseless ? item.message.toLowerCase() : item.message;

                    if (
                        !excludelabel.includes(item.label || undefined) &&
                        !excludecollector.includes(item.collector || undefined) &&
                        (!search || message.indexOf(search) > -1)
                    ) {
                        fdata.push(item);
                    }
                });

                this.$list.set('data', fdata);
            });
        },

        onFilterClick: function(el, type) {
            $(el).toggleClass(csscls('excluded'));

            var excluded = [];
            this.$toolbar.find(csscls('.filter') + csscls('.excluded') + csscls('.' + type)).each(function() {
                excluded.push(this.rel === 'none' || !this.rel ? undefined : this.rel);
            });

            this.set('exclude' + type, excluded);
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    var TimelineWidget = PhpDebugBar.Widgets.TimelineWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('timeline'),

        render: function() {
            this.bindAttr('data', function(data) {

                // ported from php DataFormatter
                var formatDuration = function(seconds) {
                    if (seconds < 0.001)
                        return (seconds * 1000000).toFixed() + 'μs';
                    else if (seconds < 0.1)
                        return (seconds * 1000).toFixed(2) + 'ms';
                    else if (seconds < 1)
                        return (seconds * 1000).toFixed() + 'ms';
                    return (seconds).toFixed(2) +  's';
                };

                // ported from php DataFormatter
                var formatBytes = function formatBytes(size) {
                    if (size === 0 || size === null) {
                        return '0B';
                    }

                    var sign = size < 0 ? '-' : '',
                        size = Math.abs(size),
                        base = Math.log(size) / Math.log(1024),
                        suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    return sign + (Math.round(Math.pow(1024, base - Math.floor(base)) * 100) / 100) + suffixes[Math.floor(base)];
                }

                this.$el.empty();
                if (data.measures) {
                    var aggregate = {};

                    for (var i = 0; i < data.measures.length; i++) {
                        var measure = data.measures[i];
                        var group = measure.group || measure.label;

                        if(!aggregate[group]) {
                            aggregate[group] = { count: 0, duration: 0, memory : 0 };
                        }

                        aggregate[group]['count'] += 1;
                        aggregate[group]['duration'] += measure.duration;
                        aggregate[group]['memory'] += (measure.memory || 0);

                        var m = $('<div />').addClass(csscls('measure')),
                            li = $('<li />'),
                            left = (measure.relative_start * 100 / data.duration).toFixed(2),
                            width = Math.min((measure.duration * 100 / data.duration).toFixed(2), 100 - left);

                        m.append($('<span />').addClass(csscls('value')).css({
                            left: left + "%",
                            width: width + "%"
                        }));
                        m.append($('<span />').addClass(csscls('label'))
                            .text(measure.label.replace(/\s+/g, ' ') + ( measure.duration ? " (" + measure.duration_str +(measure.memory ? '/' + measure.memory_str: '') + ")" : "")));

                        if (measure.collector) {
                            $('<span />').addClass(csscls('collector')).text(measure.collector).appendTo(m);
                        }

                        m.appendTo(li);
                        this.$el.append(li);

                        if (measure.params && !$.isEmptyObject(measure.params)) {
                            var table = $('<table><tr><th colspan="2">Params</th></tr></table>').hide().addClass(csscls('params')).appendTo(li);
                            for (var key in measure.params) {
                                if (typeof measure.params[key] !== 'function') {
                                    table.append('<tr><td class="' + csscls('name') + '">' + key + '</td><td class="' + csscls('value') +
                                    '"><pre><code>' + measure.params[key] + '</code></pre></td></tr>');
                                }
                            }
                            li.css('cursor', 'pointer').click(function() {
                                if (window.getSelection().type == "Range") {
                                    return '';
                                }
                                var table = $(this).find('table');
                                if (table.is(':visible')) {
                                    table.hide();
                                } else {
                                    table.show();
                                }
                            }).on('click', '.sf-dump', function(event) {
                                event.stopPropagation();
                            });
                        }
                    }

                    // convert to array and sort by duration
                    aggregate = $.map(aggregate, function(data, label) {
                       return {
                           label: label,
                           data: data
                       }
                    }).sort(function(a, b) {
                        return b.data.duration - a.data.duration
                    });

                    // build table and add
                    var aggregateTable = $('<table></table>').addClass(csscls('params'));
                    $.each(aggregate, function(i, aggregate) {
                        width = Math.min((aggregate.data.duration * 100 / data.duration).toFixed(2), 100);

                        aggregateTable.append('<tr><td class="' + csscls('name') + '">' +
                            aggregate.data.count + ' x ' + $('<i />').text(aggregate.label.replace(/\s+/g, ' ')).html() + ' (' + width + '%)</td><td class="' + csscls('value') + '">' +
                            '<div class="' + csscls('measure') +'">' +
                                '<span class="' + csscls('value') + '"></span>' +
                                '<span class="' + csscls('label') + '">' + formatDuration(aggregate.data.duration) + (aggregate.data.memory ? '/' + formatBytes(aggregate.data.memory) : '') + '</span>' +
                            '</div></td></tr>');
                        aggregateTable.find('span.' + csscls('value') + ':last').css({width: width + "%" });
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
    var ExceptionsWidget = PhpDebugBar.Widgets.ExceptionsWidget = PhpDebugBar.Widget.extend({

        className: csscls('exceptions'),

        render: function() {
            this.$list = new ListWidget({ itemRenderer: function(li, e) {
                $('<span />').addClass(csscls('message')).text(e.message).appendTo(li);
                if (e.file) {
                    var header = $('<span />').addClass(csscls('filename')).text(e.file + "#" + e.line);
                    if (e.xdebug_link) {
                        if (e.xdebug_link.ajax) {
                            $('<a title="' + e.xdebug_link.url + '"></a>').on('click', function () {
                                fetch(e.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(header);
                        } else {
                            $('<a href="' + e.xdebug_link.url + '"></a>').addClass(csscls('editor-link')).appendTo(header);
                        }
                    }
                    header.appendTo(li);
                }
                if (e.type) {
                    $('<span />').addClass(csscls('type')).text(e.type).appendTo(li);
                }
                if (e.surrounding_lines) {
                    var startLine = (e.line - 3) <= 0 ? 1 : e.line - 3;
                    var pre = createCodeBlock(e.surrounding_lines.join(""), 'php', startLine, e.line).addClass(csscls('file')).appendTo(li);
                    if (!e.stack_trace_html) {
                        // This click event makes the var-dumper hard to use.
                        li.click(function () {
                            if (pre.is(':visible')) {
                                pre.hide();
                            } else {
                                pre.show();
                            }
                        });
                    }
                }
                if (e.stack_trace_html) {
                    var $trace = $('<span />').addClass(csscls('filename')).html(e.stack_trace_html);
                    $trace.find('samp[data-depth="1"]').removeClass('sf-dump-expanded').addClass('sf-dump-compact').parent()
                        .find('>.sf-dump-note').html((_, t) => t.replace(/^array:/, '<span class="sf-dump-key">Stack Trace:</span> ') + ' files');
                    $trace.appendTo(li);
                } else if (e.stack_trace) {
                    e.stack_trace.split("\n").forEach(function (trace) {
                        var $traceLine = $('<div />');
                        $('<span />').addClass(csscls('filename')).text(trace).appendTo($traceLine);
                        $traceLine.appendTo(li);
                    });
                }
            }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data', data);
                if (data.length == 1) {
                    this.$list.$el.children().first().find(csscls('.file')).show();
                }
            });

        }

    });

    /**
     * Displays datasets in a table
     *
     */
    var DatasetWidget = PhpDebugBar.Widgets.DatasetWidget = PhpDebugBar.Widget.extend({

        initialize: function(options) {
            if (!options['itemRenderer']) {
                options['itemRenderer'] = this.itemRenderer;
            }
            this.set(options);
            this.set('autoshow', null);
            this.set('id', null);
            this.set('sort', localStorage.getItem('debugbar-history-sort') || 'asc');
            this.$el.addClass(csscls('dataset-history'))

            this.renderHead();
        },

        renderHead: function() {
            this.$el.empty();
            this.$actions = $('<div />').addClass(csscls('dataset-actions')).appendTo(this.$el);

            var self = this;
            var debugbar = self.get('debugbar');

            this.$autoshow = $('<input type=checkbox>')
                .on('click', function() {
                    if (debugbar.ajaxHandler) {
                        debugbar.ajaxHandler.setAutoShow($(this).is(':checked'));
                    }
                    if (debugbar.controls['__settings']) {
                        debugbar.controls['__settings'].get('widget').set('autoshow', this.autoShow);
                    }
                });

            $('<label>Autoshow</label>')
                .append(this.$autoshow)
                .appendTo(this.$actions)


            this.$clearbtn = $('<a>Clear</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.$table.empty();
                });

            this.$showBtn = $('<a>Show all</a>')
                .appendTo(this.$actions)
                .on('click', function() {
                    self.searchInput.val(null);
                    self.methodInput.val(null);
                    self.set('search', null);
                    self.set('method', null);
                });

            this.methodInput = $('<select name="method" style="width:100px"><option>(method)</option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select>')
                .on('change', function() { self.set('method', this.value)})
                .appendTo(this.$actions)

            this.searchInput = $('<input type="text" name="search" aria-label="Search" placeholder="Search" />')
                .on('input', function() { self.set('search', this.value); })
                .appendTo(this.$actions);


            this.$table = $('<tbody />');

            $('<table/>')
                .append($('<thead/>')
                    .append($('<tr/>')
                        .append($('<th></th>').css('width', '30px'))
                        .append($('<th>Date ↕</th>').css('width', '175px').click(function() {
                            self.set('sort', self.get('sort') === 'asc' ? 'desc' : 'asc')
                            localStorage.setItem('debugbar-history-sort', self.get('sort'))
                        }))
                        .append($('<th>Method</th>').css('width', '80px'))
                        .append($('<th>URL</th>'))
                        .append($('<th width="40%">Data</th>')))
                )
                .append(this.$table)
                .appendTo(this.$el);


        },

        renderDatasets: function() {
            this.$table.empty();
            var self = this;
            $.each(this.get('data'), function(key, data) {
                if (!data.__meta) {
                    return;
                }

                self.get('itemRenderer')(self, data);
            });
        },

        render: function() {
            this.bindAttr('data', function() {
                if (this.get('autoshow') === null && this.get('debugbar').ajaxHandler) {
                    this.set('autoshow', this.get('debugbar').ajaxHandler.autoShow);
                }

                if (!this.has('data')) {
                    return;
                }

                // Render the latest item
                var datasets = this.get('data');
                var data = datasets[Object.keys(datasets)[Object.keys(datasets).length - 1]]
                if (!data.__meta) {
                    return;
                }

                this.get('itemRenderer')(this, data);
           });
            this.bindAttr(['itemRenderer', 'search', 'method', 'sort'], function() {
                this.renderDatasets();
            })
            this.bindAttr('autoshow', function() {
                var autoshow = this.get('autoshow');
                this.$autoshow.prop('checked', autoshow);
            })
            this.bindAttr('id', function() {
                var id = this.get('id');
                this.$table.find('.' + csscls('active')).removeClass(csscls('active'));
                this.$table.find('tr[data-id=' + id+']').addClass(csscls('active'));
            })
        },

        /**
         * Renders the content of a dataset item
         *
         * @param {Object} value An item from the data array
         */
        itemRenderer: function(widget, data) {
            var meta = data.__meta;

            var $badges = $('<td />');
            var tr = $('<tr />');
            if (widget.get('sort') === 'asc') {
                tr.appendTo(widget.$table);
            } else {
                tr.prependTo(widget.$table);
            }

            var clickHandler = function() {
                var debugbar = widget.get('debugbar');
                debugbar.showDataSet(meta.id, debugbar.datesetTitleFormater.format('', data, meta.suffix, meta.nb));
                widget.$table.find('.' + csscls('active')).removeClass(csscls('active'));
                tr.addClass(csscls('active'));

                if ($(this).data('tab')) {
                    debugbar.showTab($(this).data('tab'));
                }
            }

            tr.attr('data-id', meta['id'])
                .append($('<td>#' + meta['nb'] + '</td>').click(clickHandler))
                .append($('<td>' + meta['datetime'] + '</td>').click(clickHandler))
                .append($('<td>' + meta['method'] + '</td>').click(clickHandler))
                .append($('<td />').append(meta['uri'] + (meta['suffix'] ? ' ' + meta['suffix'] : '')).click(clickHandler))
                .css('cursor', 'pointer')
                .addClass(csscls('table-row'))

            var debugbar = widget.get('debugbar');
            $.each(debugbar.dataMap, function(key, def) {
                var d = getDictValue(data, def[0], def[1]);
                if (key.indexOf(':') != -1) {
                    key = key.split(':');
                    if (key[1] === 'badge' && d > 0) {
                        var control = debugbar.getControl(key[0]);
                        var $a = $('<a>').attr('title', control.get('title')).data('tab', key[0]);
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

            if (debugbar.activeDatasetId === meta['id']) {
                tr.addClass(csscls('active'));
            }

            var search = widget.get('search');
            var method = widget.get('method');
            if ((search && meta['uri'].indexOf(search) == -1) || (method && meta['method'] !== method)) {
                tr.hide();
            }
        }

    });



})(PhpDebugBar.$);
