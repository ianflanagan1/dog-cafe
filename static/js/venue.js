const SEARCH_BASE_URL = '/list';
let map, favButton;
let fav, extId;

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

    //history.replaceState(null, null, URL_BASE + query);

    // map
    map = new Map(document.getElementById('map'), {
        zoom: 16,
        center: {
            lat: lat,
            lng: lng,
        },
        disableDefaultUI: true,
        clickableIcons: false,
        mapId: 'dog-cafe-map',
    });

    let iconImageSelected = new google.maps.marker.PinElement({
        glyphColor: 'white',
        background: '#ed2020',
        borderColor: 'black',
    });

    let marker = new google.maps.marker.AdvancedMarkerElement({
        map: map,
        position: { lat: lat, lng: lng },
        content: iconImageSelected.element,
        zIndex: 2,
    });
}

document.addEventListener('DOMContentLoaded', function(){
    fav = parseInt(document.getElementById('favSetter').value);
    extId = document.getElementById('extIdSetter').value;

    favButton = document.getElementById('favButton');
    favButton.addEventListener('click', clickFav);
}, false);

function clickFav()
{
    if (fav) {
        delFav();

    } else {
        addFav()
    }
}

function addFav()
{
    fav = true;
    favButton.innerHTML = '<img src="/images/core/fav-on.svg" />';

    post(
        '/api/v1/favs',
        {
            ext_id: extId,
        },
    );
}

function delFav()
{
    fav = false;
    favButton.innerHTML = '<img src="/images/core/fav-off.svg" />';

    del('/api/v1/favs/' + extId);
}
