(function () {
    function normalizePostalCode(value) {
        return String(value || '').replace(/\D+/g, '').trim();
    }

    function fillDatalist(datalist, cities) {
        if (!datalist) {
            return;
        }

        datalist.innerHTML = '';
        cities.forEach(function (city) {
            var option = document.createElement('option');
            option.value = city;
            datalist.appendChild(option);
        });
    }

    function initPostalCityGroup(group) {
        var postalInput = group.querySelector('[data-postal-code-input]');
        var cityInput = group.querySelector('[data-city-input]');
        var datalist = group.querySelector('datalist');
        var status = group.querySelector('[data-postal-city-status]');
        var endpoint = group.getAttribute('data-postal-endpoint') || '/clinikauto/postal_lookup.php';
        var timer = null;
        var lastPostalCode = '';

        if (!postalInput || !cityInput || !datalist) {
            return;
        }

        function setStatus(message) {
            if (status) {
                status.textContent = message || '';
            }
        }

        function applyCities(postalCode, cities) {
            fillDatalist(datalist, cities);

            if (cities.length === 1 && cityInput.value.trim() === '') {
                cityInput.value = cities[0];
            }

            if (postalCode.length < 5) {
                setStatus('');
                return;
            }

            if (cities.length === 0) {
                setStatus('Aucune ville proposee. Saisie libre possible.');
                return;
            }

            if (cities.length === 1) {
                setStatus('Ville proposee automatiquement.');
                return;
            }

            setStatus(cities.length + ' villes disponibles.');
        }

        function loadCities() {
            var postalCode = normalizePostalCode(postalInput.value);
            postalInput.value = postalCode;

            if (postalCode.length < 4) {
                lastPostalCode = '';
                applyCities(postalCode, []);
                return;
            }

            if (postalCode === lastPostalCode) {
                return;
            }

            lastPostalCode = postalCode;
            setStatus('Recherche des villes...');

            fetch(endpoint + '?postal_code=' + encodeURIComponent(postalCode), {
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('lookup_failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    applyCities(postalCode, Array.isArray(payload.cities) ? payload.cities : []);
                })
                .catch(function () {
                    fillDatalist(datalist, []);
                    setStatus('Recherche indisponible. Saisie libre possible.');
                });
        }

        postalInput.addEventListener('input', function () {
            if (timer) {
                window.clearTimeout(timer);
            }

            timer = window.setTimeout(loadCities, 180);
        });

        postalInput.addEventListener('change', loadCities);
        postalInput.addEventListener('blur', loadCities);

        if (normalizePostalCode(postalInput.value).length >= 4) {
            loadCities();
        }
    }

    function boot() {
        document.querySelectorAll('[data-postal-city-group]').forEach(initPostalCityGroup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();