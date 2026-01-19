/* global phpdebugbar_sqlformatter */
(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    class SQLQueriesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('sqlqueries');
        }

        onFilterClick(el) {
            el.classList.toggle(csscls('excluded'));
            const connection = el.getAttribute('rel');
            const items = this.list.el.querySelectorAll(`li[connection="${connection}"]`);
            for (const item of items) {
                item.hidden = !item.hidden;
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

        renderList(table, caption, data) {
            const thead = document.createElement('thead');

            const tr = document.createElement('tr');
            const nameTh = document.createElement('th');
            nameTh.colSpan = 2;
            nameTh.classList.add(csscls('name'));
            nameTh.innerHTML = caption;
            tr.append(nameTh);
            thead.append(tr);

            table.append(thead);

            const tbody = document.createElement('tbody');

            for (const key in data) {
                const value = typeof data[key] === 'function' ? `${data[key].name} {}` : data[key];
                const tr = document.createElement('tr');

                if (typeof value === 'object' && value !== null) {
                    const keyTd = document.createElement('td');
                    keyTd.classList.add('phpdebugbar-text-muted');
                    keyTd.textContent = value.index || key;
                    tr.append(keyTd);

                    const valueTd = document.createElement('td');
                    if (value.namespace) {
                        valueTd.append(`${value.namespace}::`);
                    }

                    valueTd.append(value.name || value.file);

                    if (value.line) {
                        const lineSpan = document.createElement('span');
                        lineSpan.classList.add('phpdebugbar-text-muted');
                        lineSpan.textContent = `:${value.line}`;
                        valueTd.append(lineSpan);
                    }
                } else {
                    const keyTd = document.createElement('td');
                    keyTd.classList.add('phpdebugbar-text-muted');
                    keyTd.textContent = key;
                    tr.append(keyTd);

                    const valueTd = document.createElement('td');
                    valueTd.classList.add('phpdebugbar-text-muted');
                    valueTd.textContent = value;
                    tr.append(valueTd);
                }

                tbody.append(tr);
            }

            table.append(tbody);
        }

        itemRenderer(li, stmt) {
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

                if (!this.filters.includes(stmt.connection)) {
                    this.filters.push(stmt.connection);

                    const filterLink = document.createElement('a');
                    filterLink.classList.add(csscls('filter'));
                    filterLink.textContent = stmt.connection;
                    filterLink.setAttribute('rel', stmt.connection);
                    filterLink.addEventListener('click', () => {
                        this.onFilterClick(filterLink);
                    });
                    this.toolbar.append(filterLink);

                    if (this.filters.length > 1) {
                        this.toolbar.hidden = false;
                    }
                }
            }
            if ((!stmt.type || stmt.type === 'query')) {
                const copyBtn = document.createElement('span');
                copyBtn.setAttribute('title', 'Copy to clipboard');
                copyBtn.classList.add(csscls('copy-clipboard'));
                copyBtn.style.cursor = 'pointer';
                copyBtn.innerHTML = '&#8203;';
                copyBtn.addEventListener('click', (event) => {
                    this.onCopyToClipboard(copyBtn);
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
                li.prepend(header);
            } else if (typeof (stmt.filename) !== 'undefined' && stmt.filename) {
                const header = document.createElement('span');
                header.setAttribute('title', 'Filename');
                header.classList.add(csscls('filename'));
                header.textContent = stmt.filename;
                li.prepend(header);
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
            table.hidden = true;

            if (stmt.params && Object.keys(stmt.params).length > 0) {
                this.renderList(table, 'Params', stmt.params);
            }
            if (stmt.backtrace && Object.keys(stmt.backtrace).length > 0) {
                const values = [];
                for (const trace of stmt.backtrace.values()) {
                    let text = trace.name || trace.file;
                    if (trace.line) {
                        text = `${text}:${trace.line}`;
                    }
                    values.push(text);
                }
                this.renderList(table, 'Backtrace', values);
            }
            if (table.querySelectorAll('tr').length) {
                li.append(table);
                li.style.cursor = 'pointer';
                li.addEventListener('click', () => {
                    if (window.getSelection().type === 'Range') {
                        return '';
                    }
                    table.hidden = !table.hidden;
                    const code = li.querySelector('code');
                    if (code && typeof phpdebugbar_sqlformatter !== 'undefined') {
                        let sql = stmt.sql;
                        if (!table.hidden) {
                            sql = phpdebugbar_sqlformatter.format(stmt.sql);
                        }
                        code.innerHTML = PhpDebugBar.Widgets.highlight(sql, 'sql');
                    }
                });
            }
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.toolbar = document.createElement('div');
            this.toolbar.classList.add(csscls('toolbar'));
            this.el.append(this.toolbar);

            this.filters = [];
            let sortState = 'none'; // 'none', 'asc', 'desc'
            let originalData = null;

            this.list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: (li, stmt) => this.itemRenderer(li, stmt)
            });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                // the PDO collector maybe is empty
                if (data.length <= 0 || !data.statements) {
                    return false;
                }
                this.filters = [];
                this.toolbar.hidden = true;
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
                    toggleLink.addEventListener('click', () => {
                        toggleLink.classList.toggle('shown-duplicated');
                        toggleLink.textContent = toggleLink.classList.contains('shown-duplicated') ? 'Show All' : duplicatedText;

                        const selector = `.${this.className} .${csscls('list-item')}:not(.${csscls('sql-duplicate')})`;
                        const items = document.querySelectorAll(selector);
                        for (const item of items) {
                            item.hidden = !item.hidden;
                        }
                    });
                    t.append(toggleLink);
                }
                if (data.accumulated_duration_str) {
                    const duration = document.createElement('span');
                    duration.setAttribute('title', 'Accumulated duration');
                    duration.classList.add(csscls('duration'));
                    duration.textContent = data.accumulated_duration_str;

                    const sortIcon = document.createElement('span');
                    sortIcon.classList.add(csscls('sort-icon'));
                    sortIcon.style.cursor = 'pointer';
                    sortIcon.style.marginLeft = '5px';
                    sortIcon.textContent = 'Sort ⇅';
                    sortIcon.setAttribute('title', 'Sort by duration');

                    sortIcon.addEventListener('click', () => {
                        if (sortState === 'none') {
                            sortState = 'desc';
                            sortIcon.textContent = '↓';
                            originalData = [...data.statements];
                            data.statements.sort((a, b) => (b.duration || 0) - (a.duration || 0));
                        } else if (sortState === 'desc') {
                            sortState = 'asc';
                            sortIcon.textContent = '↑';
                            data.statements.sort((a, b) => (a.duration || 0) - (b.duration || 0));
                        } else {
                            sortState = 'none';
                            sortIcon.textContent = '⇅';
                            if (originalData) {
                                data.statements = originalData;
                                originalData = null;
                            }
                        }
                        this.list.set('data', data.statements);
                    });

                    duration.append(sortIcon);
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
