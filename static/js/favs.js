const URL_BASE = '/favs';
let ajaxUrl = '/api/v1/search-favs';

function getUrl(page)
{
    let str = query;

    if (page > 1) {
        str += query == '' ? '?' : '&';
        str += 'page=' + page;
    }

    return URL_BASE + str;
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

    return ajaxUrl + str;
}
