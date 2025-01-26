(function($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    var SQLQueriesWidget = PhpDebugBar.Widgets.SQLQueriesWidget = PhpDebugBar.Widget.extend({

        className: csscls('sqlqueries'),

        onFilterClick: function(el) {
            $(el).toggleClass(csscls('excluded'));
            this.$list.$el.find("li[connection=" + $(el).attr("rel") + "]").toggle();
        },
        onCopyToClipboard: function (el) {
            var code = $(el).parent('li').find('code').get(0);
            var copy = function () {
                try {
                    if (document.execCommand('copy')) {
                        $(el).addClass(csscls('copy-clipboard-check'));
                        setTimeout(function(){
                            $(el).removeClass(csscls('copy-clipboard-check'));
                        }, 2000)
                    }
                } catch (err) {
                    console.log('Oops, unable to copy');
                }
            };
            var select = function (node) {
                if (document.selection) {
                    var range = document.body.createTextRange();
                    range.moveToElementText(node);
                    range.select();
                } else if (window.getSelection) {
                    var range = document.createRange();
                    range.selectNodeContents(node);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                }
                copy();
                window.getSelection().removeAllRanges();
            };
            select(code);
        },
        renderList: function (caption, icon, data) {
            var $ul = $('<ul />').addClass(csscls('table-list')), $parts;
            var $li = $('<li />').addClass(csscls('table-list-item'));
            var $span = $('<span />').addClass('phpdebugbar-text-muted');
            for (var key in data) {
                var value = typeof data[key] === 'function' ? data[key].name + ' {}' : data[key];
                $li.clone().append(typeof value === 'object' && value !== null
                    ? [$span.clone().text(value.index || key).append('.'), '&nbsp;']
                        .concat(value.namespace ? [value.namespace + '::'] : [])
                        .concat([value.name || value.file])
                        .concat(value.line ? [$span.clone().text(':' + value.line)] : [])
                    : [$span.clone().text(key + ':'), '&nbsp;', value]
                ).appendTo($ul);
            }
            caption += icon ? ' <i class="phpdebugbar-fa phpdebugbar-fa-' + icon + ' phpdebugbar-text-muted"></i>' : '';
            return $('<tr />').append(
                $('<td />').addClass(csscls('name')).html(caption),
                $('<td />').addClass(csscls('value')).append($ul)
            );
        },
        render: function() {
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);

            this.$toolbar = $('<div />').addClass(csscls('toolbar')).appendTo(this.$el);

            var filters = [], self = this;

            this.$list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer: function(li, stmt) {
                if (stmt.type === 'transaction') {
                    $('<strong />').addClass(csscls('sql')).addClass(csscls('name')).text(stmt.sql).appendTo(li);
                } else {
                    $('<code />').addClass(csscls('sql')).html(PhpDebugBar.Widgets.highlight(stmt.sql, 'sql')).appendTo(li);
                }
                if (stmt.width_percent) {
                    $('<div />').addClass(csscls('bg-measure')).append(
                        $('<div />').addClass(csscls('value')).css({
                            left: stmt.start_percent + '%',
                            width: Math.max(stmt.width_percent, 0.01) + '%',
                        })
                    ).appendTo(li);
                }
                if (stmt.duration_str) {
                    $('<span title="Duration" />').addClass(csscls('duration')).text(stmt.duration_str).appendTo(li);
                }
                if (stmt.memory_str) {
                    $('<span title="Memory usage" />').addClass(csscls('memory')).text(stmt.memory_str).appendTo(li);
                }
                if (typeof(stmt.row_count) != 'undefined') {
                    $('<span title="Row count" />').addClass(csscls('row-count')).text(stmt.row_count).appendTo(li);
                }
                if (typeof(stmt.stmt_id) != 'undefined' && stmt.stmt_id) {
                    $('<span title="Prepared statement ID" />').addClass(csscls('stmt-id')).text(stmt.stmt_id).appendTo(li);
                }
                if (stmt.connection) {
                    $('<span title="Connection" />').addClass(csscls('database')).text(stmt.connection).appendTo(li);
                    li.attr("connection",stmt.connection);
                    if ( $.inArray(stmt.connection, filters) == -1 ) {
                        filters.push(stmt.connection);
                        $('<a />')
                            .addClass(csscls('filter'))
                            .text(stmt.connection)
                            .attr('rel', stmt.connection)
                            .on('click', function() { self.onFilterClick(this); })
                            .appendTo(self.$toolbar);
                        if (filters.length>1) {
                            self.$toolbar.show();
                            self.$list.$el.css("margin-bottom","20px");
                        }
                    }
                }
                if (typeof(stmt.is_success) != 'undefined' && !stmt.is_success) {
                    li.addClass(csscls('error'));
                    li.append($('<span />').addClass(csscls('error')).text("[" + stmt.error_code + "] " + stmt.error_message));
                }
                if ((!stmt.type || stmt.type === 'query')) {
                    $('<span title="Copy to clipboard" />')
                        .addClass(csscls('copy-clipboard'))
                        .css('cursor', 'pointer')
                        .html("&#8203;")
                        .on('click', function (event) {
                            self.onCopyToClipboard(this);
                            event.stopPropagation();
                        })
                        .appendTo(li);
                }
                if (typeof(stmt.xdebug_link) !== 'undefined' && stmt.xdebug_link) {
                    var header = $('<span title="Filename" />').addClass(csscls('filename')).text(stmt.xdebug_link.filename + ( stmt.xdebug_link.line ? "#" + stmt.xdebug_link.line : ''));
                    $('<a href="' + stmt.xdebug_link.url + '"></a>').on('click', function () {
                        event.stopPropagation();
                        if (stmt.xdebug_link.ajax) {                            
                            fetch(stmt.xdebug_link.url);
                            event.preventDefault();
                        }
                    }).addClass(csscls('editor-link')).appendTo(header);
                    header.appendTo(li);
                }
                var table = $('<table></table>').addClass(csscls('params'));
                if (stmt.params && !$.isEmptyObject(stmt.params)) {
                    self.renderList('Params', 'thumb-tack', stmt.params).appendTo(table);
                }
                if (stmt.bindings && !$.isEmptyObject(stmt.bindings)) {
                    self.renderList('Bindings', 'thumb-tack', stmt.bindings).appendTo(table);
                }
                if (stmt.hints && !$.isEmptyObject(stmt.hints)) {
                    self.renderList('Hints', 'question-circle', stmt.hints).appendTo(table);
                }
                if (stmt.backtrace && !$.isEmptyObject(stmt.backtrace)) {
                    self.renderList('Backtrace', 'list-ul', stmt.backtrace).appendTo(table);
                }
                if (table.find('tr').length) {
                    table.appendTo(li);
                    li.css('cursor', 'pointer').click(function() {
                        if (window.getSelection().type == "Range") {
                            return''
                        }
                        if (table.is(':visible')) {
                            table.hide();
                        } else {
                            table.show();
                        }
                    });
                }
            }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                // the PDO collector maybe is empty
                if (data.length <= 0 || !data.statements) {
                    return false;
                }
                filters = [];
                this.$toolbar.hide().find(csscls('.filter')).remove();
                this.$list.set('data', data.statements);
                this.$status.empty();

                // Search for duplicate statements.
                for (var sql = {}, duplicate = 0, i = 0; i < data.statements.length; i++) {
                    if (data.statements[i].type && data.statements[i].type !== 'query') {
                        continue;
                    }
                    var stmt = data.statements[i].sql;
                    if (data.statements[i].params && !$.isEmptyObject(data.statements[i].params)) {
                        stmt += JSON.stringify(data.statements[i].params);
                    }
                    if (data.statements[i].bindings && !$.isEmptyObject(data.statements[i].bindings)) {
                        stmt += JSON.stringify(data.statements[i].bindings);
                    }
                    if (data.statements[i].connection) {
                        stmt += '@' + data.statements[i].connection;
                    }
                    sql[stmt] = sql[stmt] || { keys: [] };
                    sql[stmt].keys.push(i);
                }
                // Add classes to all duplicate SQL statements.
                for (var stmt in sql) {
                    if (sql[stmt].keys.length > 1) {
                        duplicate += sql[stmt].keys.length;
                        for (var i = 0; i < sql[stmt].keys.length; i++) {
                            this.$list.$el.find('.' + csscls('list-item')).eq(sql[stmt].keys[i])
                                .addClass(csscls('sql-duplicate'));
                        }
                    }
                }

                var t = $('<span />').text(data.nb_statements + " statements were executed").appendTo(this.$status);
                if (data.nb_failed_statements) {
                    t.append(", " + data.nb_failed_statements + " of which failed");
                }
                if (duplicate) {
                    t.append(", " + duplicate + " of which were duplicates");
                    t.append(", " + (data.nb_statements - duplicate) + " unique. ");

                    // add toggler for displaying only duplicated queries
                    var duplicatedText = 'Show only duplicated';
                    $('<a />').addClass(csscls('duplicates')).click(function () {
                        $(this).toggleClass('shown-duplicated')
                            .text($(this).hasClass('shown-duplicated') ? 'Show All' : duplicatedText);
                        $('.' + self.className + ' .' + csscls('list-item'))
                            .not('.' + csscls('sql-duplicate')).toggle();
                    }).text(duplicatedText).appendTo(t);
                }
                if (data.accumulated_duration_str) {
                    this.$status.append($('<span title="Accumulated duration" />').addClass(csscls('duration')).text(data.accumulated_duration_str));
                }
                if (data.memory_usage_str) {
                    this.$status.append($('<span title="Memory usage" />').addClass(csscls('memory')).text(data.memory_usage_str));
                }
            });
        }

    });

})(PhpDebugBar.$);
