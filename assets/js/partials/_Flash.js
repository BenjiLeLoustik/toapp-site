/** ./assets/js/components/_Flash.js */

window._Flash = {

    duration: 5000,

    init: function () {
        this.triggerCloseFlash();
        this.triggerAutoClose();
    },

    triggerCloseFlash: function () {
        const btnsCloseFlash = document.querySelectorAll('[data-flash-close]');

        if (!btnsCloseFlash.length) {
            return;
        }

        btnsCloseFlash.forEach(btnCloseFlash => {
            btnCloseFlash.addEventListener('click', (event) => {
                const flash = event.currentTarget.closest('[data-flash]');

                if (!flash) {
                    return;
                }

                this._closeFlash(flash);
            });
        });
    },

    triggerAutoClose: function () {
        const flashes = document.querySelectorAll('[data-flash]');

        if (!flashes.length) {
            return;
        }

        flashes.forEach(flash => {
            let timer = setTimeout(() => this._closeFlash(flash), this.duration);

            flash.addEventListener('mouseenter', () => clearTimeout(timer));
            flash.addEventListener('mouseleave', () => {
                timer = setTimeout(() => this._closeFlash(flash), this.duration);
            });
        });
    },

    _closeFlash: function (flash) {
        if (flash.classList.contains('flash--hidden')) {
            return;
        }

        flash.classList.add('flash--hidden');

        setTimeout(() => {
            const container = flash.parentElement;

            flash.remove();

            if (container && !container.querySelector('[data-flash]')) {
                container.remove();
            }
        }, 300);
    }

};

document.addEventListener('DOMContentLoaded', () => window._Flash.init());