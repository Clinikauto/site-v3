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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
