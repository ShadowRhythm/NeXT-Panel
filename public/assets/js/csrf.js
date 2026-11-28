(function () {
    function getCsrfToken() {
        const match = document.cookie.match(/(?:^|;\s*)_csrf=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function isSameOrigin(url) {
        if (typeof url !== 'string' && !(typeof URL !== 'undefined' && url instanceof URL)) {
            return false;
        }
        try {
            return new URL(url, window.location.origin).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function isSafeMethod(method) {
        const m = (method || 'GET').toUpperCase();
        return m === 'GET' || m === 'HEAD' || m === 'OPTIONS';
    }

    function attachJqueryCsrf() {
        if (typeof window.jQuery === 'undefined' || window.jQuery.__csrfAttached) {
            return;
        }
        window.jQuery.__csrfAttached = true;
        window.jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
            if (options.crossDomain || isSafeMethod(options.type || options.method)) {
                return;
            }
            const token = getCsrfToken();
            if (token) {
                jqXHR.setRequestHeader('X-CSRF-Token', token);
            }
        });
    }

    attachJqueryCsrf();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachJqueryCsrf);
    }

    if (typeof window.htmx !== 'undefined') {
        window.htmx.on('htmx:configRequest', function (evt) {
            if (isSafeMethod(evt.detail && evt.detail.verb)) {
                return;
            }
            if (evt.detail && evt.detail.path && !isSameOrigin(evt.detail.path)) {
                return;
            }
            const token = getCsrfToken();
            if (token) {
                evt.detail.headers['X-CSRF-Token'] = token;
            }
        });
    }

    if (typeof window.fetch === 'function') {
        const origFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            const url = typeof input === 'string'
                ? input
                : (input && typeof input.url === 'string' ? input.url : '');
            const method = (init && init.method)
                || (input && typeof input.method === 'string' ? input.method : 'GET');

            if (!isSafeMethod(method) && isSameOrigin(url)) {
                const token = getCsrfToken();
                if (token) {
                    const nextInit = Object.assign({}, init || {});
                    const headers = new Headers(
                        (init && init.headers) || (input && input.headers) || undefined
                    );
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', token);
                    }
                    nextInit.headers = headers;
                    return origFetch(input, nextInit);
                }
            }
            return origFetch(input, init);
        };
    }

    if (typeof window.XMLHttpRequest !== 'undefined') {
        const proto = window.XMLHttpRequest.prototype;
        const origOpen = proto.open;
        const origSetHeader = proto.setRequestHeader;
        const origSend = proto.send;

        proto.open = function (method, url) {
            this._csrfMethod = method;
            this._csrfUrl = url;
            this._hasCsrfHeader = false;
            return origOpen.apply(this, arguments);
        };

        proto.setRequestHeader = function (name, value) {
            if (typeof name === 'string' && name.toLowerCase() === 'x-csrf-token') {
                this._hasCsrfHeader = true;
            }
            return origSetHeader.apply(this, arguments);
        };

        proto.send = function () {
            if (
                this.readyState === 1 &&
                !this._hasCsrfHeader &&
                !isSafeMethod(this._csrfMethod) &&
                isSameOrigin(this._csrfUrl)
            ) {
                const token = getCsrfToken();
                if (token) {
                    origSetHeader.call(this, 'X-CSRF-Token', token);
                    this._hasCsrfHeader = true;
                }
            }
            return origSend.apply(this, arguments);
        };
    }
})();
