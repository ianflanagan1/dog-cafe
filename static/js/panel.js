let filterButton, closePanelButton, typesCafes, typesRestaurants, typesBars, openNow;

document.addEventListener('DOMContentLoaded', function() {
    filterButton = document.getElementById('filterButton');
    closePanelButton = document.getElementById('closePanelButton');
    typesCafes = document.getElementById('typesCafes');
    typesRestaurants = document.getElementById('typesRestaurants');
    typesBars = document.getElementById('typesBars');
    openNow = document.getElementById('openNow');

    filterButton.addEventListener("click", openPanel);
    closePanelButton.addEventListener("click", closePanel);

    typesCafes.addEventListener('change', (event) => {
        panelCheckbox('typesCafes', event.currentTarget.checked);
    });

    typesRestaurants.addEventListener('change', (event) => {
        panelCheckbox('typesRestaurants', event.currentTarget.checked);
    });

    typesBars.addEventListener('change', (event) => {
        panelCheckbox('typesBars', event.currentTarget.checked);
    });

    openNow.addEventListener('change', (event) => {
        panelCheckbox('openNow', event.currentTarget.checked);
    });

    // TODO: check if this might be too early sometimes
    parameters.typesCafes = typesCafes.checked;
    parameters.typesRestaurants = typesRestaurants.checked;
    parameters.typesBars = typesBars.checked;
    parameters.openNow = openNow.checked;

    setQuery();
}, false);

function openPanel()
{
    panel.classList.remove('mobile-hide');
}

function closePanel()
{
    panel.classList.add('mobile-hide');
}
