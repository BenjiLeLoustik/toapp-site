/** ./assets/js/pages/_Profile.js */

window._Profile = {

    init(url) {
        const button = document.querySelector('[data-follow-toggle]');

        if (!button || button.dataset.followReady) {
            return;
        }

        button.dataset.followReady = 'true';
        button.addEventListener('click', () => this._toggle(button, url));
    },

    _toggle(button, url) {
        if (button.dataset.followLoading) {
            return;
        }

        button.dataset.followLoading = 'true';

        window._Request.POST(url, {}).then(response => {
            if (!response.success) {
                return;
            }

            const label = button.querySelector('.button__label');

            window.changeButtonVariant(button, response.following ? 'ghost-outline' : 'primary');
            button.setAttribute('aria-pressed', String(response.following));

            if (label) {
                label.textContent = response.following
                    ? button.dataset.followLabelFollowing
                    : button.dataset.followLabel;
            }

            document.querySelectorAll('[data-followers-count]').forEach(element => {
                element.textContent = response.followers;
            });
        }).finally(() => {
            delete button.dataset.followLoading;
        });
    },
};