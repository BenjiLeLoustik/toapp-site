/** ./assets/js/components/_Modal.js */

window._Modal = {

    init: function () {
        this.triggerOpenModal();
        this.triggerCloseModal();
    },

    triggerOpenModal: function () {
        const btnsOpenModal = document.querySelectorAll('[data-modal-open]');

        if (!btnsOpenModal.length) {
            return;
        }

        btnsOpenModal.forEach(btnOpenModal => {
            btnOpenModal.addEventListener('click', (event) => {
                const modalName = event.currentTarget.dataset.modalOpen;
                const modal = this._checkIsValidModal(modalName);

                if (!modal) {
                    return;
                }

                this._closeAllModals();
                this._openModal(modal);
            });
        });
    },

    triggerCloseModal: function() {
        const btnsCloseModal = document.querySelectorAll('[data-modal-close]');

        if (!btnsCloseModal.length) {
            return;
        }

        btnsCloseModal.forEach(btnCloseModal => {
             btnCloseModal.addEventListener('click', (event) => {
                 const modalName = event.currentTarget.closest('.modal').dataset.modalName;
                 const modal = this._checkIsValidModal(modalName);

                 if (!modal) {
                     return;
                 }

                 this._closeOnlyOneModal(modal);
             });
        });
    },

    _checkIsValidModal: function (modalName) {
        let modals = document.querySelectorAll(`.modal[data-modal-name="${modalName}"]`);
        if (modals.length !== 1) {
            return null;
        }

        return modals[0];
    },

    _closeAllModals: function () {
        let modals = document.querySelectorAll('.modal[data-modal-name]');
        if (!modals.length) {
            return;
        }

        modals.forEach(modal => {
            modal.classList.remove('modal--active');
        })
    },

    _closeOnlyOneModal: function (modal) {
        modal.classList.remove('modal--active');
    },

    _openModal: function (modal) {
        if (modal.classList.contains('modal--active')) {
            return;
        }

        modal.classList.add('modal--active');
    }

};

document.addEventListener('DOMContentLoaded', () => window._Modal.init());