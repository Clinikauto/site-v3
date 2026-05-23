(function () {
    function mountBackTopButton() {
        if (document.getElementById('site-back-top')) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.id = 'site-back-top';
        button.className = 'site-back-top-floating';
        button.setAttribute('aria-label', 'Retour en haut de la page');
        button.textContent = '↑';

        button.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        var syncVisibility = function () {
            if (window.scrollY > 420) {
                button.classList.add('is-visible');
            } else {
                button.classList.remove('is-visible');
            }
        };

        window.addEventListener('scroll', syncVisibility, { passive: true });
        window.addEventListener('resize', syncVisibility);
        document.body.appendChild(button);
        syncVisibility();
    }

    function sendEvent(eventName, params) {
        var payload = params || {};

        if (typeof window.gtag === 'function') {
            window.gtag('event', eventName, payload);
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: eventName,
            event_params: payload
        });
    }

    function normalizePath(path) {
        return (path || '').replace(/^\/+/, '');
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link) {
            return;
        }

        var href = (link.getAttribute('href') || '').trim();
        if (!href) {
            return;
        }

        var pagePath = normalizePath(window.location.pathname);
        var label = (link.textContent || '').trim().slice(0, 80);

        if (href.indexOf('tel:') === 0) {
            sendEvent('click_phone', {
                page_path: pagePath,
                link_text: label,
                destination: href
            });
            return;
        }

        if (href.indexOf('devis/devis.php') !== -1) {
            sendEvent('click_devis', {
                page_path: pagePath,
                link_text: label,
                destination: href
            });
            return;
        }

        if (href.indexOf('rdv/rdv.php') !== -1) {
            sendEvent('click_rdv', {
                page_path: pagePath,
                link_text: label,
                destination: href
            });
            return;
        }

        if (href.indexOf('contact/contact.php') !== -1) {
            sendEvent('click_contact', {
                page_path: pagePath,
                link_text: label,
                destination: href
            });
            return;
        }

        if (href.indexOf('faq.php') !== -1) {
            sendEvent('click_faq', {
                page_path: pagePath,
                link_text: label,
                destination: href
            });
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var pagePath = normalizePath(window.location.pathname);
        var formId = form.getAttribute('id') || '';

        if (formId === 'devis-form') {
            sendEvent('submit_devis', {
                page_path: pagePath,
                form_id: formId
            });
            return;
        }

        if (pagePath.indexOf('rdv/rdv.php') !== -1) {
            sendEvent('submit_rdv', {
                page_path: pagePath,
                form_id: formId || 'rdv-form'
            });
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountBackTopButton);
    } else {
        mountBackTopButton();
    }
})();
