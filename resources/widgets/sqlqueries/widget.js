(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    class SQLQueriesWidget extends PhpDebugBar.Widget {
        get className() { return csscls('sqlqueries'); }

        onFilterClick(el) {
            el.classList.toggle(csscls('excluded'));
            const connection = el.getAttribute('rel');
            const items = this.list.el.querySelectorAll(`li[connection="${connection}"]`);
            for (const item of items) {
                item.style.display = item.style.display === 'none' ? '' : 'none';
            }
        }

        onCopyToClipboard(el) {
            const code = el.parentElement.querySelector('code');
            const copy = function () {
                try {
                    if (document.execCommand('copy')) {
                        el.classList.add(csscls('copy-clipboard-check'));
                        setTimeout(() => {
                            el.classList.remove(csscls('copy-clipboard-check'));
                        }, 2000);
                    }
                } catch (err) {
                    console.log('Oops, unable to copy');
                }
            };
            const select = function (node) {
                if (document.selection) {
                    const range = document.body.createTextRange();
                    range.moveToElementText(node);
                    range.select();
                } else if (window.getSelection) {
                    const range = document.createRange();
                    range.selectNodeContents(node);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                }
                copy();
                window.getSelection().removeAllRanges();
            };
            select(code);
        }

        renderList(caption, icon, data) {
            const ul = document.createElement('ul');
            ul.classList.add(csscls('table-list'));

            for (const key in data) {
                const value = typeof data[key] === 'function' ? `${data[key].name} {}` : data[key];
                const li = document.createElement('li');
                li.classList.add(csscls('table-list-item'));

                if (typeof value === 'object' && value !== null) {
                    const keySpan = document.createElement('span');
                    keySpan.classList.add('phpdebugbar-text-muted');
                    keySpan.textContent = value.index || key;
                    keySpan.append('.');
                    li.append(keySpan);
                    li.append('\u00A0');

                    if (value.namespace) {
                        li.append(`${value.namespace}::`);
                    }

                    li.append(value.name || value.file);

                    if (value.line) {
                        const lineSpan = document.createElement('span');
                        lineSpan.classList.add('phpdebugbar-text-muted');
                        lineSpan.textContent = `:${value.line}`;
                        li.append(lineSpan);
                    }
                } else {
                    const keySpan = document.createElement('span');
                    keySpan.classList.add('phpdebugbar-text-muted');
                    keySpan.textContent = `${key}:`;
                    li.append(keySpan);
                    li.append('\u00A0');

                    const valueSpan = document.createElement('span');
                    valueSpan.classList.add('phpdebugbar-text-muted');
                    valueSpan.textContent = value;
                    li.append(valueSpan.innerHTML);
                }

                ul.append(li);
            }

            caption += icon ? ` <i class="phpdebugbar-fa phpdebugbar-fa-${icon} phpdebugbar-text-muted"></i>` : '';

            const tr = document.createElement('tr');
            const nameTd = document.createElement('td');
            nameTd.classList.add(csscls('name'));
            nameTd.innerHTML = caption;
            tr.append(nameTd);

            const valueTd = document.createElement('td');
            valueTd.classList.add(csscls('value'));
            valueTd.append(ul);
            tr.append(valueTd);

            return tr;
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.toolbar = document.createElement('div');
            this.toolbar.classList.add(csscls('toolbar'));
            this.el.append(this.toolbar);

            let filters = [];
            const self = this;

            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, stmt) {
                if (stmt.slow) {
                    li.classList.add(csscls('sql-slow'));
                }
                if (stmt.width_percent) {
                    const bgMeasure = document.createElement('div');
                    bgMeasure.classList.add(csscls('bg-measure'));

                    const valueDiv = document.createElement('div');
                    valueDiv.classList.add(csscls('value'));
                    valueDiv.style.left = `${stmt.start_percent}%`;
                    valueDiv.style.width = `${Math.max(stmt.width_percent, 0.01)}%`;
                    bgMeasure.append(valueDiv);
                    li.append(bgMeasure);
                }
                if (stmt.duration_str) {
                    const duration = document.createElement('span');
                    duration.setAttribute('title', 'Duration');
                    duration.classList.add(csscls('duration'));
                    duration.textContent = stmt.duration_str;
                    li.append(duration);
                }
                if (stmt.memory_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = stmt.memory_str;
                    li.append(memory);
                }
                if (typeof (stmt.row_count) !== 'undefined') {
                    const rowCount = document.createElement('span');
                    rowCount.setAttribute('title', 'Row count');
                    rowCount.classList.add(csscls('row-count'));
                    rowCount.textContent = stmt.row_count;
                    li.append(rowCount);
                }
                if (typeof (stmt.stmt_id) !== 'undefined' && stmt.stmt_id) {
                    const stmtId = document.createElement('span');
                    stmtId.setAttribute('title', 'Prepared statement ID');
                    stmtId.classList.add(csscls('stmt-id'));
                    stmtId.textContent = stmt.stmt_id;
                    li.append(stmtId);
                }
                if (stmt.connection) {
                    const database = document.createElement('span');
                    database.setAttribute('title', 'Connection');
                    database.classList.add(csscls('database'));
                    database.textContent = stmt.connection;
                    li.append(database);
                    li.setAttribute('connection', stmt.connection);

                    if (!filters.includes(stmt.connection)) {
                        filters.push(stmt.connection);

                        const filterLink = document.createElement('a');
                        filterLink.classList.add(csscls('filter'));
                        filterLink.textContent = stmt.connection;
                        filterLink.setAttribute('rel', stmt.connection);
                        filterLink.addEventListener('click', function () {
                            self.onFilterClick(this);
                        });
                        self.toolbar.append(filterLink);

                        if (filters.length > 1) {
                            self.toolbar.style.display = '';
                            self.list.el.style.marginBottom = '20px';
                        }
                    }
                }
                if ((!stmt.type || stmt.type === 'query')) {
                    const copyBtn = document.createElement('span');
                    copyBtn.setAttribute('title', 'Copy to clipboard');
                    copyBtn.classList.add(csscls('copy-clipboard'));
                    copyBtn.style.cursor = 'pointer';
                    copyBtn.innerHTML = '&#8203;';
                    copyBtn.addEventListener('click', function (event) {
                        self.onCopyToClipboard(this);
                        event.stopPropagation();
                    });
                    li.append(copyBtn);
                }
                if (typeof (stmt.xdebug_link) !== 'undefined' && stmt.xdebug_link) {
                    const header = document.createElement('span');
                    header.setAttribute('title', 'Filename');
                    header.classList.add(csscls('filename'));
                    header.textContent = stmt.xdebug_link.filename + (stmt.xdebug_link.line ? `#${stmt.xdebug_link.line}` : '');

                    const link = document.createElement('a');
                    link.setAttribute('href', stmt.xdebug_link.url);
                    link.classList.add(csscls('editor-link'));
                    link.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (stmt.xdebug_link.ajax) {
                            fetch(stmt.xdebug_link.url);
                            event.preventDefault();
                        }
                    });
                    header.append(link);
                    li.append(header);
                }
                if (stmt.type === 'transaction') {
                    const strong = document.createElement('strong');
                    strong.classList.add(csscls('sql'), csscls('name'));
                    strong.textContent = stmt.sql;
                    li.append(strong);
                } else {
                    const code = document.createElement('code');
                    code.classList.add(csscls('sql'));
                    code.innerHTML = PhpDebugBar.Widgets.highlight(stmt.sql, 'sql');
                    li.append(code);
                }
                if (typeof (stmt.is_success) !== 'undefined' && !stmt.is_success) {
                    li.classList.add(csscls('error'));
                    const errorSpan = document.createElement('span');
                    errorSpan.classList.add(csscls('error'));
                    errorSpan.textContent = `[${stmt.error_code}] ${stmt.error_message}`;
                    li.append(errorSpan);
                }
                const table = document.createElement('table');
                table.classList.add(csscls('params'));

                if (stmt.params && Object.keys(stmt.params).length > 0) {
                    table.append(self.renderList('Params', 'thumb-tack', stmt.params));
                }
                if (stmt.bindings && Object.keys(stmt.bindings).length > 0) {
                    table.append(self.renderList('Bindings', 'thumb-tack', stmt.bindings));
                }
                if (stmt.hints && Object.keys(stmt.hints).length > 0) {
                    table.append(self.renderList('Hints', 'question-circle', stmt.hints));
                }
                if (stmt.backtrace && Object.keys(stmt.backtrace).length > 0) {
                    table.append(self.renderList('Backtrace', 'list-ul', stmt.backtrace));
                }
                if (table.querySelectorAll('tr').length) {
                    li.append(table);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', () => {
                        if (window.getSelection().type === 'Range') {
                            return '';
                        }
                        if (table.style.display !== 'none') {
                            table.style.display = 'none';
                        } else {
                            table.style.display = '';
                        }
                    });
                }
            } });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                // the PDO collector maybe is empty
                if (data.length <= 0 || !data.statements) {
                    return false;
                }
                filters = [];
                this.toolbar.style.display = 'none';
                const toolbarFilters = this.toolbar.querySelectorAll(`.${csscls('filter')}`);
                for (const filter of toolbarFilters) {
                    filter.remove();
                }
                this.list.set('data', data.statements);
                this.status.innerHTML = '';

                // Search for duplicate statements.
                const sql = {};
                let duplicate = 0;
                for (let i = 0; i < data.statements.length; i++) {
                    if (data.statements[i].type && data.statements[i].type !== 'query') {
                        continue;
                    }
                    let stmt = data.statements[i].sql;
                    if (data.statements[i].params && Object.keys(data.statements[i].params).length > 0) {
                        stmt += JSON.stringify(data.statements[i].params);
                    }
                    if (data.statements[i].bindings && Object.keys(data.statements[i].bindings).length > 0) {
                        stmt += JSON.stringify(data.statements[i].bindings);
                    }
                    if (data.statements[i].connection) {
                        stmt += `@${data.statements[i].connection}`;
                    }
                    sql[stmt] = sql[stmt] || { keys: [] };
                    sql[stmt].keys.push(i);
                }
                // Add classes to all duplicate SQL statements.
                for (const stmt in sql) {
                    if (sql[stmt].keys.length > 1) {
                        duplicate += sql[stmt].keys.length;
                        for (let i = 0; i < sql[stmt].keys.length; i++) {
                            const listItems = this.list.el.querySelectorAll(`.${csscls('list-item')}`);
                            if (listItems[sql[stmt].keys[i]]) {
                                listItems[sql[stmt].keys[i]].classList.add(csscls('sql-duplicate'));
                            }
                        }
                    }
                }

                const t = document.createElement('span');
                t.textContent = `${data.nb_statements} statements were executed`;
                this.status.append(t);

                if (data.nb_failed_statements) {
                    t.append(`, ${data.nb_failed_statements} of which failed`);
                }
                if (duplicate) {
                    t.append(`, ${duplicate} of which were duplicates`);
                    t.append(`, ${data.nb_statements - duplicate} unique. `);

                    // add toggler for displaying only duplicated queries
                    const duplicatedText = 'Show only duplicated';
                    const toggleLink = document.createElement('a');
                    toggleLink.classList.add(csscls('duplicates'));
                    toggleLink.textContent = duplicatedText;
                    toggleLink.addEventListener('click', function () {
                        this.classList.toggle('shown-duplicated');
                        this.textContent = this.classList.contains('shown-duplicated') ? 'Show All' : duplicatedText;

                        const selector = `.${self.className} .${csscls('list-item')}:not(.${csscls('sql-duplicate')})`;
                        const items = document.querySelectorAll(selector);
                        for (const item of items) {
                            item.style.display = item.style.display === 'none' ? '' : 'none';
                        }
                    });
                    t.append(toggleLink);
                }
                if (data.accumulated_duration_str) {
                    const duration = document.createElement('span');
                    duration.setAttribute('title', 'Accumulated duration');
                    duration.classList.add(csscls('duration'));
                    duration.textContent = data.accumulated_duration_str;
                    this.status.append(duration);
                }
                if (data.memory_usage_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = data.memory_usage_str;
                    this.status.append(memory);
                }
            });
        }
    }
    PhpDebugBar.Widgets.SQLQueriesWidget = SQLQueriesWidget;
})();
