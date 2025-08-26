const triggerPercentage = 0.25;

let nextPage, prevPage, nextCache, prevCache;
let doc, body, pageContent, pageHolder, header;
let currPage, totalPages;
let lastScroll = 0;
let timestamp;
let isLoading = false;
let parameters = {};
let query = '';

document.addEventListener("DOMContentLoaded", function(event) {
    doc = document.documentElement;

    totalPages = parseInt(document.getElementById('totalPagesSetter').value);
    currPage = parseInt(document.getElementById('currPageSetter').value);

    pageContent = document.getElementById('pageContent');
    pageHolder = document.getElementById('pageHolder');
    header = document.getElementById('header');

    prevPage = currPage - 1;
    nextPage = currPage + 1;

    history.replaceState(null, null, getUrl(currPage));

    timestamp = Date.now();
    setQuery();

    document.getElementsByTagName('body')[0].onscroll = function() {
        let scroll_pos = doc.scrollTop;

        if (scroll_pos >= 0.9 * (pageContent.offsetHeight - doc.offsetHeight)) {
            if (!isLoading) {
                loadNext();
            }
        }

        if (scroll_pos <= 0.9 * header.offsetHeight) {
            if (!isLoading) {
                loadPrev();
            }
        }

        // Adjust the URL based on the top item shown for reasonable amounts of items
        if (Math.abs(scroll_pos - lastScroll) > doc.offsetHeight * 0.1) {
            lastScroll = scroll_pos;

            let pages = document.getElementsByClassName('page');
            for (let i = 0; i < pages.length; i++) {
                if (mostlyVisible(pages[i])) {
                    history.replaceState(null, null, getUrl(pages[i].dataset.page));
                    return;
                }
            }
        }
    };

    if (doc.offsetHeight > pageContent.offsetHeight) {
        if (nextPage <= totalPages) {
            loadNext();
        } else {
            let filler = document.createElement('div');
            filler.id = 'filler';
            filler.style.height = (doc.offsetHeight - pageContent.offsetHeight) + 'px';
            pageContent.after(filler);
        }
    }

    // If not on page 1, scroll down a little to hide the header
    if (prevPage) {
        doc.scrollTo(0, header.offsetHeight);
    }

    primeCache();
});

function loadNext()
{
    if (nextPage <= totalPages) {
        isLoading = true; // TODO: handle the case of server not responding

        if (nextCache) {
            showNext(nextCache);
            isLoading = false;
        } else {
            getNewPage(nextPage, function (data) {
                showNext(data);
                isLoading = false;
            });
        }

        function showNext(data)
        {
            pageHolder.innerHTML += data.items;
            nextCache = false;

            nextPage++;

            getNewPage(nextPage, function (data) {
                nextCache = data;
            });
        }
    }
};

function loadPrev()
{
    if (prevPage) {
        isLoading = true; // TODO: handle the case of server not responding

        if (prevCache) {
            showPrev(prevCache);
            isLoading = false;
        } else {
            getNewPage(prevPage, function (data) {
                showPrev(data);
                isLoading = false;
            });
        }

        function showPrev(data)
        {
            pageHolder.innerHTML = data.items + pageHolder.innerHTML;

            item_height = document.getElementsByClassName('page')[0].offsetHeight;
            doc.scrollTo(0, doc.scrollTop + item_height); // Adjust scroll
            prevCache = false;

            prevPage--;
            
            getNewPage(prevPage, function (data) {
                prevCache = data;
            });
        }
    }
};

function mostlyVisible(element)
{
    let scroll_pos = doc.scrollTop;
    let window_height = doc.offsetHeight;
    let el_top = element.offsetTop;
    let el_height = element.offsetHeight;
    let el_bottom = el_top + el_height;
    return ((el_bottom - el_height * triggerPercentage > scroll_pos) && (el_top < (scroll_pos + 0.5 * window_height)));
}

function primeCache()
{
    getNewPage(prevPage, function (data) {
        prevCache = data;
    });
    getNewPage(nextPage, function (data) {
        nextCache = data;
    });
}

function getNewPage(page, callback)
{
    let url = getDataUrl(page);

    if (!url) {
        return;
    }
    get(url, function (response) {
        if (response.data.timestamp != timestamp) {
            return;
        }

        totalPages = response.data.totalPages;

        if (totalPages < 1) {
            pageHolder.innerHTML = '<div id="paginateNoResults">No results</div>';
            return;
        }

        response.data.items = createPaginatedHtml(response.data, page);
        callback(response.data);
    });
}

function createPaginatedHtml(data, page)
{
    let output = `<div class="page" data-page="${page}">`
    if (page > 1) {
        output += `<div class="page-title">Page ${page}</div>`;
    }

    for (let i = 0; i < data.items.length; i++) {
        output += createItemHtml(data.items[i]);
    }

    output += '</div>';

    return output;
}

function createItemHtml(item)
{
    const TYPE_CAFE = 1;
    const TYPE_RESTAURANT = 2;
    const TYPE_BAR = 3;
    
    let typesHtml = '';
    let timeHtml = '';
    let imageHtml = '';

    if (item.types.length) {
        for (let i = 0; i < item.types.length; i++) {
            switch (item.types[i]) {
                case TYPE_CAFE:         typesHtml += '<div class="cafe">Cafe</div>';                break;
                case TYPE_RESTAURANT:   typesHtml += '<div class="restaurant">Restaurant</div>';    break;
                case TYPE_BAR:          typesHtml += '<div class="bar">Bar</div>';                  break;
            }
        }
        typesHtml = `<div div class="types-holder">${typesHtml}</div>`;
    }

    timeHtml = item.open
        ? `<span class="open">Open</span> till ${item.change_time}`
        : `<span class="closed">Opens ${item.change_time} </span>`;

    imageHtml = item.image
        ? `<img src="/images/small/${item.image}" loading="lazy" />`
        : '<img src="/images/core/no-image.svg" />';

    return `<a class="venue" href="/venue/${item.ext_id}">
            ${imageHtml}
            <div class="body">
                <div>${item.name}</div>
                ${typesHtml}
                <div>${timeHtml}</div>
            </div>
        </a>
    `;
}

function panelCheckbox(name, value) {
    parameters[name] = value;
    currPage = 1;
    prevPage = 0;
    nextPage = 2;

    prevCache = null;
    nextCache = null;
    totalPages = null;
    pageHolder.innerHTML = '';

    timestamp = Date.now();
    setQuery();

    history.replaceState(null, null, getUrl(currPage));

    getNewPage(currPage, function (data) {
        pageHolder.innerHTML = data.items;
        primeCache();
    });
}

function processUserLocation(lat, lng) {
    addMarker([3, lat, lng]);
    map.setCenter({
        lat: lat,
        lng: lng,
    });
    map.setZoom(12);
    loadPoints();
}

function setQuery()
{
    let segments = [];
    let types = [];
    
    if (parameters.typesCafes) {
        types.push('cafe');
    }
    
    if (parameters.typesRestaurants) {
        types.push('restaurant');
    }
    
    if (parameters.typesBars) {
        types.push('bar');
    }

    if (types.length > 0 && types.length < 3) {
        segments.push('types=' + types.join());
    }

    if (parameters.openNow) {
        segments.push('open_now');
    }

    if (segments.length) {
        query = '?' + segments.join('&');
    } else {
        query = '';
    }
}
