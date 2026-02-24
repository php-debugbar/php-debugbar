(function () {
    const csscls = function (cls) {
        return PhpDebugBar.utils.csscls(cls, 'phpdebugbar-openhandler-');
    };

    PhpDebugBar.OpenHandler = PhpDebugBar.Widget.extend({

        className: 'phpdebugbar-openhandler',

        defaults: {
            items_per_page: 20
        },

        render() {
            const self = this;

            document.body.append(this.el);
            this.el.style.display = 'none';

            this.closebtn = document.createElement('a');
            this.closebtn.classList.add(csscls('closebtn'));
            this.closebtn.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-x"></i>';

            this.brand = document.createElement('span');
            this.brand.classList.add(csscls('brand'));
            this.brand.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-brand"></i>';

            this.table = document.createElement('tbody');

            const header = document.createElement('div');
            header.classList.add(csscls('header'));
            header.textContent = 'PHP DebugBar | Open';
            header.prepend(this.brand);
            header.append(this.closebtn);

            this.el.append(header);

            const tableWrapper = document.createElement('table');
            tableWrapper.innerHTML = '<thead><tr><th width="155">Date</th><th width="75">Method</th><th>URL</th><th width="125">IP</th><th width="100">Filter data</th></tr></thead>';
            tableWrapper.append(this.table);
            this.el.append(tableWrapper);

            this.actions = document.createElement('div');
            this.actions.classList.add(csscls('actions'));
            this.el.append(this.actions);

            this.closebtn.addEventListener('click', () => {
                self.hide();
            });

            this.loadmorebtn = document.createElement('a');
            this.loadmorebtn.textContent = 'Load more';
            this.actions.append(this.loadmorebtn);
            this.loadmorebtn.addEventListener('click', () => {
                self.find(self.last_find_request, self.last_find_request.offset + self.get('items_per_page'), self.handleFind.bind(self));
            });

            this.showonlycurrentbtn = document.createElement('a');
            this.showonlycurrentbtn.textContent = 'Show only current URL';
            this.actions.append(this.showonlycurrentbtn);
            this.showonlycurrentbtn.addEventListener('click', () => {
                self.uriInput.value = window.location.pathname;
                self.searchBtn.click();
            });

            this.refreshbtn = document.createElement('a');
            this.refreshbtn.textContent = 'Refresh';
            this.actions.append(this.refreshbtn);
            this.refreshbtn.addEventListener('click', () => {
                self.refresh();
            });

            this.clearbtn = document.createElement('a');
            this.clearbtn.textContent = 'Clear storage';
            this.actions.append(this.clearbtn);
            this.clearbtn.addEventListener('click', () => {
                self.clear(() => {
                    self.hide();
                });
            });

            this.addSearch();

            this.overlay = document.createElement('div');
            this.overlay.classList.add(csscls('overlay'));
            this.overlay.style.display = 'none';
            document.body.append(this.overlay);
            this.overlay.addEventListener('click', () => {
                self.hide();
            });
        },

        refresh() {
            this.table.innerHTML = '';
            this.loadmorebtn.style.display = '';
            this.find({}, 0, this.handleFind.bind(this));
        },

        addSearch() {
            const self = this;

            const searchBtn = this.searchBtn = document.createElement('button');
            searchBtn.textContent = 'Search';
            searchBtn.type = 'submit';
            searchBtn.addEventListener('click', function (e) {
                self.table.innerHTML = '';
                const search = {};
                const formData = new FormData(this.parentElement);
                for (const [name, value] of formData.entries()) {
                    if (value) {
                        search[name] = value;
                    }
                }

                self.find(search, 0, self.handleFind.bind(self));
                e.preventDefault();
            });

            const form = document.createElement('form');
            form.innerHTML = '<br/><b>Filter results</b><br/>'
                + '<select name="method"><option selected value="">(Method)</option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select>';

            this.uriInput = document.createElement('input');
            this.uriInput.type = 'text';
            this.uriInput.name = 'uri';
            this.uriInput.placeholder = "URI, eg '/user/*'";
            form.append(this.uriInput);

            this.ipInput = document.createElement('input');
            this.ipInput.type = 'text';
            this.ipInput.name = 'ip';
            this.ipInput.placeholder = 'IP';
            form.append(this.ipInput);

            const resetBtn = document.createElement('button');
            resetBtn.textContent = 'Reset';
            resetBtn.type = 'button';
            resetBtn.addEventListener('click', () => {
                form.reset();
                searchBtn.click();
            });
            form.append(searchBtn);
            form.append(resetBtn);
            this.actions.append(form);
        },

        handleFind(data) {
            const self = this;
            for (const meta of data) {
                const loadLink = document.createElement('a');
                loadLink.textContent = 'Load dataset';
                loadLink.addEventListener('click', (e) => {
                    self.hide();
                    self.load(meta.id, (data) => {
                        self.callback(meta.id, data);
                    });
                    e.preventDefault();
                });

                const methodLink = document.createElement('a');
                methodLink.textContent = meta.method;
                methodLink.addEventListener('click', (e) => {
                    self.table.innerHTML = '';
                    self.find({ method: meta.method }, 0, self.handleFind.bind(self));
                    e.preventDefault();
                });

                const uriLink = document.createElement('a');
                uriLink.textContent = meta.uri;
                uriLink.addEventListener('click', (e) => {
                    self.hide();
                    self.load(meta.id, (data) => {
                        self.callback(meta.id, data);
                    });
                    e.preventDefault();
                });

                const ipLink = document.createElement('a');
                ipLink.textContent = meta.ip;
                ipLink.addEventListener('click', (e) => {
                    self.ipInput.value = meta.ip;
                    self.searchBtn.click();
                    e.preventDefault();
                });

                const searchLink = document.createElement('a');
                searchLink.textContent = 'Show URL';
                searchLink.addEventListener('click', (e) => {
                    self.uriInput.value = meta.uri;
                    self.searchBtn.click();
                    e.preventDefault();
                });

                const tr = document.createElement('tr');
                const datetimeTd = document.createElement('td');
                datetimeTd.textContent = meta.datetime;
                tr.append(datetimeTd);

                const methodTd = document.createElement('td');
                methodTd.textContent = meta.method;
                tr.append(methodTd);

                const uriTd = document.createElement('td');
                uriTd.append(uriLink);
                tr.append(uriTd);

                const ipTd = document.createElement('td');
                ipTd.append(ipLink);
                tr.append(ipTd);

                const searchTd = document.createElement('td');
                searchTd.append(searchLink);
                tr.append(searchTd);

                self.table.append(tr);
            }
            if (data.length < this.get('items_per_page')) {
                this.loadmorebtn.style.display = 'none';
            }
        },

        show(callback) {
            this.callback = callback;
            this.el.style.display = 'block';
            this.overlay.style.display = 'block';
            this.refresh();
        },

        hide() {
            this.el.style.display = 'none';
            this.overlay.style.display = 'none';
        },

        find(filters, offset, callback) {
            const data = Object.assign({ op: 'find' }, filters, { max: this.get('items_per_page'), offset: offset || 0 });
            this.last_find_request = data;
            this.ajax(data, callback);
        },

        load(id, callback) {
            this.ajax({ op: 'get', id }, callback);
        },

        clear(callback) {
            this.ajax({ op: 'clear' }, callback);
        },

        ajax(data, callback) {
            let url = this.get('url');
            if (data) {
                url = url + (url.includes('?') ? '&' : '?') + new URLSearchParams(data);
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json'
                }
            })
                .then(data => data.json())
                .then(callback)
                .catch((err) => {
                    callback(null, err);
                });
        }

    });
})();
