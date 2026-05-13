<?php
require_once dirname(__DIR__) . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('catalogue_vehicle');

$items = catalog_all_items('vehicle');
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selected = $selectedId > 0 ? catalog_find_item($selectedId, 'vehicle') : null;

if (!$selected && !empty($items)) {
    $selected = $items[0];
}

$vehicleListItems = [];
foreach (array_slice($items, 0, 12) as $index => $item) {
    $vehicleListItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => 'https://www.clinikauto.fr/catalogue/occasion.php?id=' . (int) ($item['id'] ?? 0),
        'item' => [
            '@type' => 'Car',
            'name' => (string) ($item['title'] ?? ''),
            'description' => (string) (($item['subtitle'] ?? '') ?: ($item['description'] ?? '')),
            'image' => catalog_primary_image($item),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => (string) ((float) ($item['price'] ?? 0)),
                'availability' => (($item['status'] ?? '') === 'reserved')
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock',
                'url' => 'https://www.clinikauto.fr/catalogue/occasion.php?id=' . (int) ($item['id'] ?? 0)
            ]
        ]
    ];
}

$occasionStructuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => 'https://www.clinikauto.fr/catalogue/occasion.php#webpage',
            'url' => 'https://www.clinikauto.fr/catalogue/occasion.php',
            'name' => 'Voitures d\'Occasion à Scionzier | Clinik Auto',
            'description' => 'Véhicules d\'occasion contrôlés et garantis chez Clinik Auto à Scionzier.',
            'about' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ]
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://www.clinikauto.fr/catalogue/occasion.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://www.clinikauto.fr/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Catalogue', 'item' => 'https://www.clinikauto.fr/catalogue/catalogue.php'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Véhicules d\'occasion', 'item' => 'https://www.clinikauto.fr/catalogue/occasion.php']
            ]
        ],
        [
            '@type' => 'ItemList',
            'name' => 'Véhicules d\'occasion disponibles',
            'numberOfItems' => count($vehicleListItems),
            'itemListElement' => $vehicleListItems
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voitures Occasion Scionzier Cluses (74) | VO Contrôlées & Garanties | Clinik Auto</title>
    <meta name="description" content="Achetez une voiture d'occasion contrôlée chez Clinik Auto, garage à Scionzier (74950) proche de Cluses, Bonneville, Sallanches. Réservation de visite en ligne – 06 20 18 56 27.">
    <meta name="keywords" content="voiture occasion Scionzier, vente VO 74, achat véhicule occasion Cluses, voiture garantie Haute-Savoie, occasion contrôlée 74950, VO Bonneville, achat auto Sallanches">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/catalogue/occasion.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Voitures d'Occasion à Scionzier (74) | Clinik Auto">
    <meta property="og:description" content="Véhicules d'occasion contrôlés et garantis chez Clinik Auto à Scionzier (74950). Consultez nos annonces et réservez votre visite.">
    <meta property="og:url" content="https://www.clinikauto.fr/catalogue/occasion.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Voitures d'Occasion à Scionzier (74) | Clinik Auto">
    <meta name="twitter:description" content="Véhicules contrôlés et garantis chez Clinik Auto, Scionzier (74950). Réservez votre visite en ligne.">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
    <script type="application/ld+json">
        <?php echo json_encode($occasionStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
</head>
<body class="public-page">
    <header>
        <div class="site-brand">
            <a class="site-brand-link" href="../index.html" aria-label="Clinik Auto accueil">
                <img class="site-logo" src="../assets/logo.png" alt="Logo Clinik Auto">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="../contact/contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="inventory-page">
        <div class="inventory-page-head">
            <div>
                <span class="hero-badge">Occasion</span>
                <h1>Nos véhicules d'occasion disponibles</h1>
                <p>Cliquez sur une ligne pour ouvrir la fiche détaillée du véhicule et réserver une visite.</p>
            </div>
            <a class="cta-link cta-link-small" href="pieces.php">Voir les pièces d'occasion →</a>
        </div>

        <div class="inventory-layout">
            <section class="inventory-panel">
                <h3>Liste des annonces</h3>
                <div class="inventory-list">
                    <?php foreach ($items as $item): ?>
                        <a class="inventory-row <?php echo ($selected && (int) $selected['id'] === (int) $item['id']) ? 'is-active' : ''; ?> <?php echo (($item['status'] ?? '') === 'reserved') ? 'is-unavailable' : ''; ?> <?php echo !empty($item['transaction_in_progress']) ? 'is-transaction' : ''; ?>" href="occasion.php?id=<?php echo (int) $item['id']; ?>">
                            <img class="inventory-thumb" src="<?php echo catalog_escape(catalog_primary_image($item)); ?>" alt="<?php echo catalog_escape($item['title']); ?>">
                            <span class="inventory-row-text">
                                <strong><?php echo catalog_escape($item['title']); ?></strong>
                                <small><?php echo catalog_escape($item['subtitle']); ?></small>
                            </span>
                            <span class="inventory-side-meta">
                                <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($item['price'])); ?> €</span>
                                <span class="status-pill <?php echo ($item['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($item)); ?></span>
                                <?php if (($item['status'] ?? '') !== 'reserved' && empty($item['transaction_in_progress'])): ?>
                                    <label class="part-selector-line" onclick="event.preventDefault(); event.stopPropagation();">
                                        <input
                                            type="checkbox"
                                            class="vehicle-selector"
                                            value="<?php echo (int) $item['id']; ?>"
                                            data-vehicle-id="<?php echo (int) $item['id']; ?>"
                                            data-vehicle-title="<?php echo catalog_escape($item['title']); ?>"
                                            data-vehicle-price="<?php echo catalog_escape((string) ($item['price'] ?? '')); ?>"
                                        >
                                        Sélectionner
                                    </label>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="inventory-detail-panel">
                <?php if ($selected): ?>
                    <div class="detail-heading">
                        <div>
                            <span class="detail-category"><?php echo catalog_escape(catalog_type_label($selected['type'])); ?></span>
                            <h3><?php echo catalog_escape($selected['title']); ?></h3>
                            <p class="detail-subtitle"><?php echo catalog_escape($selected['subtitle']); ?></p>
                        </div>
                        <div class="detail-price-box">
                            <strong><?php echo catalog_escape(catalog_format_price($selected['price'])); ?> €</strong>
                            <span class="status-pill <?php echo ($selected['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($selected)); ?></span>
                        </div>
                    </div>

                    <div class="detail-gallery">
                        <?php foreach ($selected['images'] as $image): ?>
                            <figure class="detail-photo-card">
                                <img src="<?php echo catalog_escape($image['data']); ?>" alt="<?php echo catalog_escape($selected['title']); ?>">
                            </figure>
                        <?php endforeach; ?>
                    </div>

                    <div class="detail-grid">
                        <article class="detail-card">
                            <h4>Description</h4>
                            <p><?php echo nl2br(catalog_escape($selected['description'])); ?></p>
                        </article>
                        <article class="detail-card">
                            <h4>Renseignements véhicule</h4>
                            <p><?php echo nl2br(catalog_escape($selected['specs'])); ?></p>
                        </article>
                    </div>

                    <div class="deposit-note multi-deposit-note" id="vehicle-selection-summary" hidden>
                        <strong>Véhicules sélectionnés :</strong> <span id="selected-vehicles-count">0</span>
                    </div>

                    <div class="detail-actions">
                        <?php if (($selected['status'] ?? '') === 'reserved'): ?>
                            <span class="cta-link cta-link-disabled">Véhicule actuellement réservé</span>
                        <?php else: ?>
                            <?php if (!empty($selected['transaction_in_progress'])): ?>
                                <div class="deposit-note">
                                    <strong>Transaction en cours :</strong> un dossier est déjà ouvert sur ce véhicule. Vous pouvez quand même demander un essai ultérieur en cas de non-conclusion.
                                </div>
                                <a class="cta-link" href="<?php echo catalog_escape(catalog_build_contact_link($selected)); ?>">Demander un essai ultérieur →</a>
                            <?php else: ?>
                                <div class="devis-form-actions">
                                    <a
                                        class="cta-link"
                                        href="<?php echo catalog_escape(catalog_build_contact_link($selected)); ?>"
                                        id="vehicle-cta-btn"
                                        data-vehicle-contact-base="<?php echo catalog_escape(catalog_build_contact_link($selected)); ?>"
                                        data-vehicle-id="<?php echo (int) ($selected['id'] ?? 0); ?>"
                                        data-vehicle-title="<?php echo catalog_escape((string) ($selected['title'] ?? '')); ?>"
                                        data-vehicle-price="<?php echo catalog_escape((string) ($selected['price'] ?? '')); ?>"
                                    >Valider mon rendez-vous →</a>
                                    <button type="button" class="btn-secondary" id="vehicles-continue-btn">Continuer la navigation →</button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>Aucune annonce vehicule n'est disponible pour le moment.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script>
    (function () {
        var selectors = document.querySelectorAll('.vehicle-selector');
        if (selectors.length === 0) { return; }

        var ctaBtn = document.getElementById('vehicle-cta-btn');
        var countLabel = document.getElementById('selected-vehicles-count');
        var summaryDiv = document.getElementById('vehicle-selection-summary');
        var storageKey = 'clinikauto_selected_vehicles_v1';

        var selectedIds = [];
        var selectedTitles = [];

        var updateSummary = function () {
            var count = selectedIds.length;
            if (countLabel) { countLabel.textContent = String(count); }
            if (summaryDiv) { summaryDiv.hidden = count === 0; }
        };

        var updateCta = function () {
            if (!ctaBtn) { return; }
            var base = ctaBtn.getAttribute('data-vehicle-contact-base') || ctaBtn.getAttribute('href') || '';
            if (selectedIds.length === 0) {
                ctaBtn.setAttribute('href', base);
                return;
            }
            var destination = new URL(base, window.location.href);
            destination.searchParams.set('selected_vehicles_count', String(selectedIds.length));
            destination.searchParams.set('selected_vehicles_ids', selectedIds.join(','));
            destination.searchParams.set('selected_vehicles_titles', selectedTitles.join(' | '));
            if (selectedIds.length > 1) {
                destination.searchParams.set('sujet', 'Réservation visite véhicules - ' + selectedIds.length + ' véhicules sélectionnés');
                destination.searchParams.set('message', 'Bonjour, je souhaite réserver une visite pour les véhicules suivants :\n' + selectedTitles.map(function (t) { return '- ' + t; }).join('\n') + '\nMerci de me proposer un rendez-vous.');
                destination.searchParams.set('annonce_id', String(selectedIds[0]));
                destination.searchParams.set('annonce_title', selectedTitles[0] || '');
            }
            ctaBtn.setAttribute('href', destination.toString());
        };

        var syncFromCheckboxes = function () {
            selectedIds = [];
            selectedTitles = [];
            selectors.forEach(function (cb) {
                if (!cb.checked) { return; }
                var id = parseInt(cb.getAttribute('data-vehicle-id') || cb.value || '0', 10);
                var title = cb.getAttribute('data-vehicle-title') || '';
                if (id > 0) { selectedIds.push(id); }
                if (title) { selectedTitles.push(title); }
            });
            try {
                localStorage.setItem(storageKey, JSON.stringify(selectedIds));
            } catch (e) {}
            updateSummary();
            updateCta();
        };

        var restoreSelection = function () {
            var restoredIds = [];
            try {
                var raw = localStorage.getItem(storageKey);
                var parsed = raw ? JSON.parse(raw) : [];
                if (Array.isArray(parsed)) {
                    restoredIds = parsed.map(function (v) { return parseInt(v, 10); }).filter(function (v) { return v > 0; });
                }
            } catch (e) {}
            if (restoredIds.length > 0) {
                selectors.forEach(function (cb) {
                    var id = parseInt(cb.getAttribute('data-vehicle-id') || cb.value || '0', 10);
                    cb.checked = restoredIds.indexOf(id) !== -1;
                });
            }
            syncFromCheckboxes();
        };

        selectors.forEach(function (cb) {
            cb.addEventListener('change', syncFromCheckboxes);
        });

        restoreSelection();

        var vehiclesContinueBtn = document.getElementById('vehicles-continue-btn');
        if (vehiclesContinueBtn) {
            vehiclesContinueBtn.addEventListener('click', function () {
                syncFromCheckboxes();
                window.location.href = 'catalogue.php';
            });
        }
    })();
    </script>
    <footer>
        <address>
            <strong>Clinik Auto</strong> &mdash; Garage Automobile<br>
            118 Clos des Teppes, 74950 Scionzier<br>
            <a href="tel:+33620185627">06 20 18 56 27</a> &bull;
            <a href="mailto:clinikauto74@gmail.com">clinikauto74@gmail.com</a>
        </address>
        <p class="footer-hours">Lun&ndash;Ven : 9h&ndash;12h / 14h&ndash;18h &bull; Sam : 9h&ndash;12h &bull; Dim : Ferm&eacute;</p>
        <p class="footer-social">
            <a href="https://www.facebook.com/Clinik.Auto" target="_blank" rel="noopener noreferrer">&#x1F4D8; Suivez-nous sur Facebook</a>
        </p>
        <p class="footer-copy">&copy; 2026 Clinik Auto. Tous droits r&eacute;serv&eacute;s.</p>