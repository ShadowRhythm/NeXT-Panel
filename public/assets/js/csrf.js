(function () {
    function getCsrfToken() {
        const match = document.cookie.match(/(?:^|;\s*)_csrf=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function isSameOrigin(url) {
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
})();
