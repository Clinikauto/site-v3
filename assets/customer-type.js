(function () {
    var STORAGE_KEY = 'clinikauto_customer_type';

    function normalizeType(value) {
        return value === 'professional' ? 'professional' : 'individual';
    }

    function readStoredType() {
        try {
            return normalizeType(window.localStorage.getItem(STORAGE_KEY) || 'individual');
        } catch (e) {
            return 'individual';
        }
    }

    function writeStoredType(value) {
        try {
            window.localStorage.setItem(STORAGE_KEY, normalizeType(value));
        } catch (e) {
            // localStorage may be unavailable in privacy mode.
        }
    }

    function applyType(container, type) {
        var resolvedType = normalizeType(type);
        var isProfessional = resolvedType === 'professional';

        container.querySelectorAll('[data-customer-type-input]').forEach(function (input) {
            input.value = resolvedType;
        });

        container.querySelectorAll('[data-customer-type-checkbox]').forEach(function (checkbox) {
            checkbox.checked = isProfessional;
        });

        container.querySelectorAll('[data-type-label-target]').forEach(function (label) {
            var individualLabel = label.getAttribute('data-individual-label') || label.textContent;
            var professionalLabel = label.getAttribute('data-professional-label') || individualLabel;
            label.textContent = isProfessional ? professionalLabel : individualLabel;
        });

        container.querySelectorAll('[data-type-placeholder-target]').forEach(function (input) {
            var individualPlaceholder = input.getAttribute('data-individual-placeholder') || '';
            var professionalPlaceholder = input.getAttribute('data-professional-placeholder') || individualPlaceholder;
            input.setAttribute('placeholder', isProfessional ? professionalPlaceholder : individualPlaceholder);
        });
    }

    function initContainer(container) {
        var initialType = 'individual';
        var hidden = container.querySelector('[data-customer-type-input]');
        if (hidden && hidden.value) {
            initialType = normalizeType(hidden.value);
        } else {
            initialType = readStoredType();
        }

        applyType(container, initialType);

        container.querySelectorAll('[data-customer-type-checkbox]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var nextType = checkbox.checked ? 'professional' : 'individual';
                applyType(container, nextType);
                writeStoredType(nextType);
            });
        });
    }

    function boot() {
        document.querySelectorAll('[data-customer-type-context]').forEach(initContainer);
        bootAdminConsoleQuickFallback();
    }

    function bootAdminConsoleQuickFallback() {
        if (window.__adminConsoleBootstrap) {
            return;
        }

        var toggleButtons = Array.prototype.slice.call(document.querySelectorAll('.admin-console-toggles .admin-section-toggle'));
        if (!toggleButtons.length) {
            return;
        }

        var openAllButton = document.getElementById('admin-quick-open-all');
        var closeAllButton = document.getElementById('admin-quick-close-all');
        var openCountNode = document.getElementById('admin-open-console-count');
        var autoRefreshOnButton = document.getElementById('admin-quick-autoref-on');
        var autoRefreshOffButton = document.getElementById('admin-quick-autoref-off');
        var autoRefreshStateNode = document.getElementById('admin-quick-autoref-state');
        var autoRefreshToggle = document.getElementById('customer-auto-refresh-toggle');
        var customerSearchForm = document.getElementById('customer-search-form');
        var devisCategoriesToggleButton = document.getElementById('admin-quick-toggle-devis-categories');
        var devisCategoriesZone = document.getElementById('section-devis-categories-zone');

        function getDevisCategoriesZoneElements() {
            var elements = [];

            if (devisCategoriesZone && devisCategoriesZone.isConnected) {
                elements.push(devisCategoriesZone);
            }

            var configActionInput = document.querySelector('input[name="action"][value="devis_config_save"]');
            var configForm = configActionInput ? configActionInput.closest('form') : null;
            if (!configForm) {
                configForm = document.getElementById('devis-config-form');
            }
            if (configForm && elements.indexOf(configForm) === -1) {
                elements.push(configForm);
            }

            var addCategoryActionInput = document.querySelector('input[name="action"][value="devis_category_add"]');
            var addCategoryForm = addCategoryActionInput ? addCategoryActionInput.closest('form') : null;
            if (addCategoryForm && elements.indexOf(addCategoryForm) === -1) {
                elements.push(addCategoryForm);
            }

            return elements.filter(function (element) {
                return !!element;
            });
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

        function isVisible(section) {
            if (!section) {
                return false;
            }
            return window.getComputedStyle(section).display !== 'none';
        }

        function setVisible(section, visible) {
            if (!section) {
                return;
            }
            section.style.display = visible ? '' : 'none';
            if (visible) {
                var parent = section.closest('.admin-reminder-card, .admin-editor-card, .admin-list-card, .admin-customers-card, .admin-dashboard-grid, .admin-login-card, .admin-analytics-card');
                if (parent) {
                    parent.style.display = '';
                }
            }
        }

        function baseLabel(text) {
            return String(text || '')
                .replace(/^Masquer\s+/i, '')
                .replace(/^Afficher\s+/i, '')
                .replace(/^\d+\.\s*/, '')
                .trim();
        }

        function applyConsoleButtonStateStyles(button, visible) {
            if (!button) {
                return;
            }
            if (visible) {
                button.style.background = '#dcfce7';
                button.style.borderColor = '#22c55e';
                button.style.color = '#14532d';
            } else {
                button.style.background = '#fee2e2';
                button.style.borderColor = '#ef4444';
                button.style.color = '#7f1d1d';
            }
        }

        function refreshUi() {
            var openCount = 0;
            toggleButtons.forEach(function (button) {
                var target = button.getAttribute('data-target-section') || '';
                var section = resolveSectionElement(target);
                var visible = isVisible(section);
                if (!button.dataset.baseLabel) {
                    button.dataset.baseLabel = baseLabel(button.textContent) || 'Bloc';
                }
                if (visible) {
                    openCount += 1;
                }
                button.textContent = button.dataset.baseLabel;
                button.classList.toggle('is-visible', visible);
                button.classList.toggle('is-hidden', !visible);
                applyConsoleButtonStateStyles(button, visible);
                button.disabled = false;
                button.dataset.missingSection = section ? '0' : '1';
                if (!section) {
                    button.title = 'Section non chargee sur cette vue, clic pour ouvrir.';
                } else {
                    button.removeAttribute('title');
                }
            });

            if (openCountNode) {
                if (devisCategoriesToggleButton && isDevisCategoriesZoneVisible()) {
                    openCount += 1;
                }
                openCountNode.textContent = String(openCount);
            }

            refreshDevisCategoriesZoneButton();
        }

        function isDevisCategoriesZoneVisible() {
            var elements = getDevisCategoriesZoneElements();
            if (!elements.length) {
                return true;
            }
            return elements.some(function (element) {
                return window.getComputedStyle(element).display !== 'none';
            });
        }

        function refreshDevisCategoriesZoneButton() {
            if (!devisCategoriesToggleButton) {
                return;
            }
            var visible = isDevisCategoriesZoneVisible();
            devisCategoriesToggleButton.textContent = 'Zone categories devis';
            devisCategoriesToggleButton.classList.toggle('is-visible', visible);
            devisCategoriesToggleButton.classList.toggle('is-hidden', !visible);
            applyConsoleButtonStateStyles(devisCategoriesToggleButton, visible);
        }

        function forceConsoleButtonsState(visible) {
            toggleButtons.forEach(function (button) {
                if (!button.dataset.baseLabel) {
                    button.dataset.baseLabel = baseLabel(button.textContent) || 'Bloc';
                }
                button.textContent = button.dataset.baseLabel;
                button.classList.toggle('is-visible', visible);
                button.classList.toggle('is-hidden', !visible);
                applyConsoleButtonStateStyles(button, visible);
            });

            if (devisCategoriesToggleButton) {
                devisCategoriesToggleButton.textContent = 'Zone categories devis';
                devisCategoriesToggleButton.classList.toggle('is-visible', visible);
                devisCategoriesToggleButton.classList.toggle('is-hidden', !visible);
                applyConsoleButtonStateStyles(devisCategoriesToggleButton, visible);
            }

            if (openCountNode) {
                openCountNode.textContent = visible ? String(toggleButtons.length + (devisCategoriesToggleButton ? 1 : 0)) : '0';
            }
        }

        function setDevisCategoriesZoneVisible(visible) {
            var elements = getDevisCategoriesZoneElements();
            if (!elements.length) {
                return;
            }
            elements.forEach(function (element) {
                element.style.display = visible ? '' : 'none';
            });
            try {
                window.sessionStorage.setItem('admin-devis-categories-zone-visible', visible ? '1' : '0');
            } catch (e) {
                // Ignore storage write errors.
            }
            refreshDevisCategoriesZoneButton();
        }

        function restoreDevisCategoriesZoneVisible() {
            var elements = getDevisCategoriesZoneElements();
            if (!elements.length) {
                refreshDevisCategoriesZoneButton();
                return;
            }
            var visible = true;
            try {
                visible = window.sessionStorage.getItem('admin-devis-categories-zone-visible') !== '0';
            } catch (e) {
                visible = true;
            }
            elements.forEach(function (element) {
                element.style.display = visible ? '' : 'none';
            });
            refreshDevisCategoriesZoneButton();
        }

        function refreshAutoRefreshUi(enabled) {
            if (autoRefreshStateNode) {
                autoRefreshStateNode.textContent = enabled ? 'ON' : 'OFF';
                autoRefreshStateNode.style.color = enabled ? '#16a34a' : '#ef4444';
            }
            if (autoRefreshOnButton) {
                autoRefreshOnButton.classList.toggle('is-active', enabled);
            }
            if (autoRefreshOffButton) {
                autoRefreshOffButton.classList.toggle('is-active', !enabled);
            }
            if (autoRefreshToggle) {
                autoRefreshToggle.checked = enabled;
            }
        }

        function submitAutoRefresh(enabled) {
            refreshAutoRefreshUi(enabled);
            if (!customerSearchForm) {
                return;
            }
            var hiddenField = customerSearchForm.querySelector('input[type="hidden"][name="customer_auto_refresh"]');
            if (!hiddenField) {
                hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'customer_auto_refresh';
                customerSearchForm.appendChild(hiddenField);
            }
            hiddenField.value = enabled ? '1' : '0';
            customerSearchForm.submit();
        }

        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-target-section') || '';
                var section = resolveSectionElement(target);
                if (!section) {
                    if (target !== '') {
                        var url = new URL(window.location.href);
                        url.searchParams.set('admin_section', target);
                        url.hash = target;
                        window.location.href = url.toString();
                    }
                    return;
                }
                var nextVisible = !isVisible(section);
                setVisible(section, nextVisible);
                refreshUi();
                if (nextVisible) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        if (openAllButton) {
            openAllButton.addEventListener('click', function () {
                toggleButtons.forEach(function (button) {
                    var section = resolveSectionElement(button.getAttribute('data-target-section') || '');
                    setVisible(section, true);
                });
                setDevisCategoriesZoneVisible(true);
                refreshUi();
                forceConsoleButtonsState(true);
            });
        }

        if (closeAllButton) {
            closeAllButton.addEventListener('click', function () {
                toggleButtons.forEach(function (button) {
                    var section = resolveSectionElement(button.getAttribute('data-target-section') || '');
                    setVisible(section, false);
                });
                setDevisCategoriesZoneVisible(false);
                refreshUi();
                forceConsoleButtonsState(false);
            });
        }

        if (autoRefreshOnButton) {
            autoRefreshOnButton.addEventListener('click', function () {
                submitAutoRefresh(true);
            });
        }

        if (autoRefreshOffButton) {
            autoRefreshOffButton.addEventListener('click', function () {
                submitAutoRefresh(false);
            });
        }

        if (devisCategoriesToggleButton) {
            devisCategoriesToggleButton.addEventListener('click', function () {
                setDevisCategoriesZoneVisible(!isDevisCategoriesZoneVisible());
            });
        }

        restoreDevisCategoriesZoneVisible();
        refreshUi();
        refreshAutoRefreshUi(!!(autoRefreshToggle && autoRefreshToggle.checked));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
