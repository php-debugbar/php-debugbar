(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying templates data
     *
     * Options:
     *  - data
     */
    class TemplatesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('templates');
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, tpl) {
                const name = document.createElement('span');
                name.classList.add(csscls('name'));
                if (tpl.html) {
                    name.innerHTML = tpl.html;
                } else {
                    name.textContent = tpl.name;
                }
                li.append(name);

                if (typeof tpl.xdebug_link !== 'undefined' && tpl.xdebug_link !== null) {
                    const header = document.createElement('span');
                    header.classList.add(csscls('filename'));
                    header.textContent = tpl.xdebug_link.filename + (tpl.xdebug_link.line ? `#${tpl.xdebug_link.line}` : '');

                    if (tpl.xdebug_link) {
                        const link = document.createElement('a');
                        link.setAttribute('href', tpl.xdebug_link.url);
                        link.classList.add(csscls('editor-link'));
                        link.addEventListener('click', (event) => {
                            event.stopPropagation();
                            if (tpl.xdebug_link.ajax) {
                                fetch(tpl.xdebug_link.url);
                                event.preventDefault();
                            }
                        });
                        header.append(link);
                    }
                    li.append(header);
                }

                if (tpl.render_time_str) {
                    const renderTime = document.createElement('span');
                    renderTime.setAttribute('title', 'Render time');
                    renderTime.classList.add(csscls('render-time'));
                    renderTime.textContent = tpl.render_time_str;
                    li.append(renderTime);
                }
                if (tpl.memory_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = tpl.memory_str;
                    li.append(memory);
                }
                if (typeof (tpl.param_count) !== 'undefined') {
                    const paramCount = document.createElement('span');
                    paramCount.setAttribute('title', 'Parameter count');
                    paramCount.classList.add(csscls('param-count'));
                    paramCount.textContent = tpl.param_count;
                    li.append(paramCount);
                }
                if (typeof (tpl.type) !== 'undefined' && tpl.type) {
                    const type = document.createElement('span');
                    type.setAttribute('title', 'Type');
                    type.classList.add(csscls('type'));
                    type.textContent = tpl.type;
                    li.append(type);
                }
                if (typeof (tpl.editorLink) !== 'undefined' && tpl.editorLink) {
                    const editorLink = document.createElement('a');
                    editorLink.setAttribute('href', tpl.editorLink);
                    editorLink.classList.add(csscls('editor-link'));
                    editorLink.textContent = 'file';
                    editorLink.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                    li.append(editorLink);
                }
                if (tpl.params && Object.keys(tpl.params).length > 0) {
                    const table = document.createElement('table');
                    table.classList.add(csscls('params'));
                    const thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th colspan="2">Params</th></tr>';
                    const tbody = document.createElement('tbody');
                    table.append(thead, tbody);

                    for (const key in tpl.params) {
                        if (typeof tpl.params[key] !== 'function') {
                            const row = document.createElement('tr');
                            row.innerHTML = `<td class="${csscls('name')}">${key}</td><td class="${csscls('value')}"><pre><code>${tpl.params[key]}</code></pre></td>`;
                            tbody.append(row);
                        }
                    }
                    table.hidden = true;
                    li.append(table);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        table.hidden = !table.hidden;
                    });
                }
            } });
            this.el.append(this.list.el);

            this.callgraph = document.createElement('div');
            this.callgraph.classList.add(csscls('callgraph'));
            this.el.append(this.callgraph);

            this.bindAttr('data', function (data) {
                this.list.set('data', data.templates);
                this.status.innerHTML = '';
                this.callgraph.innerHTML = '';

                const sentence = data.sentence || 'templates were rendered';
                const sentenceSpan = document.createElement('span');
                sentenceSpan.textContent = `${data.nb_templates} ${sentence}`;
                this.status.append(sentenceSpan);

                if (data.accumulated_render_time_str) {
                    const renderTime = document.createElement('span');
                    renderTime.setAttribute('title', 'Accumulated render time');
                    renderTime.classList.add(csscls('render-time'));
                    renderTime.textContent = data.accumulated_render_time_str;
                    this.status.append(renderTime);
                }
                if (data.memory_usage_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = data.memory_usage_str;
                    this.status.append(memory);
                }
                if (data.nb_blocks > 0) {
                    const blocksDiv = document.createElement('div');
                    blocksDiv.textContent = `${data.nb_blocks} blocks were rendered`;
                    this.status.append(blocksDiv);
                }
                if (data.nb_macros > 0) {
                    const macrosDiv = document.createElement('div');
                    macrosDiv.textContent = `${data.nb_macros} macros were rendered`;
                    this.status.append(macrosDiv);
                }
                if (typeof data.callgraph !== 'undefined') {
                    this.callgraph.innerHTML = data.callgraph;
                }
            });
        }
    }
    PhpDebugBar.Widgets.TemplatesWidget = TemplatesWidget;
})();
