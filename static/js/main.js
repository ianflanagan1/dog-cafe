let dropdownMenuButton, dropdown, dropdownClose;

document.addEventListener('DOMContentLoaded', function() {
    dropdownMenuButton = document.getElementById('dropdownMenuButton');
    dropdown = document.getElementById('dropdown');
    dropdownClose = document.getElementById('dropdownClose');

    dropdownMenuButton.addEventListener('click', (event) => {
        openDropdown();
    });

    dropdownClose.addEventListener('click', (event) => {
        closeDropdown();
    });
}, false);

function openDropdown()
{
    dropdown.style.display = 'block';
}

function closeDropdown()
{
    dropdown.style.display = 'none';
}
