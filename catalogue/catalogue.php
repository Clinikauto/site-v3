<?php
require_once dirname(__DIR__) . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('catalogue_home');

$vehicles = array_slice(catalog_all_items('vehicle'), 0, 3);
$parts = array_slice(catalog_all_items('part'), 0, 3);

$vehicleListItems = [];
foreach ($vehicles as $index => $vehicle) {
    $vehicleListItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => 'https://www.clinikauto.fr/catalogue/occasion.php?id=' . (int) ($vehicle['id'] ?? 0),
        'item' => [
            '@type' => 'Car',
            'name' => (string) ($vehicle['title'] ?? ''),
            'description' => (string) ($vehicle['subtitle'] ?? ''),
            'image' => catalog_primary_image($vehicle),
            'url' => 'https://www.clinikauto.fr/catalogue/occasion.php?id=' . (int) ($vehicle['id'] ?? 0),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => (string) ((float) ($vehicle['price'] ?? 0)),
                'availability' => (($vehicle['status'] ?? '') === 'reserved')
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock'
            ]
        ]
    ];
}

$partListItems = [];
foreach ($parts as $index => $part) {
    $partListItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => 'https://www.clinikauto.fr/catalogue/pieces.php?id=' . (int) ($part['id'] ?? 0),
        'item' => [
            '@type' => 'Product',
            'name' => (string) ($part['title'] ?? ''),
            'description' => (string) ($part['subtitle'] ?? ''),
            'image' => catalog_primary_image($part),
            'url' => 'https://www.clinikauto.fr/catalogue/pieces.php?id=' . (int) ($part['id'] ?? 0),
            'itemCondition' => 'https://schema.org/UsedCondition',
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => (string) ((float) ($part['price'] ?? 0)),
                'availability' => (($part['status'] ?? '') === 'reserved')
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock'
            ]
        ]
    ];
}

$catalogStructuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => 'https://www.clinikauto.fr/catalogue/catalogue.php#webpage',
            'url' => 'https://www.clinikauto.fr/catalogue/catalogue.php',
            'name' => 'Catalogue Auto - Véhicules et Pièces d\'Occasion | Clinik Auto',
            'description' => 'Catalogue Clinik Auto à Scionzier : véhicules d\'occasion sélectionnés et pièces auto contrôlées.',
            'isPartOf' => [
                '@id' => 'https://www.clinikauto.fr/#website'
            ],
            'about' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ]
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://www.clinikauto.fr/catalogue/catalogue.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://www.clinikauto.fr/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Catalogue', 'item' => 'https://www.clinikauto.fr/catalogue/catalogue.php']
            ]
        ],
        [
            '@type' => 'ItemList',
            'name' => 'Véhicules d\'occasion en vedette',
            'numberOfItems' => count($vehicleListItems),
            'itemListElement' => $vehicleListItems
        ],
        [
            '@type' => 'ItemList',
            'name' => 'Pièces d\'occasion en vedette',
            'numberOfItems' => count($partListItems),
            'itemListElement' => $partListItems
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue VO & Pièces Occasion | Garage Scionzier (74950) | Clinik Auto</title>
    <meta name="description" content="Catalogue complet Clinik Auto : véhicules d'occasion contrôlés et pièces détachées auto à Scionzier (74950). Garage de proximité, proche Cluses, Bonneville et Sallanches.">
    <meta name="keywords" content="catalogue voiture occasion Scionzier, pièces auto occasion 74, vente VO Haute-Savoie, garage occasion Cluses, véhicules contrôlés 74950">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/catalogue/catalogue.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Catalogue Auto – Véhicules & Pièces d'Occasion | Clinik Auto">
    <meta property="og:description" content="Véhicules d'occasion et pièces auto contrôlées chez Clinik Auto à Scionzier (74). Consultez nos annonces en ligne.">
    <meta property="og:url" content="https://www.clinikauto.fr/catalogue/catalogue.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.avif">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Catalogue – Véhicules & Pièces d'Occasion | Clinik Auto Scionzier">
    <meta name="twitter:description" content="Véhicules d'occasion et pièces auto contrôlées chez Clinik Auto à Scionzier (74950).">
    <link rel="icon" type="image/png" href="../assets/logo.avif">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
    <script type="application/ld+json">
        <?php echo json_encode($catalogStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
</head>
<body class="public-page">
    <header>
        <div class="site-brand">
            <a class="site-brand-link" href="../index.html" aria-label="Clinik Auto accueil">
                <img class="site-logo" src="../assets/logo.avif" alt="Logo Clinik Auto">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="../contact/contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="catalog-home">
        <span class="hero-badge">Catalogue dynamique</span>
        <h1>Catalogue auto Clinik Auto : véhicules et pièces d'occasion</h1>
        <p>Accédez aux véhicules d'occasion et aux pièces disponibles depuis deux espaces distincts. Chaque ligne d'annonce ouvre sa fiche détaillée avec galerie photo, renseignements complets et action directe vers le formulaire de contact.</p>

        <div class="catalog-split-grid">
            <section class="inventory-block">
                <div class="inventory-block-head">
                    <div>
                        <h3>Nos véhicules disponibles</h3>
                        <p>Consultez les annonces véhicules et ouvrez chaque fiche en détail.</p>
                    </div>
                    <a class="cta-link cta-link-small" href="occasion.php">Voir les occasions →</a>
                </div>
                <div class="inventory-list compact-list">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <a class="inventory-row" href="occasion.php?id=<?php echo (int) $vehicle['id']; ?>">
                            <img class="inventory-thumb" src="<?php echo catalog_escape(catalog_primary_image($vehicle)); ?>" alt="<?php echo catalog_escape($vehicle['title']); ?>">
                            <span class="inventory-row-text">
                                <strong><?php echo catalog_escape($vehicle['title']); ?></strong>
                                <small><?php echo catalog_escape($vehicle['subtitle']); ?></small>
                            </span>
                            <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($vehicle['price'])); ?> €</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="inventory-block">
                <div class="inventory-block-head">
                    <div>
                        <h3>Nos pièces d'occasion</h3>
                        <p>Retrouvez les pièces sélectionnées avec prix, compatibilité et réservation.</p>
                    </div>
                    <a class="cta-link cta-link-small" href="pieces.php">Voir les pièces →</a>
                </div>
                <div class="inventory-list compact-list">
                    <?php foreach ($parts as $part): ?>
                        <a class="inventory-row" href="pieces.php?id=<?php echo (int) $part['id']; ?>">
                            <img class="inventory-thumb" src="<?php echo catalog_escape(catalog_primary_image($part)); ?>" alt="<?php echo catalog_escape($part['title']); ?>">
                            <span class="inventory-row-text">
                                <strong><?php echo catalog_escape($part['title']); ?></strong>
                                <small><?php echo catalog_escape($part['subtitle']); ?></small>
                            </span>
                            <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($part['price'])); ?> €</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
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
</body>
</html>