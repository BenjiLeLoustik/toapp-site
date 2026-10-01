/** ./assets/js/partials/_Header.js */

window._Header = {

    init: function () {
        this.triggerMenu();
    },

    triggerMenu: function () {
        const toggle = document.querySelector('[data-header-menu-toggle]');
        const menu = document.querySelector('[data-header-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isOpen = menu.classList.toggle('is-open');

            toggle.setAttribute(
                'aria-expanded',
                isOpen ? 'true' : 'false'
            );
        });
    }

};

document.addEventListener('DOMContentLoaded', () => window._Header.init());