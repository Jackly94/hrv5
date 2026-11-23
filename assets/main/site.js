document.querySelectorAll('[data-searchable-select]').forEach((select) => {
    if (!window.TomSelect || select.tomselect) {
        return;
    }

    new window.TomSelect(select, {
        allowEmptyOption: true,
        create: false,
        dropdownClass: 'ts-dropdown searchable-select-dropdown',
        dropdownParent: 'body',
        highlight: true,
        maxOptions: null,
        openOnFocus: true,
        plugins: ['dropdown_input'],
        sortField: [{ field: '$order' }],
    });
});

const updateSelectOptions = (select, options, placeholder) => {
    if (!select) {
        return;
    }

    const normalized = Array.isArray(options) ? options : [];
    select.replaceChildren();

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.append(placeholderOption);

    normalized.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = String(option.id ?? '');
        optionElement.textContent = String(option.name ?? '');
        select.append(optionElement);
    });

    if (!select.tomselect) {
        select.value = '';
        return;
    }

    const tomSelect = select.tomselect;
    tomSelect.clear(true);
    tomSelect.clearOptions();
    normalized.forEach((option) => {
        tomSelect.addOption({
            value: String(option.id ?? ''),
            text: String(option.name ?? ''),
        });
    });
    tomSelect.refreshOptions(false);
};

const loadDependentOptions = async (endpoint, parentValue) => {
    if (!endpoint || !parentValue) {
        return [];
    }

    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('depdrop_parents', parentValue);
    const response = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
        },
    });
    if (!response.ok) {
        return [];
    }

    const data = await response.json();
    return Array.isArray(data.output) ? data.output : [];
};

document.querySelectorAll('[data-address-country]').forEach((countrySelect) => {
    countrySelect.addEventListener('change', async () => {
        const stateSelect = document.querySelector(countrySelect.dataset.stateTarget ?? '');
        const citySelect = document.querySelector(countrySelect.dataset.cityTarget ?? '');
        updateSelectOptions(stateSelect, [], 'Pilih Negeri');
        updateSelectOptions(citySelect, [], 'Pilih Daerah');

        const states = await loadDependentOptions(countrySelect.dataset.endpoint, countrySelect.value);
        updateSelectOptions(stateSelect, states, 'Pilih Negeri');
    });
});

document.querySelectorAll('[data-address-state]').forEach((stateSelect) => {
    stateSelect.addEventListener('change', async () => {
        const citySelect = document.querySelector(stateSelect.dataset.cityTarget ?? '');
        updateSelectOptions(citySelect, [], 'Pilih Daerah');

        const cities = await loadDependentOptions(stateSelect.dataset.endpoint, stateSelect.value);
        updateSelectOptions(citySelect, cities, 'Pilih Daerah');
    });
});

document.querySelectorAll('[data-prof-cert-type]').forEach((typeSelect) => {
    const otherRow = document.querySelector(typeSelect.dataset.otherTarget ?? '');
    const updateOtherTypeVisibility = () => {
        if (!otherRow) {
            return;
        }

        otherRow.hidden = typeSelect.value !== '999';
    };

    typeSelect.addEventListener('change', updateOtherTypeVisibility);
    typeSelect.tomselect?.on('change', updateOtherTypeVisibility);
    updateOtherTypeVisibility();
});

document.querySelectorAll('[data-gcr-input]').forEach((gcrInput) => {
    const form = gcrInput.closest('form');
    const cbthOutput = form?.querySelector('[data-cbth-output]');
    const lupusOutput = form?.querySelector('[data-lupus-output]');
    const updateGcrTotals = () => {
        const gcr = Math.max(0, Number.parseInt(gcrInput.value || '0', 10) || 0);
        const maxTotal = Math.max(0, Number.parseInt(gcrInput.dataset.maxTotal || '0', 10) || 0);
        const maxCbth = Math.max(0, Number.parseInt(gcrInput.dataset.maxCbth || '0', 10) || 0);
        const cbth = Math.max(0, Math.min(maxTotal - gcr, maxCbth));
        const lupus = Math.max(0, maxTotal - gcr - cbth);

        if (cbthOutput instanceof HTMLInputElement) {
            cbthOutput.value = String(cbth);
        }
        if (lupusOutput instanceof HTMLInputElement) {
            lupusOutput.value = String(lupus);
        }
    };

    gcrInput.addEventListener('input', updateGcrTotals);
    updateGcrTotals();
});

document.querySelectorAll('[data-confirm-submit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm-submit') || 'Teruskan?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-tabs]').forEach((tabsRoot) => {
    const tabs = Array.from(tabsRoot.querySelectorAll('[role="tab"]'));
    const panels = Array.from(tabsRoot.querySelectorAll('[role="tabpanel"]'));

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    const selectTab = (selectedTab, focusTab = false) => {
        tabs.forEach((tab) => {
            const isSelected = tab === selectedTab;
            const panelId = tab.getAttribute('data-tab-target');
            const panel = panelId ? panels.find((item) => item.id === panelId) : null;

            tab.classList.toggle('is-active', isSelected);
            tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            tab.tabIndex = isSelected ? 0 : -1;

            if (panel) {
                panel.hidden = !isSelected;
            }
        });

        if (focusTab) {
            selectedTab.focus();
        }
    };

    tabsRoot.classList.add('is-ready');
    selectTab(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0]);

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', (event) => {
            const movement = {
                ArrowLeft: -1,
                ArrowUp: -1,
                ArrowRight: 1,
                ArrowDown: 1,
            }[event.key];

            if (movement) {
                event.preventDefault();
                selectTab(tabs[(index + movement + tabs.length) % tabs.length], true);
                return;
            }

            if (event.key === 'Home') {
                event.preventDefault();
                selectTab(tabs[0], true);
            }

            if (event.key === 'End') {
                event.preventDefault();
                selectTab(tabs[tabs.length - 1], true);
            }
        });
    });
});

const flashMessages = {
    created: {
        title: 'Berjaya disimpan',
        message: 'Maklumat berjaya disimpan.',
        tone: 'success',
        icon: 'check-circle-2',
        autoHide: true,
    },
    updated: {
        title: 'Berjaya dikemaskini',
        message: 'Maklumat berjaya dikemaskini.',
        tone: 'success',
        icon: 'check-circle-2',
        autoHide: true,
    },
    deleted: {
        title: 'Berjaya dipadam',
        message: 'Maklumat berjaya dipadam.',
        tone: 'success',
        icon: 'check-circle-2',
        autoHide: true,
    },
    error: {
        title: 'Ralat',
        message: 'Sila semak maklumat dan cuba semula.',
        tone: 'danger',
        icon: 'circle-alert',
        autoHide: false,
    },
};

const createAppToast = ({ title, message, tone, icon, autoHide = false }) => {
    const region = document.querySelector('[data-app-toast-region]');
    if (!region) {
        return;
    }

    const toast = document.createElement('section');
    toast.className = `app-toast app-toast-${tone}`;
    toast.setAttribute('role', tone === 'danger' ? 'alert' : 'status');

    const iconElement = document.createElement('i');
    iconElement.className = 'app-toast-icon';
    iconElement.dataset.lucide = icon;

    const content = document.createElement('div');
    content.className = 'app-toast-content';

    const titleElement = document.createElement('strong');
    titleElement.textContent = title;

    const messageElement = document.createElement('span');
    messageElement.textContent = message;

    const closeButton = document.createElement('button');
    closeButton.className = 'app-toast-close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Tutup mesej');
    closeButton.innerHTML = '<i data-lucide="x"></i>';
    let hideTimer = null;
    const closeToast = () => {
        if (toast.classList.contains('is-hiding')) {
            return;
        }

        if (hideTimer !== null) {
            window.clearTimeout(hideTimer);
        }

        toast.classList.add('is-hiding');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
        window.setTimeout(() => toast.remove(), 260);
    };
    closeButton.addEventListener('click', closeToast);

    content.append(titleElement, messageElement);
    toast.append(iconElement, content, closeButton);
    region.append(toast);

    if (autoHide) {
        hideTimer = window.setTimeout(closeToast, 5000);
    }
};

const showQueryFlashMessage = () => {
    const params = new URLSearchParams(window.location.search);
    const flashKey = Object.keys(flashMessages).find((key) => params.has(key));
    if (!flashKey) {
        return;
    }

    createAppToast(flashMessages[flashKey]);
};

showQueryFlashMessage();

window.lucide?.createIcons({
    attrs: {
        'aria-hidden': 'true',
    },
});
