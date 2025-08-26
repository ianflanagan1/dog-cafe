let searchButtonMobile, searchButtonDesktop, closeSearchButton, search, searchCover, searchInput, searchResults;
let searchCache = {};
let searchText;

document.addEventListener('DOMContentLoaded', function() {
    searchButtonMobile = document.getElementById('searchButtonMobile');
    searchButtonDesktop = document.getElementById('searchButtonDesktop');
    closeSearchButton = document.getElementById('closeSearchButton');
    search = document.getElementById('search');
    searchCover = document.getElementById('searchCover');
    searchInput = document.getElementById('searchInput');
    searchResults = document.getElementById('searchResults');

    searchButtonMobile.addEventListener("click", openSearch);
    searchButtonDesktop.addEventListener("click", openSearch);
    closeSearchButton.addEventListener("click", closeSearch);
    searchCover.addEventListener("click", closeSearch);

    searchInput.addEventListener('keyup', function (event) {
        searchTown(searchInput.value);
    });
}, false);

function openSearch()
{
    search.style.display = 'block';
    searchCover.style.display = 'block';

    searchInput.focus();
    //searchInput.select();
}

function closeSearch()
{
    search.style.display = 'none';
    searchCover.style.display = 'none';
}

function searchTown(text)
{
    text = text.trim();
    searchText = text;

    if (text == '') {
        clearSearch();
        return;
    }

    if (searchCache[text]) {
        displaySearch(searchCache[text]);
        return;
    }

    displaySearching();

    get('/api/v1/search-town?search=' + text, function (response) {
        searchCache[text] = response.data.results

        if (text == searchText) {
            displaySearch(searchCache[text]);
        }
    });
}

function clearSearch()
{
    searchResults.innerHTML = '';
}

function displaySearching()
{
    searchResults.innerHTML = '<div id="searching">Searching...</div>';
}

function displaySearch(results) {
    if (!results.length) {
        searchResults.innerHTML = '<div id="noResults">No results</div>';
        return;
    }

    let html = '';

    for (let i = 0; i < results.length; i++) {
        html += `<a href="${SEARCH_BASE_URL}/${results[i][0]}${query}">
            <div>
                <div>${results[i][1]}</div>
                <div>${results[i][2]}</div>
            </div>
        </a>`;
    }

    searchResults.innerHTML = html;
}
