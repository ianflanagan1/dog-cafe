const URL_BASE = '/list';
const SEARCH_BASE_URL = '/list';
let ajaxUrl = '/api/v1/search-list';

let cityUrl;

document.addEventListener("DOMContentLoaded", function(event) {
    cityUrl = document.getElementById('cityUrlSetter').value;
});

function getUrl(page)
{
    let str = query;

    if (page > 1) {
        str += query == '' ? '?' : '&';
        str += 'page=' + page;
    }

    return URL_BASE + '/' + cityUrl + str;
}

function getDataUrl(page)
{
    if (
        page < 1
        || (totalPages !== null && page > totalPages)
    ) {
        return false;
    }

    let str = query;

    if (page > 1) {
        str += str == '' ? '?' : '&';
        str += 'page=' + page + '&timestamp=' + timestamp;

    } else {
        str += str == '' ? '?' : '&';
        str += 'timestamp=' + timestamp;
    }

    return ajaxUrl + '/' + cityUrl + str;
}
