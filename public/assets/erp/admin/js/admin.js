(() => {
    const editSnapshots = new Map();

    const initializeStockDrawers = () => {
        const drawers = Array.from(document.querySelectorAll('.stock-drawer-shell'));
        if (drawers.length === 0) {
            return;
        }

        const closeDrawers = () => {
            drawers.forEach((drawer) => {
                drawer.classList.remove('is-open');
                drawer.setAttribute('aria-hidden', 'true');
            });
            document.body.classList.remove('has-stock-drawer-open');
        };

        document.querySelectorAll('[data-stock-drawer-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const drawerId = button.getAttribute('data-stock-drawer-target');
                const drawer = drawerId ? document.getElementById(drawerId) : null;
                if (!drawer) {
                    return;
                }

                closeDrawers();
                drawer.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
                document.body.classList.add('has-stock-drawer-open');
                drawer.querySelector('.stock-drawer-close')?.focus();
            });
        });

        document.querySelectorAll('[data-stock-drawer-close]').forEach((button) => {
            button.addEventListener('click', closeDrawers);
        });

        document.querySelectorAll('.stock-drawer-tab').forEach((button) => {
            button.addEventListener('click', () => {
                const drawer = button.closest('.stock-drawer-panel');
                const targetId = button.getAttribute('data-stock-tab-target');
                if (!drawer || !targetId) {
                    return;
                }

                drawer.querySelectorAll('.stock-drawer-tab').forEach((tab) => {
                    tab.classList.remove('is-active');
                    tab.setAttribute('aria-selected', 'false');
                });
                drawer.querySelectorAll('.stock-drawer-tab-panel').forEach((panel) => {
                    panel.classList.remove('is-active');
                    panel.hidden = true;
                });

                const targetPanel = document.getElementById(targetId);
                button.classList.add('is-active');
                button.setAttribute('aria-selected', 'true');
                if (targetPanel) {
                    targetPanel.hidden = false;
                    targetPanel.classList.add('is-active');
                    targetPanel.querySelector('input')?.focus();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDrawers();
            }
        });
    };

    const snapshotSection = (section) => {
        const values = [];
        document.querySelectorAll(`[data-order-edit-panel="${section}"]`).forEach((panel) => {
            panel.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)) {
                    values.push(field.checked ? '1' : '0');
                    return;
                }
                values.push(String(field.value ?? ''));
            });
        });
        editSnapshots.set(section, values.join('\u0001'));
    };

    const sectionIsDirty = (section) => {
        const snapshot = editSnapshots.get(section);
        if (snapshot === undefined) {
            return false;
        }
        const values = [];
        document.querySelectorAll(`[data-order-edit-panel="${section}"]`).forEach((panel) => {
            panel.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)) {
                    values.push(field.checked ? '1' : '0');
                    return;
                }
                values.push(String(field.value ?? ''));
            });
        });
        return snapshot !== values.join('\u0001');
    };

    const initializeOrderEditPanels = () => {
        const setSectionState = (section, editing) => {
            document.querySelectorAll(`[data-order-edit-panel="${section}"]`).forEach((panel) => {
                panel.classList.toggle('is-active', editing);
                panel.hidden = !editing;
            });
            document.querySelectorAll(`[data-order-read-panel="${section}"]`).forEach((panel) => {
                panel.classList.toggle('is-hidden', editing);
            });
            document.querySelectorAll(`[data-order-edit-actions="${section}"]`).forEach((actions) => {
                actions.hidden = !editing;
            });
            document.querySelectorAll(`[data-order-edit-toggle="${section}"]`).forEach((toggle) => {
                toggle.hidden = editing;
            });
            document.querySelectorAll('[data-order-remove-col]').forEach((col) => {
                col.hidden = !editing;
            });
            const card = document.getElementById(section === 'items' ? 'orderItemsCard' : 'orderAddressCard');
            if (card) {
                card.classList.toggle('is-editing', editing);
            }
            if (editing) {
                snapshotSection(section);
            } else {
                editSnapshots.delete(section);
            }
        };

        document.querySelectorAll('[data-order-edit-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = button.getAttribute('data-order-edit-toggle');
                if (!section) {
                    return;
                }
                setSectionState(section, true);
            });
        });

        document.querySelectorAll('[data-order-edit-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = button.getAttribute('data-order-edit-cancel');
                if (!section) {
                    return;
                }
                if (sectionIsDirty(section)) {
                    const message = button.getAttribute('data-confirm-cancel')
                        || 'Descartar as alterações feitas nesta seção?';
                    if (!window.confirm(message)) {
                        return;
                    }
                }
                setSectionState(section, false);
                window.location.reload();
            });
        });
    };

    const clampQtyInput = (input) => {
        const min = parseInt(input.getAttribute('min') || '1', 10) || 1;
        const maxAttr = input.getAttribute('max');
        const max = maxAttr ? parseInt(maxAttr, 10) : 99999;
        const next = Math.max(min, Math.min(max, parseInt(input.value || String(min), 10) || min));
        input.value = String(next);
        return next;
    };

    const initializeQtySteppers = () => {
        document.querySelectorAll('[data-qty-stepper]').forEach((root) => {
            const input = root.querySelector('[data-qty-stepper-input]');
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const form = root.closest('form');
            const autoSubmit = root.hasAttribute('data-qty-auto-submit')
                || (form && form.hasAttribute('data-qty-auto-submit'));
            let debounceId = 0;
            let lastSubmitted = input.value;

            const scheduleSubmit = () => {
                if (!autoSubmit || !form) {
                    return;
                }
                window.clearTimeout(debounceId);
                debounceId = window.setTimeout(() => {
                    clampQtyInput(input);
                    if (input.value === lastSubmitted) {
                        return;
                    }
                    lastSubmitted = input.value;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, 450);
            };

            root.querySelectorAll('[data-qty-stepper-delta]').forEach((button) => {
                button.addEventListener('click', () => {
                    const delta = parseInt(button.getAttribute('data-qty-stepper-delta') || '0', 10) || 0;
                    const min = parseInt(input.getAttribute('min') || '1', 10) || 1;
                    const maxAttr = input.getAttribute('max');
                    const max = maxAttr ? parseInt(maxAttr, 10) : 99999;
                    const current = parseInt(input.value || String(min), 10) || min;
                    const next = Math.max(min, Math.min(max, current + delta));
                    if (next === current) {
                        return;
                    }
                    input.value = String(next);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    scheduleSubmit();
                });
            });

            input.addEventListener('change', () => {
                clampQtyInput(input);
                scheduleSubmit();
            });

            input.addEventListener('input', scheduleSubmit);
        });
    };

    const initializeConfirmations = () => {
        document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === '1') {
                    form.dataset.confirmed = '0';
                    return;
                }
                const message = form.getAttribute('data-confirm-submit');
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-confirm-click]').forEach((element) => {
            element.addEventListener('click', (event) => {
                const message = element.getAttribute('data-confirm-click');
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        });
    };

    const closeAllActionMenus = () => {
        document.querySelectorAll('[data-actions-menu]').forEach((menu) => {
            const panel = menu.querySelector('.actions-menu__panel');
            const trigger = menu.querySelector('.actions-menu__trigger');
            if (panel) {
                panel.hidden = true;
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const initializeActionMenus = () => {
        document.querySelectorAll('[data-actions-menu]').forEach((menu) => {
            const trigger = menu.querySelector('.actions-menu__trigger');
            const panel = menu.querySelector('.actions-menu__panel');
            if (!trigger || !panel) {
                return;
            }

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = panel.hidden;
                closeAllActionMenus();
                panel.hidden = !willOpen;
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            panel.querySelectorAll('.actions-menu__item').forEach((item) => {
                item.addEventListener('click', () => {
                    closeAllActionMenus();
                });
            });
        });

        document.addEventListener('click', closeAllActionMenus);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllActionMenus();
            }
        });
    };

    const boot = () => {
        initializeStockDrawers();
        initializeOrderEditPanels();
        initializeQtySteppers();
        initializeConfirmations();
        initializeActionMenus();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
        return;
    }

    boot();
})();
