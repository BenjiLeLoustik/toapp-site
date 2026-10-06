/** ./assets/js/components/_Request.js */

window._Request = {

    GET: function (url, data = {}) {
        return this._request('GET', url, data);
    },

    POST: function (url, data = {}) {
        return this._request('POST', url, data);
    },

    PUT: function (url, data = {}) {
        return this.request('PUT', url, data);
    },

    PATCH: function (url, data = {}) {
        return this.request('PATCH', url, data);
    },

    DELETE: function (url, data = {}) {
        return this.request('DELETE', url, data);
    },

    _request: async function (method, url, data = {}) {
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json'
            },
        };

        if (method === 'GET') {
            const params = new URLSearchParams(data);
            const query = params.toString();

            if (query) {
                url += `?${query}`;
            }
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    },

};