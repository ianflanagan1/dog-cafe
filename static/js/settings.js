document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('delete-account-form');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!confirm('Are you sure you want to delete your account? This is irreversible.')) {
            return;
        }

        const token = form.querySelector('input[name="delete_form_token"]').value;
        const url = form.action;

        const body = new URLSearchParams({ delete_form_token: token });

        del(url, (response) => {
            window.location.href = '/';
        }, body);
    });
});
