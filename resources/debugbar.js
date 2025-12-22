if (typeof PhpDebugBar === 'undefined') {
    // namespace
    window.PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}


// Auto-inject SVG sprite
(function() {
    if (typeof document !== 'undefined' && !document.getElementById('phpdebugbar-icons')) {
        const div = document.createElement('div');
        div.id = 'phpdebugbar-icons';
        div.innerHTML = "<svg xmlns=\"http://www.w3.org/2000/svg\" style=\"display: none;\">\n  <!-- Generated from Tabler Icons - do not edit manually -->\n\n  <symbol id=\"debugbar-icon-adjustments\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0\" /> <path d=\"M6 4v4\" /> <path d=\"M6 12v8\" /> <path d=\"M10 16a2 2 0 1 0 4 0a2 2 0 0 0 -4 0\" /> <path d=\"M12 4v10\" /> <path d=\"M12 18v2\" /> <path d=\"M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0\" /> <path d=\"M18 4v1\" /> <path d=\"M18 9v11\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-adjustments-horizontal\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M14 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0\" /> <path d=\"M4 6l8 0\" /> <path d=\"M16 6l4 0\" /> <path d=\"M8 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0\" /> <path d=\"M4 12l2 0\" /> <path d=\"M10 12l10 0\" /> <path d=\"M17 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0\" /> <path d=\"M4 18l11 0\" /> <path d=\"M19 18l1 0\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-arrow-right\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M5 12l14 0\" /> <path d=\"M13 18l6 -6\" /> <path d=\"M13 6l6 6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-arrows-left-right\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M21 17l-18 0\" /> <path d=\"M6 10l-3 -3l3 -3\" /> <path d=\"M3 7l18 0\" /> <path d=\"M18 20l3 -3l-3 -3\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-bolt\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-bookmark\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M18 7v14l-6 -4l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4z\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-box\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5\" /> <path d=\"M12 12l8 -4.5\" /> <path d=\"M12 12l0 9\" /> <path d=\"M12 12l-8 -4.5\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-briefcase\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z\" /> <path d=\"M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2\" /> <path d=\"M12 12l0 .01\" /> <path d=\"M3 13a20 20 0 0 0 18 0\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-bug\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M9 9v-1a3 3 0 0 1 6 0v1\" /> <path d=\"M8 9h8a6 6 0 0 1 1 3v3a5 5 0 0 1 -10 0v-3a6 6 0 0 1 1 -3\" /> <path d=\"M3 13l4 0\" /> <path d=\"M17 13l4 0\" /> <path d=\"M12 20l0 -6\" /> <path d=\"M4 19l3.35 -2\" /> <path d=\"M20 19l-3.35 -2\" /> <path d=\"M4 7l3.75 2.4\" /> <path d=\"M20 7l-3.75 2.4\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-calendar\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z\" /> <path d=\"M16 3v4\" /> <path d=\"M8 3v4\" /> <path d=\"M4 11h16\" /> <path d=\"M11 15h1\" /> <path d=\"M12 15v3\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-chart-infographic\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M7 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0\" /> <path d=\"M7 3v4h4\" /> <path d=\"M9 17l0 4\" /> <path d=\"M17 14l0 7\" /> <path d=\"M13 13l0 8\" /> <path d=\"M21 12l0 9\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-clock\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0\" /> <path d=\"M12 7v5l3 3\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-code\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M7 8l-4 4l4 4\" /> <path d=\"M17 8l4 4l-4 4\" /> <path d=\"M14 4l-4 16\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-database\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0\" /> <path d=\"M4 6v6a8 3 0 0 0 16 0v-6\" /> <path d=\"M4 12v6a8 3 0 0 0 16 0v-6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-flag\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M5 5a5 5 0 0 1 7 0a5 5 0 0 0 7 0v9a5 5 0 0 1 -7 0a5 5 0 0 0 -7 0v-9z\" /> <path d=\"M5 21v-7\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-history\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 8l0 4l2 2\" /> <path d=\"M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-inbox\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z\" /> <path d=\"M4 13h3l3 3h4l3 -3h3\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-leaf\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M5 21c.5 -4.5 2.5 -8 7 -10\" /> <path d=\"M9 18c6.218 0 10.5 -3.288 11 -12v-2h-4.014c-9 0 -11.986 4 -12 9c0 1 0 3 2 5h3z\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-list\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M9 6l11 0\" /> <path d=\"M9 12l11 0\" /> <path d=\"M9 18l11 0\" /> <path d=\"M5 6l0 .01\" /> <path d=\"M5 12l0 .01\" /> <path d=\"M5 18l0 .01\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-logs\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M4 12h.01\" /> <path d=\"M4 6h.01\" /> <path d=\"M4 18h.01\" /> <path d=\"M8 18h2\" /> <path d=\"M8 12h2\" /> <path d=\"M8 6h2\" /> <path d=\"M14 6h6\" /> <path d=\"M14 12h6\" /> <path d=\"M14 18h6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-search\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0\" /> <path d=\"M21 21l-6 -6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-server-cog\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z\" /> <path d=\"M12 20h-6a3 3 0 0 1 -3 -3v-2a3 3 0 0 1 3 -3h10.5\" /> <path d=\"M18 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0\" /> <path d=\"M18 14.5v1.5\" /> <path d=\"M18 20v1.5\" /> <path d=\"M21.032 16.25l-1.299 .75\" /> <path d=\"M16.27 19l-1.3 .75\" /> <path d=\"M14.97 16.25l1.3 .75\" /> <path d=\"M19.733 19l1.3 .75\" /> <path d=\"M7 8v.01\" /> <path d=\"M7 16v.01\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-tags\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M3 8v4.172a2 2 0 0 0 .586 1.414l5.71 5.71a2.41 2.41 0 0 0 3.408 0l3.592 -3.592a2.41 2.41 0 0 0 0 -3.408l-5.71 -5.71a2 2 0 0 0 -1.414 -.586h-4.172a2 2 0 0 0 -2 2z\" /> <path d=\"M18 19l1.592 -1.592a4.82 4.82 0 0 0 0 -6.816l-4.592 -4.592\" /> <path d=\"M7 10h-.01\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-x\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M18 6l-12 12\" /> <path d=\"M6 6l12 12\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-chevron-down\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M6 9l6 6l6 -6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-chevron-up\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M6 15l6 -6l6 6\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-folder-open\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M5 19l2.757 -7.351a1 1 0 0 1 .936 -.649h12.307a1 1 0 0 1 .986 1.164l-.996 5.211a2 2 0 0 1 -1.964 1.625h-14.026a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v2\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-brand-php\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 12m-10 0a10 9 0 1 0 20 0a10 9 0 1 0 -20 0\" /> <path d=\"M5.5 15l.395 -1.974l.605 -3.026h1.32a1 1 0 0 1 .986 1.164l-.167 1a1 1 0 0 1 -.986 .836h-1.653\" /> <path d=\"M15.5 15l.395 -1.974l.605 -3.026h1.32a1 1 0 0 1 .986 1.164l-.167 1a1 1 0 0 1 -.986 .836h-1.653\" /> <path d=\"M12 7.5l-1 5.5\" /> <path d=\"M11.6 10h2.4l-.5 3\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-cpu\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M5 5m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z\" /> <path d=\"M9 9h6v6h-6z\" /> <path d=\"M3 10h2\" /> <path d=\"M3 14h2\" /> <path d=\"M10 3v2\" /> <path d=\"M14 3v2\" /> <path d=\"M21 10h-2\" /> <path d=\"M21 14h-2\" /> <path d=\"M14 21v-2\" /> <path d=\"M10 21v-2\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-table\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z\" /> <path d=\"M3 10h18\" /> <path d=\"M10 3v18\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-link\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M9 15l6 -6\" /> <path d=\"M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464\" /> <path d=\"M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-copy\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z\" /> <path d=\"M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-circle-check\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0\" /> <path d=\"M9 12l2 2l4 -4\" />\n  </symbol>\n\n  <symbol id=\"debugbar-icon-external-link\" viewBox=\"0 0 24 24\">\n    <path stroke=\"none\" d=\"M0 0h24v24H0z\" fill=\"none\"/> <path d=\"M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6\" /> <path d=\"M11 13l9 -9\" /> <path d=\"M15 4h5v5\" />\n  </symbol>\n\n</svg>\n";
        const target = document.body || document.documentElement;
        if (target.firstChild) {
            target.insertBefore(div.firstChild, target.firstChild);
        } else {
            target.appendChild(div.firstChild);
        }
    }
})();

(function ($) {
    if (typeof PhpDebugBar.utils === 'undefined') {
        PhpDebugBar.utils = {};
    }

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

    PhpDebugBar.utils.createIcon = function (id, attrs = {}) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttribute('href', `#debugbar-icon-${id}`);
        svg.appendChild(use);
        svg.setAttribute('class', 'phpdebugbar-icon');
        return $(svg).attr(attrs);
    }
    const createIcon = PhpDebugBar.utils.createIcon;

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

    // ------------------------------------------------------------------

    /**
     * Base class for all elements with a visual component
     */
    class Widget {
        constructor(options = {}) {
            this._attributes = { ...this.defaults };
            this._boundAttributes = {};
            this.$el = $(`<${this.tagName} />`);
            if (this.className) {
                this.$el.addClass(this.className);
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
            if (typeof attr !== 'string') {
                for (const k in attr) {
                    this.set(k, attr[k]);
                }
                return;
            }

            this._attributes[attr] = value;
            if (this._boundAttributes[attr]) {
                for (const callback of this._boundAttributes[attr]) {
                    callback.call(this, value);
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
         * If cb is a jQuery element, text() will be used to fill the element
         *
         * @param {string | Array} attr
         * @param {Function} cb
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
            if (typeof cb === 'object') {
                const el = cb;
                cb = value => el.text(value || '');
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

            Object.assign(Child.prototype, props);
            Object.assign(Child, Parent);
            Child.__super__ = Parent.prototype;

            return Child;
        }
    }

    // Set default prototype properties
    Widget.prototype.tagName = 'div';
    Widget.prototype.className = null;
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
     * an element property containing something appendable to a jQuery object.
     *
     * Options:
     *  - title
     *  - badge
     *  - widget
     *  - data: forward data to widget data
     */
    const Tab = Widget.extend({

        className: csscls('panel'),

        render() {
            this.$tab = $('<a />').addClass(csscls('tab'));
            this.$icon = $('<i />').addClass(csscls('icon')).appendTo(this.$tab);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.$icon.html(createIcon(icon));
                } else {
                    this.$icon.html('');
                }
            });

            this.bindAttr('title', $('<span />').addClass(csscls('text')).appendTo(this.$tab));

            this.$badge = $('<span />').addClass(csscls('badge')).appendTo(this.$tab);
            this.bindAttr('badge', function (value) {
                if (value !== null) {
                    this.$badge.text(value);
                    this.$badge.addClass(csscls('visible'));
                } else {
                    this.$badge.removeClass(csscls('visible'));
                }
            });

            this.bindAttr('widget', function (widget) {
                this.$el.empty().append(widget.$el);
            });

            this.bindAttr('data', function (data) {
                if (this.has('widget')) {
                    this.get('widget').set('data', data);
                    this.$tab.attr('data-empty', $.isEmptyObject(data) || data.count === 0);
                }
            });
        }

    });

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
    const Indicator = Widget.extend({

        tagName: 'span',

        className: csscls('indicator'),

        render() {
            this.$icon = $('<i />').appendTo(this.$el);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.$icon.html(createIcon(icon));
                } else {
                    this.$icon.html('');
                }
            });

            this.bindAttr('link', function (link) {
                if (link) {
                    this.$el.on('click', () => {
                        this.get('debugbar').showTab(link);
                    }).css('cursor', 'pointer');
                } else {
                    this.$el.off('click', false).css('cursor', '');
                }
            });

            this.bindAttr(['title', 'data'], $('<span />').addClass(csscls('text')).appendTo(this.$el));

            this.$tooltip = $('<span />').addClass(csscls('tooltip disabled')).appendTo(this.$el);
            this.bindAttr('tooltip', function (tooltip) {
                if (tooltip) {
                    const dl = $('<dl />');
                    if (Array.isArray(tooltip) || typeof tooltip === 'object') {
                        $.each(tooltip, (key, value) => {
                            $('<dt />').text(key).appendTo(dl);
                            $('<dd />').text(value).appendTo(dl);
                        });
                        this.$tooltip.html(dl).removeClass(csscls('disabled'));
                    } else {
                        this.$tooltip.text(tooltip).removeClass(csscls('disabled'));
                    }
                } else {
                    this.$tooltip.addClass(csscls('disabled'));
                }
            });
        }

    });

    /**
     * Displays datasets in a table
     *
     */
    const Settings = Widget.extend({

        tagName: 'form',

        className: csscls('settings'),

        settings: {},

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
                    debugbar.$el.attr(`data-${key}`, debugbar.options[key]);
                }
            }
        },

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
                    debugbar.controls.__datasets.get('widget').set('autoshow', $(this).is(':checked'));
                }
            }

            this.initialize(debugbar.options);
        },

        storeSetting(key, value) {
            this.settings[key] = value;

            const debugbar = this.get('debugbar');
            debugbar.options[key] = value;
            if (key !== 'theme') {
                debugbar.$el.attr(`data-${key}`, value);
            }

            localStorage.setItem('phpdebugbar-settings', JSON.stringify(this.settings));
        },

        render() {
            this.$el.empty();

            const debugbar = this.get('debugbar');
            const self = this;

            const fields = {};

            // Set Theme
            fields.Theme = $('<select>'
                + '<option value="auto">Auto (System preference)</option>'
                + '<option value="light">Light</option>'
                + '<option value="dark">Dark</option>'
                + '</select>')
                .val(debugbar.options.theme)
                .on('change', function () {
                    self.storeSetting('theme', $(this).val());
                    debugbar.setTheme($(this).val());
                });

            fields['Open Button Position'] = $('<select>'
                + '<option value="bottomLeft">Bottom Left</option>'
                + '<option value="bottomRight">Bottom Right</option>'
                + '<option value="topLeft">Top Left</option>'
                + '<option value="topRight">Top Right</option>'
                + '</select>')
                .val(debugbar.options.openBtnPosition)
                .on('change', function () {
                    self.storeSetting('openBtnPosition', $(this).val());
                });

            this.$hideEmptyTabs = $('<input type=checkbox>')
                .prop('checked', debugbar.options.hideEmptyTabs)
                .on('click', function () {
                    self.storeSetting('hideEmptyTabs', $(this).is(':checked'));
                });

            fields['Hide Empty Tabs'] = $('<label/>').append(this.$hideEmptyTabs, 'Hide empty tabs until they have data');

            this.$autoshow = $('<input type=checkbox>')
                .prop('checked', debugbar.ajaxHandler && debugbar.ajaxHandler.autoShow)
                .on('click', function () {
                    if (debugbar.ajaxHandler) {
                        debugbar.ajaxHandler.setAutoShow($(this).is(':checked'));
                    }
                    if (debugbar.controls.__datasets) {
                        debugbar.controls.__datasets.get('widget').set('autoshow', $(this).is(':checked'));
                    }
                });

            this.bindAttr('autoshow', function () {
                this.$autoshow.prop('checked', this.get('autoshow')).closest('.form-row').show();
            });
            fields.Autoshow = $('<label/>').append(this.$autoshow, 'Automatically show new incoming Ajax requests');

            fields['Reset to defaults'] = $('<button>Reset settings</button>').on('click', (e) => {
                e.preventDefault();
                self.clearSettings();
                self.render();
            });

            for (const [key, value] of Object.entries(fields)) {
                $('<div />').addClass(csscls('form-row')).append(
                    $('<div />').addClass(csscls('form-label')).text(key),
                    $('<div />').addClass(csscls('form-input')).html(value)
                ).appendTo(self.$el);
            }

            if (!this.ajaxHandler) {
                this.$autoshow.closest('.form-row').hide();
            }
        }

    });

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
            if (uri.length && !isNaN(filename)) {
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
    const DebugBar = PhpDebugBar.DebugBar = Widget.extend({

        className: `phpdebugbar ${csscls('minimized')}`,

        options: {
            bodyMarginBottom: true,
            theme: 'auto',
            openBtnPosition: 'bottomLeft',
            hideEmptyTabs: false
        },

        initialize(options = {}) {
            this.options = Object.assign({}, this.options, options);
            this.defaultOptions = { ...this.options };
            this.controls = {};
            this.dataMap = {};
            this.datasets = {};
            this.firstTabName = null;
            this.activePanelName = null;
            this.activeDatasetId = null;
            this.datesetTitleFormater = new DatasetTitleFormater(this);
            this.bodyMarginBottomHeight = Number.parseInt($('body').css('margin-bottom'));
            try {
                this.isIframe = window.self !== window.top && window.top.phpdebugbar;
            } catch (_error) {
                this.isIframe = false;
            }
            this.registerResizeHandler();
            this.registerMediaListener();

            // Attach settings
            this.settings = new PhpDebugBar.DebugBar.Tab({ icon: 'adjustments-horizontal', title: 'Settings', widget: new Settings({
                debugbar: this
            }) });
        },

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
            $(window).resize(f);
            setTimeout(f, 20);
        },

        registerMediaListener() {
            const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQueryList.addEventListener('change', (event) => {
                if (this.options.theme === 'auto') {
                    this.setTheme('auto');
                }
            });
        },

        setTheme(theme) {
            this.options.theme = theme;

            if (theme === 'auto') {
                const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
                theme = mediaQueryList.matches ? 'dark' : 'light';
            }

            this.$el.attr('data-theme', theme);
            if (this.openHandler) {
                this.openHandler.$el.attr('data-theme', theme);
            }
        },

        /**
         * Resizes the debugbar to fit the current browser window
         */
        resize() {
            if (this.isIframe) {
                return;
            }

            let contentSize = this.respCSSSize;
            if (this.respCSSSize === 0) {
                this.$header.find('> *:visible').each(function () {
                    contentSize += $(this).outerWidth(true);
                });
            }

            const currentSize = this.$header.width();
            const cssClass = csscls('mini-design');
            const bool = this.$header.hasClass(cssClass);

            if (currentSize <= contentSize && !bool) {
                this.respCSSSize = contentSize;
                this.$header.addClass(cssClass);
            } else if (contentSize < currentSize && bool) {
                this.respCSSSize = 0;
                this.$header.removeClass(cssClass);
            }

            // Reset height to ensure bar is still visible
            this.setHeight(this.$body.height());
        },

        /**
         * Initialiazes the UI
         *
         * @this {DebugBar}
         */
        render() {
            if (this.isIframe) {
                this.$el.hide();
            }

            const self = this;
            this.$el.appendTo('body');
            this.$dragCapture = $('<div />').addClass(csscls('drag-capture')).appendTo(this.$el);
            this.$resizehdle = $('<div />').addClass(csscls('resize-handle')).appendTo(this.$el);
            this.$header = $('<div />').addClass(csscls('header')).appendTo(this.$el);
            this.$headerBtn = $('<a />').addClass(csscls('restore-btn')).append(createIcon('brand-php')).appendTo(this.$header);
            this.$headerBtn.click(() => {
                self.close();
            });
            this.$headerLeft = $('<div />').addClass(csscls('header-left')).appendTo(this.$header);
            this.$headerRight = $('<div />').addClass(csscls('header-right')).appendTo(this.$header);
            const $body = this.$body = $('<div />').addClass(csscls('body')).appendTo(this.$el);
            this.recomputeBottomOffset();

            // dragging of resize handle
            let pos_y, orig_h;
            const mousemove = (e) => {
                const h = orig_h + (pos_y - e.pageY);
                self.setHeight(h);
            };
            const mouseup = () => {
                $body.parents().off('mousemove', mousemove).off('mouseup', mouseup);
                self.$dragCapture.hide();
            };
            this.$resizehdle.on('mousedown', (e) => {
                orig_h = $body.height(), pos_y = e.pageY;
                $body.parents().on('mousemove', mousemove).on('mouseup', mouseup);
                self.$dragCapture.show();
                e.preventDefault();
            });

            // close button
            this.$closebtn = $('<a />').addClass(csscls('close-btn')).append(createIcon('x')).appendTo(this.$headerRight);
            this.$closebtn.click(() => {
                self.close();
            });

            // minimize button
            this.$minimizebtn = $('<a />').addClass(csscls('minimize-btn')).append(createIcon('chevron-down')).appendTo(this.$headerRight);
            this.$minimizebtn.click(() => {
                self.minimize();
            });

            // maximize button
            this.$maximizebtn = $('<a />').addClass(csscls('maximize-btn')).append(createIcon('chevron-up')).appendTo(this.$headerRight);
            this.$maximizebtn.click(() => {
                self.restore();
            });

            // restore button
            this.$restorebtn = $('<a />').append(createIcon('brand-php')).addClass(csscls('restore-btn')).hide().appendTo(this.$el);
            this.$restorebtn.click(() => {
                self.restore();
            });

            // open button
            this.$openbtn = $('<a />').addClass(csscls('open-btn')).append(createIcon('folder-open')).appendTo(this.$headerRight).hide();
            this.$openbtn.click(() => {
                self.openHandler.show((id, dataset) => {
                    self.addDataSet(dataset, id, '(opened)');
                    self.showTab();
                });
            });

            // select box for data sets
            this.$datasets = $('<select />').addClass(csscls('datasets-switcher')).attr('name', 'datasets-switcher').appendTo(this.$headerRight);
            this.$datasets.change(function () {
                self.showDataSet(this.value);
            });

            this.controls.__settings = this.settings;
            this.settings.$tab.addClass(csscls('tab-settings'));
            this.settings.$tab.attr('data-collector', '__settings');
            this.settings.$el.attr('data-collector', '__settings');
            this.settings.$tab.insertAfter(this.$minimizebtn).show();
            this.settings.$tab.click(() => {
                if (!this.isMinimized() && this.activePanelName === '__settings') {
                    this.minimize();
                } else {
                    this.showTab('__settings');
                    this.settings.get('widget').render();
                }
            });
            this.settings.$el.appendTo(this.$body);
        },

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
            const max_h = window.innerHeight - this.$header.height() - 10;
            height = Math.min(height, max_h);
            height = Math.max(height, min_h);
            this.$body.css('height', height);
            localStorage.setItem('phpdebugbar-height', height);
            this.recomputeBottomOffset();
        },

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
            this.setHeight(height || this.$body.height());

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
                }
            }
        },

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
        },

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
            tab.$tab.appendTo(this.$headerLeft).click(() => {
                if (!self.isMinimized() && self.activePanelName === name) {
                    self.minimize();
                } else {
                    self.showTab(name);
                }
            });
            tab.$tab.attr('data-empty', true);
            tab.$tab.attr('data-collector', name);
            tab.$el.attr('data-collector', name);
            tab.$el.appendTo(this.$body);

            this.controls[name] = tab;
            if (this.firstTabName === null) {
                this.firstTabName = name;
            }
            return tab;
        },

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
        },

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
                indicator.$el.insertBefore(this.$headerLeft.children().first());
            } else {
                indicator.$el.appendTo(this.$headerRight);
            }

            this.controls[name] = indicator;
            return indicator;
        },

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
        },

        /**
         * Checks if there's a control under the specified name
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isControl(name) {
            return this.controls[name] !== undefined;
        },

        /**
         * Checks if a tab with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isTab(name) {
            return this.isControl(name) && this.controls[name] instanceof Tab;
        },

        /**
         * Checks if an indicator with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isIndicator(name) {
            return this.isControl(name) && this.controls[name] instanceof Indicator;
        },

        /**
         * Removes all tabs and indicators from the debug bar and hides it
         *
         * @this {DebugBar}
         */
        reset() {
            this.minimize();
            for (const [name, control] of Object.entries(this.controls)) {
                if (this.isTab(name)) {
                    control.$tab.remove();
                }
                control.$el.remove();
            }
            this.controls = {};
        },

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

            this.$resizehdle.show();
            this.$body.show();
            this.recomputeBottomOffset();

            $(this.$header).find(`> div > .${csscls('active')}`).removeClass(csscls('active'));
            $(this.$body).find(`> .${csscls('active')}`).removeClass(csscls('active'));

            this.controls[name].$tab.addClass(csscls('active'));
            this.controls[name].$el.addClass(csscls('active'));
            this.activePanelName = name;

            this.$el.removeClass(csscls('minimized'));
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-tab', name);

            this.resize();
        },

        /**
         * Hide panels and minimize the debug bar
         *
         * @this {DebugBar}
         */
        minimize() {
            this.$header.find(`> div > .${csscls('active')}`).removeClass(csscls('active'));
            this.$body.hide();
            this.$resizehdle.hide();
            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '0');
            this.$el.addClass(csscls('minimized'));
            this.resize();
        },

        /**
         * Checks if the panel is minimized
         *
         * @return {boolean}
         */
        isMinimized() {
            return this.$el.hasClass(csscls('minimized'));
        },

        /**
         * Close the debug bar
         *
         * @this {DebugBar}
         */
        close() {
            this.$resizehdle.hide();
            this.$header.hide();
            this.$body.hide();
            this.$restorebtn.show();
            localStorage.setItem('phpdebugbar-open', '0');
            this.$el.addClass(csscls('closed'));
            this.recomputeBottomOffset();
        },

        /**
         * Checks if the panel is closed
         *
         * @return {boolean}
         */
        isClosed() {
            return this.$el.hasClass(csscls('closed'));
        },

        /**
         * Restore the debug bar
         *
         * @this {DebugBar}
         */
        restore() {
            this.$resizehdle.show();
            this.$header.show();
            this.$restorebtn.hide();
            localStorage.setItem('phpdebugbar-open', '1');
            const tab = localStorage.getItem('phpdebugbar-tab');
            if (this.isTab(tab)) {
                this.showTab(tab);
            } else {
                this.showTab();
            }
            this.$el.removeClass(csscls('closed'));
            this.resize();
        },

        /**
         * Recomputes the margin-bottom css property of the body so
         * that the debug bar never hides any content
         */
        recomputeBottomOffset() {
            if (this.options.bodyMarginBottom) {
                if (this.isClosed()) {
                    return $('body').css('margin-bottom', this.bodyMarginBottomHeight || '');
                }

                const offset = Number.parseInt(this.$el.height()) + (this.bodyMarginBottomHeight || 0);
                $('body').css('margin-bottom', offset);
            }
        },

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
        },

        /**
         * Same as setDataMap() but appends to the existing map
         * rather than replacing it
         *
         * @this {DebugBar}
         * @param {object} map
         */
        addDataMap(map) {
            Object.assign(this.dataMap, map);
        },

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
        },

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
            if (this.isIframe) {
                window.top.phpdebugbar.addDataSet(data, id, `(iframe)${suffix || ''}`, show);
                return;
            }

            const nb = Object.keys(this.datasets).length + 1;
            id = id || nb;
            data.__meta.nb = nb;
            data.__meta.suffix = suffix;
            this.datasets[id] = data;

            const label = this.datesetTitleFormater.format(id, this.datasets[id], suffix, nb);

            if (this.datasetTab) {
                this.datasetTab.set('data', this.datasets);
                const datasetSize = Object.keys(this.datasets).length;
                this.datasetTab.set('badge', datasetSize > 1 ? datasetSize : null);
                this.datasetTab.$tab.show();
            }

            this.$datasets.append($(`<option value="${id}">${label}</option>`));
            if (this.$datasets.children().length > 1) {
                this.$datasets.show();
            }

            if (show === undefined || show) {
                this.showDataSet(id);
            }

            this.resize();

            return id;
        },

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
        },

        /**
         * Returns the data from a dataset
         *
         * @this {DebugBar}
         * @param {string} id
         * @return {object}
         */
        getDataSet(id) {
            return this.datasets[id];
        },

        /**
         * Switch the currently displayed dataset
         *
         * @this {DebugBar}
         * @param {string} id
         */
        showDataSet(id) {
            this.activeDatasetId = id;
            this.dataChangeHandler(this.datasets[id]);

            if (this.$datasets.val() !== id) {
                this.$datasets.val(id);
            }

            if (this.datasetTab) {
                this.datasetTab.get('widget').set('id', id);
            }
        },

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
            this.resize();
        },

        /**
         * Sets the handler to open past dataset
         *
         * @this {DebugBar}
         * @param {object} handler
         */
        setOpenHandler(handler) {
            this.openHandler = handler;
            this.openHandler.$el.attr('data-theme', this.$el.attr('data-theme'));
            if (handler !== null) {
                this.$openbtn.show();
            } else {
                this.$openbtn.hide();
            }
        },

        /**
         * Returns the handler to open past dataset
         *
         * @this {DebugBar}
         * @return {object}
         */
        getOpenHandler() {
            return this.openHandler;
        },

        enableAjaxHandlerTab() {
            this.datasetTab = new PhpDebugBar.DebugBar.Tab({ icon: 'history', title: 'Request history', widget: new PhpDebugBar.Widgets.DatasetWidget({
                debugbar: this
            }) });
            this.datasetTab.$tab.addClass(csscls('tab-history'));
            this.datasetTab.$tab.attr('data-collector', '__datasets');
            this.datasetTab.$el.attr('data-collector', '__datasets');
            this.datasetTab.$tab.insertAfter(this.$openbtn).hide();
            this.datasetTab.$tab.click(() => {
                if (!this.isMinimized() && this.activePanelName === '__datasets') {
                    this.minimize();
                } else {
                    this.showTab('__datasets');
                }
            });
            this.datasetTab.$el.appendTo(this.$body);
            this.controls.__datasets = this.datasetTab;
        }
    });

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
         * @return {Bool}
         */
        handle(response) {
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
        }

        setAutoShow(autoshow) {
            this.autoShow = autoshow;
            localStorage.setItem('phpdebugbar-ajaxhandler-autoshow', autoshow ? '1' : '0');
        }

        /**
         * Checks if the HEADER-id exists and loads the dataset using the open handler
         *
         * @param {Response|XMLHttpRequest} response
         * @return {Bool}
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
         * @return {Bool}
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
            const proxied = window.fetch;

            if (proxied !== undefined && proxied.polyfill !== undefined) {
                return;
            }

            window.fetch = function (...args) {
                const promise = proxied.apply(window, args);

                promise.then((response) => {
                    self.handle(response);
                }).catch((reason) => {
                    // Fetch request failed or aborted via AbortController.abort().
                    // Catch is required to not trigger React's error handler.
                });

                return promise;
            };
        }

        /**
         * Attaches an event listener to XMLHttpRequest
         */
        bindToXHR() {
            const self = this;
            const proxied = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function (method, url, async, user, pass) {
                const xhr = this;
                xhr.addEventListener('readystatechange', () => {
                    if (xhr.readyState === 4) {
                        self.handle(xhr);
                    }
                }, false);
                proxied.apply(xhr, [method, url, async, user, pass]);
            };
        }
    }

    PhpDebugBar.AjaxHandler = AjaxHandler;
})(PhpDebugBar.$);
