/** ./assets/js/pages/_ProjectForm.js */

window._ProjectForm = {

    init() {
        const form = document.querySelector('[data-project-form]');

        if (!form || form.dataset.projectFormReady) {
            return;
        }

        form.dataset.projectFormReady = 'true';

        this._initCover(form);
        this._initTags(form);
        this._initFeatures(form);
        this._initScreenshots(form);

        if (window.lucide) {
            window.lucide.createIcons();
        }
    },

    _initCover(form) {
        const dropzone = form.querySelector('[data-cover-dropzone]');
        const input = form.querySelector('[data-cover-input]');
        const image = form.querySelector('[data-cover-image]');
        const remove = form.querySelector('[data-cover-remove]');

        if (!dropzone || !input || !image) {
            return;
        }

        const original = image.getAttribute('src') || '';

        ['dragenter', 'dragover'].forEach(name => {
            dropzone.addEventListener(name, event => {
                event.preventDefault();
                dropzone.classList.add('project-form__cover--dragging');
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            dropzone.addEventListener(name, () => {
                dropzone.classList.remove('project-form__cover--dragging');
            });
        });

        dropzone.addEventListener('drop', event => {
            event.preventDefault();

            if (event.dataTransfer?.files?.length) {
                input.files = event.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                this._show(image, original, dropzone, 'project-form__cover--filled');
                return;
            }

            this._show(image, URL.createObjectURL(file), dropzone, 'project-form__cover--filled');

            if (remove) {
                remove.checked = false;
            }
        });

        remove?.addEventListener('change', () => {
            this._show(image, remove.checked ? '' : original, dropzone, 'project-form__cover--filled');

            if (remove.checked) {
                input.value = '';
            }
        });
    },

    _initTags(form) {
        const container = form.querySelector('[data-tags]');

        if (!container) {
            return;
        }

        const list = container.querySelector('[data-tags-list]');
        const input = container.querySelector('[data-tags-input]');
        const menu = container.querySelector('[data-tags-menu]');
        const empty = container.querySelector('[data-tags-empty]');
        const template = container.querySelector('[data-tag-template]');
        const options = Array.from(menu.querySelectorAll('[data-tags-option]'));
        let activeIndex = -1;

        const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

        const selected = () => Array.from(list.querySelectorAll('[data-tag]')).map(tag => tag.dataset.tag);

        const visibleOptions = () => options.filter(option => !option.hidden);

        const setActive = index => {
            const visible = visibleOptions();

            options.forEach(option => {
                option.classList.remove('project-form__tags-option--active');
                option.setAttribute('aria-selected', 'false');
            });

            activeIndex = visible.length === 0 ? -1 : (index + visible.length) % visible.length;

            if (activeIndex >= 0) {
                const option = visible[activeIndex];
                option.classList.add('project-form__tags-option--active');
                option.setAttribute('aria-selected', 'true');
                option.scrollIntoView({ block: 'nearest' });
            }
        };

        const open = () => {
            menu.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        const close = () => {
            menu.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        };

        const filter = () => {
            const query = normalize(input.value);
            const taken = selected();

            const ranked = options.map(option => {
                const name = normalize(option.dataset.name);
                const available = !taken.includes(option.dataset.slug);
                const matches = query === '' || name.includes(query);

                option.hidden = !(available && matches);

                return { option, rank: name.startsWith(query) ? 0 : 1 };
            });

            ranked
                .sort((a, b) => a.rank - b.rank || a.option.dataset.name.localeCompare(b.option.dataset.name))
                .forEach(({ option }) => menu.insertBefore(option, empty));

            empty.hidden = visibleOptions().length > 0;

            open();
            setActive(query === '' ? -1 : 0);
        };

        const add = option => {
            if (!option || selected().includes(option.dataset.slug)) {
                return;
            }

            const tag = template.content.firstElementChild.cloneNode(true);

            tag.dataset.tag = option.dataset.slug;
            tag.querySelector('[data-tag-label]').textContent = option.dataset.name;
            tag.querySelector('input[type="hidden"]').value = option.dataset.slug;

            list.insertBefore(tag, input);
            input.value = '';

            if (window.lucide) {
                window.lucide.createIcons();
            }

            filter();
            input.focus();
        };

        input.addEventListener('focus', filter);
        input.addEventListener('input', filter);

        input.addEventListener('keydown', event => {
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    menu.hidden ? filter() : setActive(activeIndex + 1);
                    break;

                case 'ArrowUp':
                    event.preventDefault();
                    setActive(activeIndex - 1);
                    break;

                case 'Enter':
                case 'Tab':
                    if (!menu.hidden && activeIndex >= 0) {
                        event.preventDefault();
                        add(visibleOptions()[activeIndex]);
                    } else if (event.key === 'Enter') {
                        event.preventDefault();
                    }
                    break;

                case 'Escape':
                    close();
                    break;

                case 'Backspace':
                    if (input.value === '') {
                        const tags = list.querySelectorAll('[data-tag]');
                        tags[tags.length - 1]?.remove();
                        filter();
                    }
                    break;
            }
        });

        menu.addEventListener('mousedown', event => {
            const option = event.target.closest('[data-tags-option]');

            if (option) {
                event.preventDefault();
                add(option);
            }
        });

        list.addEventListener('click', event => {
            const button = event.target.closest('[data-tag-remove]');

            if (button) {
                button.closest('[data-tag]')?.remove();
                filter();
            }

            input.focus();
        });

        document.addEventListener('click', event => {
            if (!container.contains(event.target)) {
                close();
            }
        });
    },

    _initFeatures(form) {
        const container = form.querySelector('[data-features]');

        if (!container) {
            return;
        }

        const list = container.querySelector('[data-features-list]');
        const input = container.querySelector('[data-feature-input]');
        const addButton = container.querySelector('[data-feature-add]');
        const template = container.querySelector('[data-feature-template]');

        const add = () => {
            const value = input.value.trim();

            if (value === '') {
                return;
            }

            const row = template.content.firstElementChild.cloneNode(true);
            row.querySelector('input').value = value;
            list.appendChild(row);
            input.value = '';

            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        addButton?.addEventListener('click', add);

        input?.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                add();
            }
        });

        list.addEventListener('click', event => {
            const button = event.target.closest('[data-feature-remove]');

            if (button) {
                button.closest('[data-feature]')?.remove();
            }
        });
    },

    _initScreenshots(form) {
        form.querySelectorAll('[data-shot]').forEach(slot => {
            const input = slot.querySelector('[data-shot-input]');
            const image = slot.querySelector('[data-shot-image]');
            const remove = slot.querySelector('[data-shot-remove]');
            const original = image.getAttribute('src') || '';

            input.addEventListener('change', () => {
                const file = input.files?.[0];

                if (!file || !file.type.startsWith('image/')) {
                    this._show(image, original, slot, 'project-form__shot--filled');
                    return;
                }

                this._show(image, URL.createObjectURL(file), slot, 'project-form__shot--filled');
                slot.classList.remove('project-form__shot--removed');

                if (remove) {
                    remove.checked = false;
                }
            });

            remove?.addEventListener('change', () => {
                slot.classList.toggle('project-form__shot--removed', remove.checked);

                if (remove.checked) {
                    input.value = '';
                    this._show(image, original, slot, 'project-form__shot--filled');
                }
            });
        });
    },

    _show(image, src, container, filledClass) {
        image.src = src;
        image.hidden = src === '';
        container.classList.toggle(filledClass, src !== '');
    },
};