/** ./assets/js/app.js */

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});

window.initPreferenceChange = function (name, route, attribute, reload = false, callback = null) {
    document.querySelectorAll(`[data-${name}-change]`).forEach(element => {

        const eventName = element.matches('select, input, textarea') ? 'change' : 'click';

        element.addEventListener(eventName, event => {
            const value = event.currentTarget.matches('select, input, textarea')
                ? event.currentTarget.value
                : event.currentTarget.dataset[`${name}Change`];

            console.log(value);

            window._Request.GET(route, {
                [name]: value,
            }).then(response => {
                if (reload) {
                    window.location.reload();
                    return;
                }

                document.documentElement.setAttribute(attribute, response[name]);

                if (callback) {
                    callback(response[name]);
                }
            });
        });
    });
};

window.changeButtonVariant = function (button, variant) {
    const currentVariant = button.dataset.variant;

    if (currentVariant) {
        button.classList.remove(`button--${currentVariant}`);
    }

    button.classList.add(`button--${variant}`);
    button.dataset.variant = variant;
};