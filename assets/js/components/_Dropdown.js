/** ./assets/js/components/_Dropdown.js */

window._Dropdown = {

    init: function () {
        this.triggerToggleDropdown();
        this.triggerCloseOnOutsideClick();
        this.triggerKeyboard();
    },

    triggerToggleDropdown: function () {
        const btnsToggleDropdown = document.querySelectorAll('[data-dropdown-trigger]');

        if (!btnsToggleDropdown.length) {
            return;
        }

        btnsToggleDropdown.forEach(btnToggleDropdown => {
            btnToggleDropdown.addEventListener('click', (event) => {
                const dropdownName = event.currentTarget.closest('.dropdown').dataset.dropdownName;
                const dropdown = this._checkIsValidDropdown(dropdownName);

                if (!dropdown) {
                    return;
                }

                if (dropdown.classList.contains('dropdown--active')) {
                    this._closeOnlyOneDropdown(dropdown);
                    return;
                }

                this._closeAllDropdowns();
                this._openDropdown(dropdown);
            });
        });
    },

    triggerCloseOnOutsideClick: function () {
        document.addEventListener('click', (event) => {
            if (event.target.closest('.dropdown')) {
                return;
            }

            this._closeAllDropdowns();
        });
    },

    triggerKeyboard: function () {
        document.addEventListener('keydown', (event) => {
            const dropdown = document.querySelector('.dropdown.dropdown--active');

            if (!dropdown) {
                return;
            }

            if (event.key === 'Escape') {
                this._closeOnlyOneDropdown(dropdown);
                dropdown.querySelector('[data-dropdown-trigger]').focus();
                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                this._focusItem(dropdown, event.key === 'ArrowDown' ? 1 : -1);
                return;
            }

            if (event.key === 'Tab') {
                this._closeOnlyOneDropdown(dropdown);
            }
        });
    },

    _checkIsValidDropdown: function (dropdownName) {
        let dropdowns = document.querySelectorAll(`.dropdown[data-dropdown-name="${dropdownName}"]`);
        if (dropdowns.length !== 1) {
            return null;
        }

        return dropdowns[0];
    },

    _closeAllDropdowns: function () {
        let dropdowns = document.querySelectorAll('.dropdown[data-dropdown-name]');
        if (!dropdowns.length) {
            return;
        }

        dropdowns.forEach(dropdown => {
            this._closeOnlyOneDropdown(dropdown);
        });
    },

    _closeOnlyOneDropdown: function (dropdown) {
        dropdown.classList.remove('dropdown--active');
        dropdown.querySelector('[data-dropdown-trigger]').setAttribute('aria-expanded', 'false');
    },

    _openDropdown: function (dropdown) {
        if (dropdown.classList.contains('dropdown--active')) {
            return;
        }

        dropdown.classList.add('dropdown--active');
        dropdown.querySelector('[data-dropdown-trigger]').setAttribute('aria-expanded', 'true');
    },

    _focusItem: function (dropdown, step) {
        const items = Array.from(dropdown.querySelectorAll('[data-dropdown-item]'));

        if (!items.length) {
            return;
        }

        const index = items.indexOf(document.activeElement);
        const next = index === -1 ? (step > 0 ? 0 : items.length - 1) : (index + step + items.length) % items.length;

        items[next].focus();
    }

};

document.addEventListener('DOMContentLoaded', () => window._Dropdown.init());