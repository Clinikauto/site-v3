(function () {
    'use strict';

    function normalizeLabel(text) {
        return String(text || '')
            .replace(/^Masquer\s+/i, '')
            .replace(/^Afficher\s+/i, '')
            .replace(/^\d+\.\s*/, '')
            .trim();
    }

    function resolveSectionElement(sectionId) {
        if (!sectionId) {
            return null;
        }
        if (sectionId === 'section-vehicles') {
            return document.getElementById('section-vehicles-block') || document.getElementById('section-vehicles');
        }
        if (sectionId === 'section-parts') {
            return document.getElementById('section-parts-block') || document.getElementById('section-parts');
        }
        return document.getElementById(sectionId);
    }

    function bindQuickConsoleFallback() {
        if (window.__adminConsoleBootstrap) {
            return;
        }

        const toggleButtons = Array.from(document.querySelectorAll('.admin-console-toggles .admin-section-toggle'));
        if (!toggleButtons.length) {
            return;
        }

        const quickOpenAllButton = document.getElementById('admin-quick-open-all');
        const quickCloseAllButton = document.getElementById('admin-quick-close-all');
        const quickOpenConsoleCount = document.getElementById('admin-open-console-count');
        const quickAutoRefreshOnButton = document.getElementById('admin-quick-autoref-on');
        const quickAutoRefreshOffButton = document.getElementById('admin-quick-autoref-off');
        const quickAutoRefreshState = document.getElementById('admin-quick-autoref-state');
        const autoRefreshToggle = document.getElementById('customer-auto-refresh-toggle');
        const customerSearchForm = document.getElementById('customer-search-form');

        function isVisible(element) {
            if (!element) {
                return false;
            }
            return window.getComputedStyle(element).display !== 'none';
        }

        function setVisible(element, visible) {
            if (!element) {
                return;
            }
            element.style.display = visible ? '' : 'none';
            if (visible) {
                const parent = element.closest('.admin-reminder-card, .admin-editor-card, .admin-list-card, .admin-customers-card, .admin-dashboard-grid, .admin-login-card, .admin-analytics-card');
                if (parent) {
                    parent.style.display = '';
                }
            }
        }

        function updateCount() {
            const openCount = toggleButtons.reduce(function (count, button) {
                const target = button.getAttribute('data-target-section') || '';
                const section = resolveSectionElement(target);
                return count + (isVisible(section) ? 1 : 0);
            }, 0);

            if (quickOpenConsoleCount) {
                quickOpenConsoleCount.textContent = String(openCount);
            }
        }

        function updateToggleLabels() {
            toggleButtons.forEach(function (button) {
                const target = button.getAttribute('data-target-section') || '';
                const section = resolveSectionElement(target);
                if (!button.dataset.baseLabel) {
                    button.dataset.baseLabel = normalizeLabel(button.textContent) || 'Bloc';
                }
                const visible = isVisible(section);
                button.textContent = (visible ? 'Masquer ' : 'Afficher ') + button.dataset.baseLabel;
                button.classList.toggle('is-visible', visible);
                button.classList.toggle('is-hidden', !visible);
                button.disabled = !section;
                button.title = section ? '' : 'Section indisponible sur cette vue';
            });
            updateCount();
        }

        function setAllSections(visible) {
            toggleButtons.forEach(function (button) {
                const target = button.getAttribute('data-target-section') || '';
                const section = resolveSectionElement(target);
                setVisible(section, visible);
            });
            updateToggleLabels();
        }

        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.getAttribute('data-target-section') || '';
                const section = resolveSectionElement(target);
                if (!section) {
                    return;
                }
                const nextVisible = !isVisible(section);
                setVisible(section, nextVisible);
                updateToggleLabels();
                if (nextVisible) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        if (quickOpenAllButton) {
            quickOpenAllButton.addEventListener('click', function () {
                setAllSections(true);
            });
        }

        if (quickCloseAllButton) {
            quickCloseAllButton.addEventListener('click', function () {
                setAllSections(false);
            });
        }

        function updateAutoRefreshUi(enabled) {
            if (quickAutoRefreshState) {
                quickAutoRefreshState.textContent = enabled ? 'ON' : 'OFF';
                quickAutoRefreshState.style.color = enabled ? '#16a34a' : '#ef4444';
            }
            if (quickAutoRefreshOnButton) {
                quickAutoRefreshOnButton.classList.toggle('is-active', enabled);
            }
            if (quickAutoRefreshOffButton) {
                quickAutoRefreshOffButton.classList.toggle('is-active', !enabled);
            }
            if (autoRefreshToggle) {
                autoRefreshToggle.checked = enabled;
            }
        }

        function submitAutoRefresh(enabled) {
            updateAutoRefreshUi(enabled);
            if (customerSearchForm) {
                let hidden = customerSearchForm.querySelector('input[type="hidden"][name="customer_auto_refresh"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'customer_auto_refresh';
                    customerSearchForm.appendChild(hidden);
                }
                hidden.value = enabled ? '1' : '0';
                customerSearchForm.submit();
            }
        }

        if (quickAutoRefreshOnButton) {
            quickAutoRefreshOnButton.addEventListener('click', function () {
                submitAutoRefresh(true);
            });
        }

        if (quickAutoRefreshOffButton) {
            quickAutoRefreshOffButton.addEventListener('click', function () {
                submitAutoRefresh(false);
            });
        }

        updateToggleLabels();
        updateAutoRefreshUi(!!(autoRefreshToggle && autoRefreshToggle.checked));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindQuickConsoleFallback);
    } else {
        bindQuickConsoleFallback();
    }
})();
