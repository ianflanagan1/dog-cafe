function get(url, callback)
{
    xhr(url, callback, 'GET');
}

function post(url, parameters, callback)
{
    let formData = new FormData();

    for ( var key in parameters ) {
        formData.append(key, parameters[key]);
    }

    xhr(url, callback, 'POST', formData);
}

function del(url, callback)
{
    xhr(url, callback, 'DELETE');
}

function xhr(url, callback, method = 'GET', body = null)
{
    options = {
        method: method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    if (body) {
        options.body = body;
    }

    fetch(url, options)
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');

            // Handle HTTP 4xx / 5xx
            if (!response.ok) {
                const errorBody = isJson ? await response.json() : await response.text();
                const message = isJson
                    ? JSON.stringify(errorBody, null, 2)
                    : errorBody;

                throw new Error(`HTTP ${response.status}:\n${message}`);
            }

            // Expect no content
            if (response.status === 204) {
                return null;
            }

            if (!isJson) {
                throw new Error('Expected JSON response but got empty body');
            }

            return response.json();
        })
        .then(data => {
            callback?.(data);
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}
