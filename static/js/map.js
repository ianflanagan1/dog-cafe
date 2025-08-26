const URL_BASE = '/map'; // TODO: update
const SEARCH_BASE_URL = '/map';

const POINT_TYPE_SINGLE = 1;
const POINT_TYPE_CLUSTER = 2;
const POINT_TYPE_USER_LOCATION = 3;
const POINT_TYPE_SELECTED_MARKER = 4;
const POINT_TYPE_INDEX = 0;
const POINT_LATITUDE_INDEX = 1;
const POINT_LONGITUDE_INDEX = 2;
const SINGLE_POINT_EXT_ID_INDEX = 3;
const CLUSTER_POINT_COUNT_INDEX = 3;
const CLUSTER_POINT_BOUNDING_BOX_INDEX = 4;
const BOUNDING_BOX_NW_LATITUDE_INDEX = 0;
const BOUNDING_BOX_NW_LONGITUDE_INDEX = 1;
const BOUNDING_BOX_SW_LATITUDE_INDEX = 2;
const BOUNDING_BOX_SW_LONGITUDE_INDEX = 3;

let map, venueBox, venueBoxContent, closeVenueBox, seeVenue, getLocation;
let iconImageSelected;
let markers = [];
let timestamp = {
    loadPoints: null,
};
let venueCache = {};
let selectedVenue = false;
let userMarker = null;
let hiddenMarker = false;
let parameters = {};
let query = '';

(g=>{var h,a,k,p='The Google Maps JavaScript API',c='google',l='importLibrary',q='__ib__',m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement('script'));e.set('libraries',[...r]+'');for(k in g)e.set(k.replace(/[A-Z]/g,t=>'_'+t[0].toLowerCase()),g[k]);e.set('callback',c+'.maps.'+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+' could not load.'));a.nonce=m.querySelector('script[nonce]')?.nonce||'';m.head.append(a)}));d[l]?console.warn(p+' only loads once. Ignoring:',g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: 'AIzaSyCSpBqids50TGeihGvZEqdUpNQZ68HUo9c',
    v: 'weekly',
});

initMap();

async function initMap()
{
    const { Map } = await google.maps.importLibrary('maps');
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

    let lat = parseFloat(document.getElementById('latSetter').value);
    let lng = parseFloat(document.getElementById('lngSetter').value);
    let zoom = parseInt(document.getElementById('zoomSetter').value);

    setQuery();
    history.replaceState(null, null, URL_BASE + query);

    getLocation = document.getElementById('getLocation');

    getLocation.addEventListener('click', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    processUserLocation(position.coords.latitude, position.coords.longitude);
                },
                () => {
                    // TODO:
                    //handleLocationError(true, infoWindow, map.getCenter());
                },
            );
        } else {
            // TODO:Browser doesn't support Geolocation
            //handleLocationError(false, infoWindow, map.getCenter());
        }
        closePanel();
    });

    map = new Map(document.getElementById('map'), {
        minZoom: 3,
        maxZoom: 22,
        zoom: zoom,
        center: {
            lat: lat,
            lng: lng,
        },
        disableDefaultUI: true,
        clickableIcons: false,
        mapId: 'dog-cafe-map',
    });
    map.addListener('dragend', (e) => {
        loadPoints();
    });
    map.addListener('zoom_changed', (e) => {
        loadPoints();
    });
    map.addListener('click', (e) => {
        unselectVenue();
    });

    venueBox = document.getElementById('venueBox');
    venueBoxContent = document.getElementById('venueBoxContent');
    closeVenueBox = document.getElementById('closeVenueBox');
    seeVenue = document.getElementById('seeVenue');

    venueBox.addEventListener('click', () => {
        if (isMobileView()) {
            goToVenue();
        }
    });

    seeVenue.addEventListener('click', goToVenue);

    closeVenueBox.addEventListener("click", unselectVenue);

    google.maps.event.addListenerOnce(map, 'idle', function () {
        loadPoints();
    });

    iconImageSelected = new google.maps.marker.PinElement({
        glyphColor: 'white',
        background: '#ed2020',
        borderColor: 'black',
    });
}

function panelCheckbox(name, value) {
    parameters[name] = value;
    setQuery();
    history.replaceState(null, null, URL_BASE + query);
    unselectVenue();
    loadPoints();
}

function processUserLocation(lat, lng) {
    addMarker([POINT_TYPE_USER_LOCATION, lat, lng]);
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

    if (types.length && types.length < 3) {
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

function loadPoints()
{
    let zoom = map.getZoom();
    let bounds = map.getBounds();

    if (!bounds || !zoom) {
        return;
    }

    let ne = bounds.getNorthEast();
    let sw = bounds.getSouthWest();

    timestamp.loadPoints = Date.now();

    let str;

    str = query !== ''
        ? query + '&'
        : '?';

    get('/api/v1/search-map' + str + 'lat1=' + sw.lat() + '&lng1=' + sw.lng() + '&lat2=' + ne.lat() + '&lng2=' + ne.lng() + '&zoom=' + zoom + '&height=' + map.getDiv().offsetHeight + '&width=' + map.getDiv().offsetWidth + '&timestamp=' + timestamp.loadPoints, function (response) {
        if (response.data.timestamp == timestamp.loadPoints) {
            let points = response.data.points;

            if (selectedVenue) {
                hiddenMarker = false;

                for (let i = 0; i < points.length; i++) {
                    if (
                        points[i][POINT_TYPE_INDEX] == POINT_TYPE_SINGLE
                        && points[i][SINGLE_POINT_EXT_ID_INDEX] == selectedVenue.extId
                    ) {
                        hiddenMarker = points[i];
                        points.splice(i, 1);
                        break;
                    }
                }
            }

            updateMarkers(points);
        }
    });
}

function deleteAllMarkers()
{
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }

    markers = [];
}

function updateMarkers(points)
{
    outer:
    for (let m = 0; m < markers.length; m++) {
        for (let n = 0; n < points.length; n++) {
            // remove from points (new markers)
            if (
                points[n][POINT_TYPE_INDEX] == markers[m].type
                && points[n][POINT_LATITUDE_INDEX] == markers[m].position.lat
                && points[n][POINT_LONGITUDE_INDEX] == markers[m].position.lng
            ) {
                points.splice(n, 1);
                n--;
                continue outer;
            }
        }

        // delete old marker
        markers[m].setMap(null);
        markers.splice(m, 1);
        m--;
    }

    for (let n = 0; n < points.length; n++) {
        addMarker(points[n]);
    }
}

function addMarker(point)
{
    let marker;

    switch (point[POINT_TYPE_INDEX]) {
        case POINT_TYPE_SINGLE:
            const single = document.createElement("div");
            single.className = 'single';

            marker = new google.maps.marker.AdvancedMarkerElement({
                map: map,
                position: { lat: point[POINT_LATITUDE_INDEX], lng: point[POINT_LONGITUDE_INDEX] },
                content: single,
                zIndex: 2,
            });

            marker.type = 1;
            marker.extId = point[SINGLE_POINT_EXT_ID_INDEX];

            marker.addListener('click', (e) => {
                selectVenue(marker.extId, e.latLng.lat(), e.latLng.lng());
            });
            break;

        case POINT_TYPE_CLUSTER:
            const cluster = document.createElement("div");
            cluster.className = 'cluster';
            cluster.textContent = point[CLUSTER_POINT_COUNT_INDEX];

            if (point[CLUSTER_POINT_COUNT_INDEX] > 9) {
                if (point[CLUSTER_POINT_COUNT_INDEX] < 100) {
                    cluster.classList.add('digits-2');
                } else {
                    if (point[CLUSTER_POINT_COUNT_INDEX] < 1000) {
                        cluster.classList.add('digits-3');
                    } else {
                        if (point[CLUSTER_POINT_COUNT_INDEX] < 10000) {
                            cluster.classList.add('digits-4');
                        } else {
                            cluster.classList.add('digits-5');
                        }
                    }
                }
            }

            marker = new google.maps.marker.AdvancedMarkerElement({
                map,
                position: { lat: point[POINT_LATITUDE_INDEX], lng: point[POINT_LONGITUDE_INDEX] },
                content: cluster,
                zIndex: 2,
            });

            marker.type = 2;
            marker.bounds = point[CLUSTER_POINT_BOUNDING_BOX_INDEX];

            marker.addListener('click', (e) => {
                let bounds = new google.maps.LatLngBounds();
                let point1 = new google.maps.LatLng(marker.bounds[BOUNDING_BOX_NW_LATITUDE_INDEX], marker.bounds[BOUNDING_BOX_NW_LONGITUDE_INDEX]);
                let point2 = new google.maps.LatLng(marker.bounds[BOUNDING_BOX_SW_LATITUDE_INDEX], marker.bounds[BOUNDING_BOX_SW_LONGITUDE_INDEX]);

                bounds.extend(point1);
                bounds.extend(point2);

                map.fitBounds(bounds);
                unselectVenue();
            });
            break;

        case POINT_TYPE_USER_LOCATION:
            const userLocationPin = document.createElement("div");
            userLocationPin.id = 'userLocation';
            userLocationPin.innerHTML = '<div></div>';

            userMarker = new google.maps.marker.AdvancedMarkerElement({
                map,
                position: { lat: point[POINT_LATITUDE_INDEX], lng: point[POINT_LONGITUDE_INDEX] },
                content: userLocationPin,
                zIndex: 1,
            });
            return;

        case POINT_TYPE_SELECTED_MARKER:
            selectedVenue.marker = new google.maps.marker.AdvancedMarkerElement({
                map: map,
                position: { lat: point[POINT_LATITUDE_INDEX], lng: point[POINT_LONGITUDE_INDEX] },
                content: iconImageSelected.element,
                zIndex: 3,
            });
            return;
    }

    markers.push(marker);
}

function selectVenue(extId, lat, lng)
{
    if (selectedVenue) {
        if (selectedVenue.extId == extId) {
            return;
        }

        removeSelectedMarker();
    }

    selectedVenue = {
        extId: extId,
    };

    hiddenMarker = [
        1,
        lat,
        lng,
        extId,
    ];

    for (let i = 0; i < markers.length; i++) {
        if (
            markers[i].type == 1
            && markers[i].extId == extId
        ) {
            markers[i].setMap(null);
            markers.splice(i, 1);
            break;
        }
    }

    addMarker([POINT_TYPE_SELECTED_MARKER, lat, lng]);

    if (venueCache[extId]) {
        showVenueShortWithExtId(extId);
        return;
    }
    
    showVenueShortWithoutExtId();

    // TODO: still need timestamp for map search?
    // TODO: should dragging hide open venue short?
    // TODO: check for X-Requested-With head in PHP
    get('/api/v1/venue-short?ext_id=' + extId, function (response) {
        if (response.data.errors) {
            return;
        }

        venueCache[extId] = response.data.venue;
        if (
            selectedVenue
            && selectedVenue.extId == extId
        ) {
            showVenueShortWithExtId(extId);
        }
    });

    function showVenueShortWithExtId(extId)
    {
        const TYPE_CAFE = 1;
        const TYPE_RESTAURANT = 2;
        const TYPE_BAR = 3;

        let item = venueCache[extId];
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
            ? `<img src="/images/small/${item.image}" />`
            : '<img src="/images/core/no-image.svg" />';

        venueBoxContent.innerHTML = (`
                ${imageHtml}
                <div class="body">
                    <div>${item.name}</div>
                    ${typesHtml}
                    <div>${timeHtml}</div>
                </div>`);
        venueBox.style.display = 'block';
    }

    function showVenueShortWithoutExtId()
    {
        venueBox.setAttribute('href', '');
        venueBoxContent.innerHTML = (`<div>
                <img />
                <div></div>
            </div>
            `);
        venueBox.style.display = 'block';
    }
}

function unselectVenue()
{
    if (!selectedVenue) {
        return;
    }

    venueBox.style.display = 'none';
    removeSelectedMarker()
}

function removeSelectedMarker()
{
    selectedVenue.marker.setMap(null);
    selectedVenue = false;

    if (hiddenMarker) {
        addMarker(hiddenMarker);
        hiddenMarker = false;
    }
}

function goToVenue()
{
    window.location.href = '/venue/' + selectedVenue.extId;
}

function isMobileView()
{
    return window.getComputedStyle(filterButton, null).display != 'none';
}
