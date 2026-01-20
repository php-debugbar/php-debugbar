window.PhpDebugBar = window.PhpDebugBar || {};

(function () {
    const PhpDebugBar = window.PhpDebugBar;
    PhpDebugBar.utils = PhpDebugBar.utils || {};

    /**
     * Returns the value from an object property.
     * Using dots in the key, it is possible to retrieve nested property values.
     *
     * Note: This returns `defaultValue` only when the path is missing (null/undefined),
     * not when the value is falsy (0/false/"").
     *
     * @param {Record<string, any>} dict
     * @param {string} key
     * @param {any} [defaultValue]
     * @returns {any}
     */
    const getDictValue = PhpDebugBar.utils.getDictValue = function (dict, key, defaultValue) {
        if (dict === null || dict === undefined) {
            return defaultValue;
        }

        const parts = String(key).split('.');
        let d = dict;

        for (const part of parts) {
            if (d === null || d === undefined) {
                return defaultValue;
            }
            d = d[part];
            if (d === undefined) {
                return defaultValue;
            }
        }

        return d;
    };

    /**
     * Returns a prefixed CSS class name (or selector).
     *
     * If `cls` contains spaces, each class is prefixed.
     * If `cls` starts with ".", the dot is preserved (selector form).
     *
     * @param {string} cls
     * @param {string} prefix
     * @returns {string}
     */
    PhpDebugBar.utils.csscls = function (cls, prefix) {
        const s = String(cls).trim();

        if (s.includes(' ')) {
            return s
                .split(/\s+/)
                .filter(Boolean)
                .map(c => PhpDebugBar.utils.csscls(c, prefix))
                .join(' ');
        }

        if (s.startsWith('.')) {
            return `.${prefix}${s.slice(1)}`;
        }

        return prefix + s;
    };

    /**
     * Creates a partial function of csscls where the second
     * argument is already defined
     *
     * @param  {string} prefix
     * @return {Function}
     */
    PhpDebugBar.utils.makecsscls = function (prefix) {
        return cls => PhpDebugBar.utils.csscls(cls, prefix);
    };

    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-');

    PhpDebugBar.utils.sfDump = function (el) {
        if (typeof window.Sfdump == 'function') {
            el.querySelectorAll('pre.sf-dump[id]').forEach((pre) => {
                window.Sfdump(pre.id, { maxDepth: 0 });
            });
        }
    };

    PhpDebugBar.utils.schedule = function (cb) {
        if (window.requestIdleCallback) {
            return window.requestIdleCallback(cb, { timeout: 1000 });
        }

        return setTimeout(cb, 0);
    };

    // ------------------------------------------------------------------

    /**
     * Base class for all elements with a visual component
     */
    class Widget {
        get tagName() {
            return 'div';
        }

        constructor(options = {}) {
            this._attributes = { ...this.defaults };
            this._boundAttributes = {};
            this.el = document.createElement(this.tagName);
            if (this.className) {
                this.el.classList.add(...this.className.split(' '));
            }
            this.initialize(options);
            this.render();
        }

        /**
         * Called after the constructor
         *
         * @param {object} options
         */
        initialize(options) {
            this.set(options);
        }

        /**
         * Called after the constructor to render the element
         */
        render() {}

        /**
         * Sets the value of an attribute
         *
         * @param {string | object} attr Attribute name or object with multiple attributes
         * @param {*} [value] Attribute value (optional if attr is an object)
         */
        set(attr, value) {
            const attrs = typeof attr === 'string' ? { [attr]: value } : attr;

            const callbacks = [];
            for (const attr in attrs) {
                value = attrs[attr];
                this._attributes[attr] = value;
                if (this._boundAttributes[attr]) {
                    for (const callback of this._boundAttributes[attr]) {
                        // Make sure to run the callback only once per attribute change
                        if (!callbacks.includes(callback)) {
                            callback.call(this, value);
                            callbacks.push(callback);
                        }
                    }
                }
            }
        }

        /**
         * Checks if an attribute exists and is not null
         *
         * @param {string} attr
         * @return {boolean}
         */
        has(attr) {
            return this._attributes[attr] !== undefined && this._attributes[attr] !== null;
        }

        /**
         * Returns the value of an attribute
         *
         * @param {string} attr
         * @return {*}
         */
        get(attr) {
            return this._attributes[attr];
        }

        /**
         * Registers a callback function that will be called whenever the value of the attribute changes
         *
         * If cb is a HTMLElement element, textContent will be used to fill the element
         *
         * @param {string | Array} attr
         * @param {Function | HTMLElement} cb
         */
        bindAttr(attr, cb) {
            if (Array.isArray(attr)) {
                for (const a of attr) {
                    this.bindAttr(a, cb);
                }
                return;
            }

            if (!this._boundAttributes[attr]) {
                this._boundAttributes[attr] = [];
            }
            if (cb instanceof HTMLElement) {
                const el = cb;
                cb = value => el.textContent = value || '';
            }
            this._boundAttributes[attr].push(cb);
            if (this.has(attr)) {
                cb.call(this, this._attributes[attr]);
            }
        }

        /**
         * Creates a subclass
         *
         * Code from Backbone.js
         *
         * @param {object} props Prototype properties
         * @return {Function}
         */
        static extend(props) {
            const Parent = this;
            class Child extends Parent {}

            // Use defineProperties to handle getters/setters properly
            for (const key in props) {
                const descriptor = Object.getOwnPropertyDescriptor(props, key);
                if (descriptor) {
                    Object.defineProperty(Child.prototype, key, descriptor);
                }
            }
            Object.assign(Child, Parent);
            Child.__super__ = Parent.prototype;

            return Child;
        }
    }
    Widget.prototype.defaults = {};

    PhpDebugBar.Widget = Widget;

    // ------------------------------------------------------------------

    /**
     * Tab
     *
     * A tab is composed of a tab label which is always visible and
     * a tab panel which is visible only when the tab is active.
     *
     * The panel must contain a widget. A widget is an object which has
     * an element property containing something appendable to a HTMLElement object.
     *
     * Options:
     *  - title
     *  - badge
     *  - widget
     *  - data: forward data to widget data
     */
    class Tab extends Widget {
        get className() {
            return csscls('panel');
        }

        render() {
            this.active = false;
            this.tab = document.createElement('a');
            this.tab.classList.add(csscls('tab'));

            this.icon = document.createElement('i');
            this.tab.append(this.icon);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.icon.className = `phpdebugbar-icon phpdebugbar-icon-${icon}`;
                } else {
                    this.icon.className = '';
                }
            });

            const title = document.createElement('span');
            title.classList.add(csscls('text'));
            this.tab.append(title);
            this.bindAttr('title', title);

            this.badge = document.createElement('span');
            this.badge.classList.add(csscls('badge'));
            this.tab.append(this.badge);

            this.bindAttr('badge', function (value) {
                if (value !== null) {
                    this.badge.textContent = value;
                    this.badge.classList.add(csscls('visible'));
                } else {
                    this.badge.classList.remove(csscls('visible'));
                }
            });

            this.bindAttr('widget', function (widget) {
                this.el.innerHTML = '';
                this.el.append(widget.el);
            });

            this.widgetRendered = false;
            this.bindAttr('data', function (data) {
                if (this.has('widget')) {
                    this.tab.setAttribute('data-empty', Object.keys(data).length === 0 || data.count === 0);
                    if (!this.widgetRendered && this.active && data != null) {
                        this.renderWidgetData();
                    } else {
                        this.widgetRendered = false;
                    }
                }
            });
        }

        renderWidgetData() {
            const data = this.get('data');
            const widget = this.get('widget');
            if (data == null || !widget) {
                return;
            }

            widget.set('data', data);
            PhpDebugBar.utils.schedule(() => {
                PhpDebugBar.utils.sfDump(widget.el);
            });

            this.widgetRendered = true;
        }

        show() {
            const activeClass = csscls('active');
            this.tab.classList.add(activeClass);
            this.tab.hidden = false;
            this.el.classList.add(activeClass);
            this.el.hidden = false;
            this.active = true;

            if (!this.widgetRendered) {
                this.renderWidgetData();
            }
        }

        hide() {
            const activeClass = csscls('active');
            this.tab.classList.remove(activeClass);
            this.el.classList.remove(activeClass);
            this.el.hidden = true;
            this.active = false;
        }
    }

    // ------------------------------------------------------------------

    /**
     * Indicator
     *
     * An indicator is a text and an icon to display single value information
     * right inside the always visible part of the debug bar
     *
     * Options:
     *  - icon
     *  - title
     *  - tooltip
     *  - data: alias of title
     */
    class Indicator extends Widget {
        get tagName() {
            return 'span';
        }

        get className() {
            return csscls('indicator');
        }

        render() {
            this.icon = document.createElement('i');
            this.el.append(this.icon);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.icon.className = `phpdebugbar-icon phpdebugbar-icon-${icon}`;
                } else {
                    this.icon.className = '';
                }
            });

            this.bindAttr('link', function (link) {
                if (link) {
                    this.el.addEventListener('click', () => {
                        this.get('debugbar').showTab(link);
                    });
                    this.el.style.cursor = 'pointer';
                } else {
                    this.el.style.cursor = '';
                }
            });

            const textSpan = document.createElement('span');
            textSpan.classList.add(csscls('text'));
            this.el.append(textSpan);
            this.bindAttr(['title', 'data'], textSpan);

            this.tooltip = document.createElement('span');
            this.tooltip.classList.add(csscls('tooltip'), csscls('disabled'));
            this.el.append(this.tooltip);
            this.bindAttr('tooltip', function (tooltip) {
                if (tooltip) {
                    if (Array.isArray(tooltip) || typeof tooltip === 'object') {
                        const dl = document.createElement('dl');
                        for (const [key, value] of Object.entries(tooltip)) {
                            const dt = document.createElement('dt');
                            dt.textContent = key;
                            dl.append(dt);

                            const dd = document.createElement('dd');
                            dd.textContent = value;
                            dl.append(dd);
                        }
                        this.tooltip.innerHTML = '';
                        this.tooltip.append(dl);
                        this.tooltip.classList.remove(csscls('disabled'));
                    } else {
                        this.tooltip.textContent = tooltip;
                        this.tooltip.classList.remove(csscls('disabled'));
                    }
                } else {
                    this.tooltip.classList.add(csscls('disabled'));
                }
            });
        }
    }

    /**
     * Displays datasets in a table
     *
     */
    class Settings extends Widget {
        get tagName() {
            return 'form';
        }

        get className() {
            return csscls('settings');
        }

        initialize(options) {
            this.set(options);

            const debugbar = this.get('debugbar');
            this.settings = JSON.parse(localStorage.getItem('phpdebugbar-settings')) || {};

            for (const key in debugbar.options) {
                if (key in this.settings) {
                    debugbar.options[key] = this.settings[key];
                }

                // Theme requires dark/light mode detection
                if (key === 'theme') {
                    debugbar.setTheme(debugbar.options[key]);
                } else {
                    debugbar.el.setAttribute(`data-${key}`, debugbar.options[key]);
                }
            }
        }

        clearSettings() {
            const debugbar = this.get('debugbar');

            // Remove item from storage
            localStorage.removeItem('phpdebugbar-settings');
            localStorage.removeItem('phpdebugbar-ajaxhandler-autoshow');
            this.settings = {};

            // Reset options
            debugbar.options = { ...debugbar.defaultOptions };

            // Reset ajax handler
            if (debugbar.ajaxHandler) {
                const autoshow = debugbar.ajaxHandler.defaultAutoShow;
                debugbar.ajaxHandler.setAutoShow(autoshow);
                this.set('autoshow', autoshow);
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.autoshow.checked);
                }
            }

            this.initialize(debugbar.options);
        }

        storeSetting(key, value) {
            this.settings[key] = value;

            const debugbar = this.get('debugbar');
            debugbar.options[key] = value;
            if (key !== 'theme') {
                debugbar.el.setAttribute(`data-${key}`, value);
            }

            localStorage.setItem('phpdebugbar-settings', JSON.stringify(this.settings));
        }

        render() {
            this.el.innerHTML = '';

            const debugbar = this.get('debugbar');
            const self = this;

            const fields = {};

            // Set Theme
            const themeSelect = document.createElement('select');
            themeSelect.innerHTML = '<option value="auto">Auto (System preference)</option>'
                + '<option value="light">Light</option>'
                + '<option value="dark">Dark</option>';
            themeSelect.value = debugbar.options.theme;
            themeSelect.addEventListener('change', function () {
                self.storeSetting('theme', this.value);
                debugbar.setTheme(this.value);
            });
            fields.Theme = themeSelect;

            // Open Button Position
            const positionSelect = document.createElement('select');
            positionSelect.innerHTML = '<option value="bottomLeft">Bottom Left</option>'
                + '<option value="bottomRight">Bottom Right</option>'
                + '<option value="topLeft">Top Left</option>'
                + '<option value="topRight">Top Right</option>';
            positionSelect.value = debugbar.options.openBtnPosition;
            positionSelect.addEventListener('change', function () {
                self.storeSetting('openBtnPosition', this.value);
                if (this.value === 'topLeft' || this.value === 'topRight') {
                    self.storeSetting('toolbarPosition', 'top');
                } else {
                    self.storeSetting('toolbarPosition', 'bottom');
                }
                self.get('debugbar').recomputeBottomOffset();
            });
            fields['Toolbar Position'] = positionSelect;

            // Hide Empty Tabs
            this.hideEmptyTabs = document.createElement('input');
            this.hideEmptyTabs.type = 'checkbox';
            this.hideEmptyTabs.checked = debugbar.options.hideEmptyTabs;
            this.hideEmptyTabs.addEventListener('click', function () {
                self.storeSetting('hideEmptyTabs', this.checked);
                // Reset button size
                self.get('debugbar').respCSSSize = 0;
                self.get('debugbar').resize();
            });

            const hideEmptyTabsLabel = document.createElement('label');
            hideEmptyTabsLabel.append(this.hideEmptyTabs, 'Hide empty tabs until they have data');
            fields['Hide Empty Tabs'] = hideEmptyTabsLabel;

            // Autoshow
            this.autoshow = document.createElement('input');
            this.autoshow.type = 'checkbox';
            this.autoshow.checked = debugbar.ajaxHandler && debugbar.ajaxHandler.autoShow;
            this.autoshow.addEventListener('click', function () {
                if (debugbar.ajaxHandler) {
                    debugbar.ajaxHandler.setAutoShow(this.checked);
                }
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.checked);
                }
                // Update dataset switcher widget
                if (debugbar.datasetSwitcherWidget) {
                    debugbar.datasetSwitcherWidget.set('autoshow', this.checked);
                }
            });

            this.bindAttr('autoshow', function () {
                this.autoshow.checked = this.get('autoshow');
                const row = this.autoshow.closest(`.${csscls('form-row')}`);
                if (row) {
                    row.style.display = '';
                }
            });

            const autoshowLabel = document.createElement('label');
            autoshowLabel.append(this.autoshow, 'Automatically show new incoming Ajax requests');
            fields.Autoshow = autoshowLabel;

            // Reset button
            const resetButton = document.createElement('button');
            resetButton.textContent = 'Reset settings';
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                self.clearSettings();
                self.render();
            });
            fields['Reset to defaults'] = resetButton;

            for (const [key, value] of Object.entries(fields)) {
                const formRow = document.createElement('div');
                formRow.classList.add(csscls('form-row'));

                const formLabel = document.createElement('div');
                formLabel.classList.add(csscls('form-label'));
                formLabel.textContent = key;
                formRow.append(formLabel);

                const formInput = document.createElement('div');
                formInput.classList.add(csscls('form-input'));
                if (value instanceof HTMLElement) {
                    formInput.append(value);
                } else {
                    formInput.innerHTML = value;
                }
                formRow.append(formInput);

                self.el.append(formRow);
            }

            if (!debugbar.ajaxHandler) {
                this.autoshow.closest(`.${csscls('form-row')}`).style.display = 'none';
            }
        }
    }

    // ------------------------------------------------------------------

    /**
     * Dataset title formater
     *
     * Formats the title of a dataset for the select box
     */
    class DatasetTitleFormater {
        constructor(debugbar) {
            this.debugbar = debugbar;
        }

        /**
         * Formats the title of a dataset
         *
         * @param {string} id
         * @param {object} data
         * @param {string} suffix
         * @param {number} nb
         * @return {string}
         */
        format(id, data, suffix, nb) {
            suffix = suffix ? ` ${suffix}` : '';
            nb = nb || Object.keys(this.debugbar.datasets).length;

            if (data.__meta === undefined) {
                return `#${nb}${suffix}`;
            }

            const uri = data.__meta.uri.split('/');
            let filename = uri.pop();

            // URI ends in a trailing /, avoid returning an empty string
            if (!filename) {
                filename = `${uri.pop() || ''}/`; // add the trailing '/' back
            }

            // filename is a number, path could be like /action/{id}
            if (uri.length && !Number.isNaN(filename)) {
                filename = `${uri.pop()}/${filename}`;
            }

            // truncate the filename in the label, if it's too long
            const maxLength = 150;
            if (filename.length > maxLength) {
                filename = `${filename.substr(0, maxLength)}...`;
            }

            const label = `#${nb} ${filename}${suffix} (${data.__meta.datetime.split(' ')[1]})`;
            return label;
        }
    }

    PhpDebugBar.DatasetTitleFormater = DatasetTitleFormater;

    // ------------------------------------------------------------------

    /**
     * DebugBar
     *
     * Creates a bar that appends itself to the body of your page
     * and sticks to the bottom.
     *
     * The bar can be customized by adding tabs and indicators.
     * A data map is used to fill those controls with data provided
     * from datasets.
     */
    class DebugBar extends Widget {
        get className() {
            return `phpdebugbar ${csscls('minimized')}`;
        }

        initialize(options = {}) {
            this.options = Object.assign({
                bodyBottomInset: true,
                theme: 'auto',
                toolbarPosition: 'bottom',
                openBtnPosition: 'bottomLeft',
                hideEmptyTabs: false
            }, options);
            this.defaultOptions = { ...this.options };
            this.controls = {};
            this.dataMap = {};
            this.datasets = {};
            this.firstTabName = null;
            this.activePanelName = null;
            this.activeDatasetId = null;
            this.pendingDataSetId = null;
            this.datesetTitleFormater = new DatasetTitleFormater(this);
            const bodyStyles = window.getComputedStyle(document.body);
            this.bodyPaddingBottomHeight = Number.parseInt(bodyStyles.paddingBottom);
            this.bodyPaddingTopHeight = Number.parseInt(bodyStyles.paddingTop);

            try {
                this.isIframe = window.self !== window.top && window.top.PhpDebugBar && window.top.PhpDebugBar;
            } catch (_error) {
                this.isIframe = false;
            }
            this.registerResizeHandler();
            this.registerMediaListener();

            // Attach settings
            this.settingsControl = new PhpDebugBar.DebugBar.Tab({ icon: 'adjustments-horizontal', title: 'Settings', widget: new Settings({
                debugbar: this
            }) });
        }

        /**
         * Register resize event, for resize debugbar with reponsive css.
         *
         * @this {DebugBar}
         */
        registerResizeHandler() {
            if (this.resize.bind === undefined || this.isIframe) {
                return;
            }

            const f = this.resize.bind(this);
            this.respCSSSize = 0;
            window.addEventListener('resize', f);
            setTimeout(f, 20);
        }

        registerMediaListener() {
            const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQueryList.addEventListener('change', (event) => {
                if (this.options.theme === 'auto') {
                    this.setTheme('auto');
                }
            });
        }

        setTheme(theme) {
            this.options.theme = theme;

            if (theme === 'auto') {
                const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
                theme = mediaQueryList.matches ? 'dark' : 'light';
            }

            this.el.setAttribute('data-theme', theme);
            if (this.openHandler) {
                this.openHandler.el.setAttribute('data-theme', theme);
            }
            if (this.datasetSwitcherWidget && this.datasetSwitcherWidget.panel) {
                this.datasetSwitcherWidget.panel.setAttribute('data-theme', theme);
            }
        }

        /**
         * Resizes the debugbar to fit the current browser window
         */
        resize() {
            if (this.isIframe) {
                return;
            }

            let contentSize = this.respCSSSize;
            if (this.respCSSSize === 0) {
                const visibleChildren = Array.from(this.header.children).filter((el) => {
                    return el.offsetParent !== null;
                });
                for (const child of visibleChildren) {
                    const styles = window.getComputedStyle(child);
                    contentSize += child.offsetWidth
                        + Number.parseFloat(styles.marginLeft)
                        + Number.parseFloat(styles.marginRight);
                }
            }

            const currentSize = this.header.offsetWidth;
            const cssClass = csscls('mini-design');
            const bool = this.header.classList.contains(cssClass);

            if (currentSize <= contentSize && !bool) {
                this.respCSSSize = contentSize;
                this.header.classList.add(cssClass);
            } else if (contentSize < currentSize && bool) {
                this.respCSSSize = 0;
                this.header.classList.remove(cssClass);
            }

            // Reset height to ensure bar is still visible
            const currentHeight = this.body.clientHeight || Number.parseInt(localStorage.getItem('phpdebugbar-height'), 10) || 300;
            this.setHeight(currentHeight);
        }

        /**
         * Initialiazes the UI
         *
         * @this {DebugBar}
         */
        render() {
            if (this.isIframe) {
                this.el.hidden = true;
            }

            const self = this;
            document.body.append(this.el);

            this.dragCapture = document.createElement('div');
            this.dragCapture.classList.add(csscls('drag-capture'));
            this.el.append(this.dragCapture);

            this.resizeHandle = document.createElement('div');
            this.resizeHandle.classList.add(csscls('resize-handle'));
            this.resizeHandle.classList.add(csscls('resize-handle-top'));
            this.el.append(this.resizeHandle);

            this.header = document.createElement('div');
            this.header.classList.add(csscls('header'));
            this.el.append(this.header);

            this.headerBtn = document.createElement('a');
            this.headerBtn.classList.add(csscls('restore-btn'));
            this.header.append(this.headerBtn);
            this.headerBtn.addEventListener('click', () => {
                self.close();
            });

            this.headerLeft = document.createElement('div');
            this.headerLeft.classList.add(csscls('header-left'));
            this.header.append(this.headerLeft);

            this.headerRight = document.createElement('div');
            this.headerRight.classList.add(csscls('header-right'));
            this.header.append(this.headerRight);

            this.body = document.createElement('div');
            this.body.classList.add(csscls('body'));
            this.el.append(this.body);
            this.recomputeBottomOffset();

            this.resizeHandleBottom = document.createElement('div');
            this.resizeHandleBottom.classList.add(csscls('resize-handle'));
            this.resizeHandleBottom.classList.add(csscls('resize-handle-bottom'));
            this.el.append(this.resizeHandleBottom);

            // dragging of resize handle
            let pos_y, orig_h;
            const mousemove = (e) => {
                const h = orig_h + (pos_y - e.pageY);
                self.setHeight(h);
            };
            const mousemoveBottom = (e) => {
                const h = orig_h - (pos_y - e.pageY);
                self.setHeight(h);
            };
            const mouseup = () => {
                document.removeEventListener('mousemove', mousemove);
                document.removeEventListener('mousemove', mousemoveBottom);
                document.removeEventListener('mouseup', mouseup);
                self.dragCapture.style.display = 'none';
            };
            this.resizeHandle.addEventListener('mousedown', (e) => {
                orig_h = self.body.offsetHeight;
                pos_y = e.pageY;
                document.addEventListener('mousemove', mousemove);
                document.addEventListener('mouseup', mouseup);
                self.dragCapture.style.display = '';
                e.preventDefault();
            });
            this.resizeHandleBottom.addEventListener('mousedown', (e) => {
                orig_h = self.body.offsetHeight;
                pos_y = e.pageY;
                document.addEventListener('mousemove', mousemoveBottom);
                document.addEventListener('mouseup', mouseup);
                self.dragCapture.style.display = '';
                e.preventDefault();
            });

            // close button
            this.closebtn = document.createElement('a');
            this.closebtn.classList.add(csscls('close-btn'));
            this.headerRight.append(this.closebtn);
            this.closebtn.addEventListener('click', () => {
                self.close();
            });

            // minimize button
            this.minimizebtn = document.createElement('a');
            this.minimizebtn.classList.add(csscls('minimize-btn'));
            this.minimizebtn.hidden = !this.isMinimized();
            this.headerRight.append(this.minimizebtn);
            this.minimizebtn.addEventListener('click', () => {
                self.minimize();
            });

            // maximize button
            this.maximizebtn = document.createElement('a');
            this.maximizebtn.classList.add(csscls('maximize-btn'));
            this.maximizebtn.hidden = this.isMinimized();
            this.headerRight.append(this.maximizebtn);
            this.maximizebtn.addEventListener('click', () => {
                self.restore();
            });

            // restore button
            this.restorebtn = document.createElement('a');
            this.restorebtn.classList.add(csscls('restore-btn'));
            this.restorebtn.hidden = true;
            this.el.append(this.restorebtn);
            this.restorebtn.addEventListener('click', () => {
                self.restore();
            });

            // open button
            this.openbtn = document.createElement('a');
            this.openbtn.classList.add(csscls('open-btn'));
            this.openbtn.hidden = true;
            this.headerRight.append(this.openbtn);
            this.openbtn.addEventListener('click', () => {
                self.openHandler.show((id, dataset) => {
                    self.addDataSet(dataset, id, '(opened)');
                });
            });

            // select box for data sets (only if AJAX handler is not used)
            this.datasetsSelectSpan = document.createElement('span');
            this.datasetsSelectSpan.classList.add(csscls('datasets-switcher'));
            this.datasetsSelectSpan.setAttribute('name', 'datasets-switcher');
            this.datasetsSelect = document.createElement('select');
            this.datasetsSelect.hidden = true;

            this.datasetsSelectSpan.append(this.datasetsSelect);

            this.headerRight.append(this.datasetsSelectSpan);
            this.datasetsSelect.addEventListener('change', function () {
                self.showDataSet(this.value);
            });

            this.controls.__settings = this.settingsControl;
            this.settingsControl.tab.classList.add(csscls('tab-settings'));
            this.settingsControl.tab.setAttribute('data-collector', '__settings');
            this.settingsControl.el.setAttribute('data-collector', '__settings');
            this.settingsControl.el.hidden = true;

            this.maximizebtn.after(this.settingsControl.tab);
            this.settingsControl.tab.hidden = false;
            this.settingsControl.tab.addEventListener('click', () => {
                if (!this.isMinimized() && this.activePanelName === '__settings') {
                    this.minimize();
                } else {
                    this.showTab('__settings');
                    this.settingsControl.get('widget').render();
                }
            });
            this.body.append(this.settingsControl.el);
        }

        /**
         * Sets the height of the debugbar body section
         * Forces the height to lie within a reasonable range
         * Stores the height in local storage so it can be restored
         * Resets the document body bottom offset
         *
         * @this {DebugBar}
         */
        setHeight(height) {
            const min_h = 40;
            const max_h = window.innerHeight - this.header.offsetHeight - 10;
            height = Math.min(height, max_h);
            height = Math.max(height, min_h);
            this.body.style.height = `${height}px`;
            localStorage.setItem('phpdebugbar-height', height);
            this.recomputeBottomOffset();
        }

        /**
         * Restores the state of the DebugBar using localStorage
         * This is not called by default in the constructor and
         * needs to be called by subclasses in their init() method
         *
         * @this {DebugBar}
         */
        restoreState() {
            if (this.isIframe) {
                return;
            }
            // bar height
            const height = localStorage.getItem('phpdebugbar-height');
            this.setHeight(Number.parseInt(height) || this.body.offsetHeight);

            // bar visibility
            const open = localStorage.getItem('phpdebugbar-open');
            if (open && open === '0') {
                this.close();
            } else {
                const visible = localStorage.getItem('phpdebugbar-visible');
                if (visible && visible === '1') {
                    const tab = localStorage.getItem('phpdebugbar-tab');
                    if (this.isTab(tab)) {
                        this.showTab(tab);
                    } else {
                        this.showTab();
                    }
                } else if (visible === '0') {
                    this.minimize();
                }
            }
        }

        /**
         * Creates and adds a new tab
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {object} widget A widget object with an element property
         * @param {string} title The text in the tab, if not specified, name will be used
         * @return {Tab}
         */
        createTab(name, widget, title) {
            const tab = new Tab({
                title: title || (name.replace(/[_-]/g, ' ').charAt(0).toUpperCase() + name.slice(1)),
                widget
            });
            return this.addTab(name, tab);
        }

        /**
         * Adds a new tab
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {Tab} tab Tab object
         * @return {Tab}
         */
        addTab(name, tab) {
            if (this.isControl(name)) {
                throw new Error(`${name} already exists`);
            }

            const self = this;
            this.headerLeft.append(tab.tab);
            tab.tab.addEventListener('click', () => {
                if (!self.isMinimized() && self.activePanelName === name) {
                    self.minimize();
                } else {
                    self.restore();
                    self.showTab(name);
                }
            });
            tab.tab.setAttribute('data-empty', true);
            tab.tab.setAttribute('data-collector', name);
            tab.el.setAttribute('data-collector', name);
            this.body.append(tab.el);

            this.controls[name] = tab;
            if (this.firstTabName === null) {
                this.firstTabName = name;
            }
            return tab;
        }

        /**
         * Creates and adds an indicator
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {string} icon
         * @param {string | object} tooltip
         * @param {string} position "right" or "left", default is "right"
         * @return {Indicator}
         */
        createIndicator(name, icon, tooltip, position) {
            const indicator = new Indicator({
                icon,
                tooltip
            });
            return this.addIndicator(name, indicator, position);
        }

        /**
         * Adds an indicator
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {Indicator} indicator Indicator object
         * @return {Indicator}
         */
        addIndicator(name, indicator, position) {
            if (this.isControl(name)) {
                throw new Error(`${name} already exists`);
            }

            indicator.set('debugbar', this);

            if (position === 'left') {
                this.headerLeft.prepend(indicator.el);
            } else {
                this.headerRight.append(indicator.el);
            }

            this.controls[name] = indicator;
            return indicator;
        }

        /**
         * Returns a control
         *
         * @param {string} name
         * @return {object}
         */
        getControl(name) {
            if (this.isControl(name)) {
                return this.controls[name];
            }
        }

        /**
         * Checks if there's a control under the specified name
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isControl(name) {
            return this.controls[name] !== undefined;
        }

        /**
         * Checks if a tab with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isTab(name) {
            return this.isControl(name) && this.controls[name] instanceof Tab;
        }

        /**
         * Checks if an indicator with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isIndicator(name) {
            return this.isControl(name) && this.controls[name] instanceof Indicator;
        }

        /**
         * Removes all tabs and indicators from the debug bar and hides it
         *
         * @this {DebugBar}
         */
        reset() {
            this.minimize();
            for (const [name, control] of Object.entries(this.controls)) {
                if (this.isTab(name)) {
                    control.tab.remove();
                }
                control.el.remove();
            }
            this.controls = {};
        }

        /**
         * Open the debug bar and display the specified tab
         *
         * @this {DebugBar}
         * @param {string} name If not specified, display the first tab
         */
        showTab(name) {
            if (!name) {
                if (this.activePanelName) {
                    name = this.activePanelName;
                } else {
                    name = this.firstTabName;
                }
            }

            if (!this.isTab(name)) {
                throw new Error(`Unknown tab '${name}'`);
            }

            this.body.hidden = false;

            this.recomputeBottomOffset();

            for (const [controleName, control] of Object.entries(this.controls)) {
                if (control instanceof Tab) {
                    if (controleName === name) {
                        control.show();
                    } else {
                        control.hide();
                    }
                }
            }

            this.activePanelName = name;

            this.el.classList.remove(csscls('minimized'));
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-tab', name);

            this.maximize();
        }

        /**
         * Hide panels and minimize the debug bar
         *
         * @this {DebugBar}
         */
        minimize() {
            const activeClass = csscls('active');
            const headerActives = this.header.querySelectorAll(`:scope > div > .${activeClass}`);
            for (const el of headerActives) {
                el.classList.remove(activeClass);
            }
            this.body.hidden = true;
            this.minimizebtn.hidden = true;
            this.maximizebtn.hidden = false;

            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '0');
            this.el.classList.add(csscls('minimized'));
            this.resize();
        }

        /**
         * Show panels and maxime the debug bar
         *
         * @this {DebugBar}
         */
        maximize() {
            this.header.hidden = false;
            this.restorebtn.hidden = true;
            this.body.hidden = false;
            this.minimizebtn.hidden = false;
            this.maximizebtn.hidden = true;

            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-open', '1');
            this.el.classList.remove(csscls('minimized'));
            this.el.classList.remove(csscls('closed'));

            this.resize();
        }

        /**
         * Checks if the panel is minimized
         *
         * @return {boolean}
         */
        isMinimized() {
            return this.el.classList.contains(csscls('minimized'));
        }

        /**
         * Close the debug bar
         *
         * @this {DebugBar}
         */
        close() {
            this.header.hidden = true;
            this.body.hidden = true;
            this.restorebtn.hidden = false;
            localStorage.setItem('phpdebugbar-open', '0');
            this.el.classList.add(csscls('closed'));
            this.recomputeBottomOffset();
        }

        /**
         * Checks if the panel is closed
         *
         * @return {boolean}
         */
        isClosed() {
            return this.el.classList.contains(csscls('closed'));
        }

        /**
         * Restore the debug bar
         *
         * @this {DebugBar}
         */
        restore() {
            const tab = localStorage.getItem('phpdebugbar-tab');
            if (this.pendingDataSetId) {
                this.dataChangeHandler(this.datasets[this.pendingDataSetId]);
                this.pendingDataSetId = null;
            }
            if (this.isTab(tab)) {
                this.showTab(tab);
            } else {
                this.showTab();
            }
        }

        /**
         * Recomputes the margin-bottom css property of the body so
         * that the debug bar never hides any content
         */
        recomputeBottomOffset() {
            if (this.options.bodyBottomInset) {
                if (this.isClosed()) {
                    document.body.style.paddingBottom = this.bodyPaddingBottomHeight ? `${this.bodyPaddingBottomHeight}px` : '';
                    document.body.style.paddingTop = this.bodyPaddingTopHeight ? `${this.bodyPaddingTopHeight}px` : '';
                    return;
                }

                if (this.options.toolbarPosition === 'top') {
                    const offset = this.el.offsetHeight + (this.bodyPaddingTopHeight || 0);
                    document.body.style.paddingTop = `${offset}px`;
                    document.body.style.paddingBottom = this.bodyPaddingBottomHeight ? `${this.bodyPaddingBottomHeight}px` : '';
                } else {
                    const offset = this.el.offsetHeight + (this.bodyPaddingBottomHeight || 0);
                    document.body.style.paddingBottom = `${offset}px`;
                    document.body.style.paddingTop = this.bodyPaddingTopHeight ? `${this.bodyPaddingTopHeight}px` : '';
                }
            }
        }

        /**
         * Sets the data map used by dataChangeHandler to populate
         * indicators and widgets
         *
         * A data map is an object where properties are control names.
         * The value of each property should be an array where the first
         * item is the name of a property from the data object (nested properties
         * can be specified) and the second item the default value.
         *
         * Example:
         *     {"memory": ["memory.peak_usage_str", "0B"]}
         *
         * @this {DebugBar}
         * @param {object} map
         */
        setDataMap(map) {
            this.dataMap = map;
        }

        /**
         * Same as setDataMap() but appends to the existing map
         * rather than replacing it
         *
         * @this {DebugBar}
         * @param {object} map
         */
        addDataMap(map) {
            Object.assign(this.dataMap, map);
        }

        /**
         * Resets datasets and add one set of data
         *
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         *
         * @this {DebugBar}
         * @param {object} data
         * @return {string} Dataset's id
         */
        setData(data) {
            this.datasets = {};
            return this.addDataSet(data);
        }

        /**
         * Adds a dataset
         *
         * If more than one dataset are added, the dataset selector
         * will be displayed.
         *
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         *
         * @this {DebugBar}
         * @param {object} data
         * @param {string} id The name of this set, optional
         * @param {string} suffix
         * @param {Bool} show Whether to show the new dataset, optional (default: true)
         * @return {string} Dataset's id
         */
        addDataSet(data, id, suffix, show) {
            if (!data || !data.__meta) {
                return;
            }
            if (this.isIframe && window.top.PhpDebugBar && window.top.PhpDebugBar.instance) {
                window.top.PhpDebugBar.instance.addDataSet(data, id, `(iframe)${suffix || ''}`, show);
                return;
            }

            const nb = Object.keys(this.datasets).length + 1;
            id = id || nb;
            data.__meta.nb = nb;
            data.__meta.suffix = suffix;
            this.datasets[id] = data;

            const label = this.datesetTitleFormater.format(id, this.datasets[id], suffix, nb);

            // Update dataset switcher widget (if AJAX handler is enabled)
            if (this.datasetSwitcherWidget) {
                this.datasetSwitcherWidget.set('data', this.datasets);
            } else {
                // Use old dropdown (if AJAX handler is not enabled)
                const option = document.createElement('option');
                option.value = id;
                option.textContent = label;
                this.datasetsSelect.append(option);
                this.datasetsSelect.hidden = false;
            }

            if (show === undefined || show) {
                this.showDataSet(id);
            }

            this.resize();

            return id;
        }

        /**
         * Loads a dataset using the open handler
         *
         * @param {string} id
         * @param {Bool} show Whether to show the new dataset, optional (default: true)
         */
        loadDataSet(id, suffix, callback, show) {
            if (!this.openHandler) {
                throw new Error('loadDataSet() needs an open handler');
            }
            const self = this;
            this.openHandler.load(id, (data) => {
                self.addDataSet(data, id, suffix, show);
                self.resize();
                callback && callback(data);
            });
        }

        /**
         * Returns the data from a dataset
         *
         * @this {DebugBar}
         * @param {string} id
         * @return {object}
         */
        getDataSet(id) {
            return this.datasets[id];
        }

        /**
         * Switch the currently displayed dataset
         *
         * @this {DebugBar}
         * @param {string} id
         */
        showDataSet(id) {
            this.activeDatasetId = id;
            if (this.isClosed()) {
                this.pendingDataSetId = id;
            } else {
                this.dataChangeHandler(this.datasets[id]);
                this.pendingDataSetId = null;
            }

            // Update dataset switcher widget to reflect current dataset
            if (this.datasetSwitcherWidget) {
                this.datasetSwitcherWidget.set('activeId', id);
            } else {
                // Update old dropdown
                this.datasetsSelect.value = id;
            }
        }

        /**
         * Called when the current dataset is modified.
         *
         * @this {DebugBar}
         * @param {object} data
         */
        dataChangeHandler(data) {
            for (const [key, def] of Object.entries(this.dataMap)) {
                const d = getDictValue(data, def[0], def[1]);
                if (key.includes(':')) {
                    const parts = key.split(':');
                    this.getControl(parts[0]).set(parts[1], d);
                } else {
                    this.getControl(key).set('data', d);
                }
            }

            if (!this.isMinimized()) {
                this.showTab();
            }

            this.resize();
        }

        /**
         * Sets the handler to open past dataset
         *
         * @this {DebugBar}
         * @param {object} handler
         */
        setOpenHandler(handler) {
            this.openHandler = handler;
            this.openHandler.el.setAttribute('data-theme', this.el.getAttribute('data-theme'));
            this.openbtn.hidden = handler == null;
        }

        /**
         * Returns the handler to open past dataset
         *
         * @this {DebugBar}
         * @return {object}
         */
        getOpenHandler() {
            return this.openHandler;
        }

        enableAjaxHandlerTab() {
            // Hide the old dropdown
            if (this.datasetsSelectSpan) {
                this.datasetsSelectSpan.hidden = true;
            }

            // Create dataset switcher widget in header (after open button)
            this.datasetSwitcherWidget = new PhpDebugBar.Widgets.DatasetWidget({
                debugbar: this
            });
            this.openbtn.after(this.datasetSwitcherWidget.el);
        }
    }

    PhpDebugBar.DebugBar = DebugBar;
    DebugBar.Tab = Tab;
    DebugBar.Indicator = Indicator;

    // ------------------------------------------------------------------

    /**
     * AjaxHandler
     *
     * Extract data from headers of an XMLHttpRequest and adds a new dataset
     *
     * @param {Bool} autoShow Whether to immediately show new datasets, optional (default: true)
     */
    class AjaxHandler {
        constructor(debugbar, headerName, autoShow) {
            this.debugbar = debugbar;
            this.headerName = headerName || 'phpdebugbar';
            this.autoShow = autoShow === undefined ? true : autoShow;
            this.defaultAutoShow = this.autoShow;
            if (localStorage.getItem('phpdebugbar-ajaxhandler-autoshow') !== null) {
                this.autoShow = localStorage.getItem('phpdebugbar-ajaxhandler-autoshow') === '1';
            }
            if (debugbar.controls.__settings) {
                debugbar.controls.__settings.get('widget').set('autoshow', this.autoShow);
            }
        }

        /**
         * Handles a Fetch API Response or an XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        handle(response) {
            const stack = this.getHeader(response, `${this.headerName}-stack`);
            if (stack) {
                const stackIds = JSON.parse(stack);
                stackIds.forEach((id) => {
                    this.debugbar.loadDataSet(id, ' (stacked)', null, false);
                });
            }

            if (this.loadFromId(response)) {
                return true;
            }

            if (this.loadFromData(response)) {
                return true;
            }

            return false;
        }

        /**
         * Retrieves a response header from either a Fetch Response or XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response - The response object from either fetch() or XHR
         * @param {string} header - The name of the header to retrieve
         * @returns {string|null} The header value, or null if not found
         */
        getHeader(response, header) {
            if (response instanceof Response) {
                return response.headers.get(header);
            } else if (response instanceof XMLHttpRequest) {
                return response.getResponseHeader(header);
            }
            return null;
        }

        setAutoShow(autoshow) {
            this.autoShow = autoshow;
            localStorage.setItem('phpdebugbar-ajaxhandler-autoshow', autoshow ? '1' : '0');
        }

        /**
         * Checks if the HEADER-id exists and loads the dataset using the open handler
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        loadFromId(response) {
            const id = this.extractIdFromHeaders(response);
            if (id && this.debugbar.openHandler) {
                this.debugbar.loadDataSet(id, '(ajax)', undefined, this.autoShow);
                return true;
            }
            return false;
        }

        /**
         * Extracts the id from the HEADER-id
         *
         * @param {Response|XMLHttpRequest} response
         * @return {string}
         */
        extractIdFromHeaders(response) {
            return this.getHeader(response, `${this.headerName}-id`);
        }

        /**
         * Checks if the HEADER exists and loads the dataset
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        loadFromData(response) {
            const raw = this.extractDataFromHeaders(response);
            if (!raw) {
                return false;
            }

            const data = this.parseHeaders(raw);
            if (data.error) {
                throw new Error(`Error loading debugbar data: ${data.error}`);
            } else if (data.data) {
                this.debugbar.addDataSet(data.data, data.id, '(ajax)', this.autoShow);
            }
            return true;
        }

        /**
         * Extract the data as a string from headers of an XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response
         * @return {string}
         */
        extractDataFromHeaders(response) {
            let data = this.getHeader(response, this.headerName);
            if (!data) {
                return;
            }
            for (let i = 1; ; i++) {
                const header = this.getHeader(response, `${this.headerName}-${i}`);
                if (!header) {
                    break;
                }
                data += header;
            }
            return decodeURIComponent(data);
        }

        /**
         * Parses the string data into an object
         *
         * @param {string} data
         * @return {object}
         */
        parseHeaders(data) {
            return JSON.parse(data);
        }

        /**
         * Attaches an event listener to fetch
         */
        bindToFetch() {
            const self = this;

            const proxied = window.fetch.__debugbar_original || window.fetch;
            const original = proxied.bind(window);

            function wrappedFetch(...args) {
                const p = original(...args);
                p?.then?.(r => self.handle(r)).catch(() => {});
                return p;
            }

            wrappedFetch.__debugbar_wrapped = true;
            wrappedFetch.__debugbar_original = proxied;

            window.fetch = wrappedFetch;
        }

        /**
         * Attaches an event listener to XMLHttpRequest
         */
        bindToXHR() {
            const self = this;
            const proto = XMLHttpRequest.prototype;

            const proxied = (proto.open || {}).__debugbar_original || proto.open;
            if (typeof proxied !== 'function') {
                return;
            }

            function wrappedOpen(method, url, async, user, pass) {
                if (!this.__debugbar_listener_attached) {
                    this.__debugbar_listener_attached = true;

                    this.addEventListener('readystatechange', () => {
                        if (this.readyState === 4) {
                            self.handle(this);
                        }
                    });
                }

                return proxied.call(this, method, url, async, user, pass);
            }

            wrappedOpen.__debugbar_wrapped = true;
            wrappedOpen.__debugbar_original = proxied;

            proto.open = wrappedOpen;
        }
    }

    PhpDebugBar.AjaxHandler = AjaxHandler;
})();
