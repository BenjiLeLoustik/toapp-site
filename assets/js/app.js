/** ./assets/js/app.js */

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    window.toggleInputPassword();
    window.initAutoSubmit();

    document.addEventListener('click', event => {
        const track = event.target.closest('.form__switch-track');

        if (track) {
            const input = track.previousElementSibling;

            if (input && !input.disabled) {
                input.checked = !input.checked;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    });
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

window.initProjectShare = function (url) {
    document.querySelectorAll('[data-share-link]').forEach(button => {
        button.addEventListener('click', async () => {
            const type = button.dataset.shareType;
            const shareLink = button.dataset.shareLink;
            const totalShares = document.querySelectorAll('[data-total-shares]');

            await window._Request.POST(url, {
                type: type,
            }).then(response => {
                totalShares.forEach(element => {
                    element.textContent = response.totalShares;
                });
            });

            if (type === 'link' || type === 'discord') {
                await navigator.clipboard.writeText(shareLink);

                const label = button.textContent;
                button.textContent = button.dataset.copySuccess;

                setTimeout(() => {
                    button.textContent = label;
                }, 2000);

                return;
            }

            if (type === 'email') {
                window.location.href = shareLink;
                return;
            }

            window.open(shareLink, '_blank', 'noopener,noreferrer');
        });
    });
};

window.initProjectLike = function (url) {
    document.querySelectorAll('[data-like-toggle]').forEach(button => {

        if (button.dataset.likeReady) {
            return;
        }

        button.dataset.likeReady = 'true';

        button.addEventListener('click', async () => {
            if (button.dataset.likeLoading) {
                return;
            }

            button.dataset.likeLoading = 'true';

            await window._Request.POST(url, {}).then(response => {
                if (!response.success) {
                    return;
                }

                document.querySelectorAll('[data-like-toggle]').forEach(likeButton => {
                    const label = likeButton.querySelector('.button__label');

                    window.changeButtonVariant(likeButton, response.liked ? 'primary' : 'ghost-outline');
                    likeButton.setAttribute('aria-pressed', String(response.liked));

                    if (label) {
                        label.textContent = response.liked
                            ? likeButton.dataset.likeLabelLiked
                            : likeButton.dataset.likeLabel;
                    }
                });

                document.querySelectorAll('[data-total-likes]').forEach(element => {
                    element.textContent = response.totalLikes;
                });

                document.querySelectorAll('[data-likes-label]').forEach(element => {
                    element.textContent = response.likesLabel;
                });
            }).finally(() => {
                delete button.dataset.likeLoading;
            });
        });
    });
};

window.initProjectFavorite = function (url) {
    document.querySelectorAll('[data-favorite-toggle]').forEach(button => {

        if (button.dataset.favoriteReady) {
            return;
        }

        button.dataset.favoriteReady = 'true';

        button.addEventListener('click', async () => {
            if (button.dataset.favoriteLoading) {
                return;
            }

            button.dataset.favoriteLoading = 'true';

            await window._Request.POST(url, {}).then(response => {
                if (!response.success) {
                    return;
                }

                document.querySelectorAll('[data-favorite-toggle]').forEach(favoriteButton => {
                    const label = favoriteButton.querySelector('.button__label');

                    window.changeButtonVariant(favoriteButton, response.favorite ? 'primary' : 'ghost-outline');
                    favoriteButton.setAttribute('aria-pressed', String(response.favorite));

                    if (label) {
                        label.textContent = response.favorite
                            ? favoriteButton.dataset.favoriteLabelSaved
                            : favoriteButton.dataset.favoriteLabel;
                    }
                });

            }).finally(() => {
                delete button.dataset.favoriteLoading;
            });
        });
    });
};

window.toggleInputPassword = function () {
    document.querySelectorAll('[data-password-toggle]').forEach(button => {

        if (button.dataset.passwordToggleReady) {
            return;
        }

        button.dataset.passwordToggleReady = 'true';

        button.addEventListener('click', () => {
            const input = document.getElementById(button.getAttribute('aria-controls'));

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';

            button.setAttribute('aria-pressed', String(isHidden));
            button.setAttribute('aria-label', isHidden
                ? button.dataset.hideLabel
                : button.dataset.showLabel
            );
        });

    });
};

window.initAutoSubmit = function () {
    document.querySelectorAll('form[data-auto-submit]').forEach(form => {

        if (form.dataset.autoSubmitReady) {
            return;
        }

        form.dataset.autoSubmitReady = 'true';

        form.querySelectorAll('[data-sort-sync]').forEach(select => {
            select.addEventListener('change', () => {
                form.querySelectorAll('input[type="radio"][name="sort"]').forEach(radio => {
                    radio.checked = radio.value === select.value;
                });
            });
        });

        form.querySelectorAll('input[type="radio"][name="sort"]').forEach(radio => {
            radio.addEventListener('change', () => {
                form.querySelectorAll('[data-sort-sync]').forEach(select => {
                    select.value = radio.value;
                });
            });
        });

        form.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(field => {
            field.addEventListener('change', () => {
                form.querySelectorAll('[data-sort-sync]').forEach(select => {
                    select.disabled = true;
                });

                form.requestSubmit();
            });
        });
    });
};