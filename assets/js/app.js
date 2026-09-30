/** ./assets/js/app.js */

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});

window.initPreferenceChange = function (name, route, attribute, reload = false) {
    document.querySelectorAll(`[data-${name}-change]`).forEach(btn => {
        btn.addEventListener('click', event => {
            const value = event.currentTarget.dataset[`${name}Change`];

            window._Request.GET(route, {
                [name]: value,
            }).then(response => {
                if (reload) {
                    window.location.reload();
                    return;
                }

                document.documentElement.setAttribute(attribute, response[name]);
            })
        });
    });
};