<?php
require_once dirname(__DIR__) . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('catalogue_home');

$vehicles = array_slice(catalog_all_items('vehicle'), 0, 3);
$parts = array_slice(catalog_all_items('part'), 0, 3);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue Auto – Véhicules & Pièces d'Occasion | Clinik Auto Scionzier (74)</title>
    <meta name="description" content="Découvrez le catalogue Clinik Auto à Scionzier (74950) : véhicules d'occasion sélectionnés et pièces auto contrôlées. Garage automobile en Haute-Savoie.">
    <meta name="keywords" content="catalogue voiture occasion Scionzier, pièces auto occasion 74, vente VO Haute-Savoie, garage occasion Cluses, véhicules contrôlés 74950">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/catalogue/catalogue.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Catalogue Auto – Véhicules & Pièces d'Occasion | Clinik Auto">
    <meta property="og:description" content="Véhicules d'occasion et pièces auto contrôlées chez Clinik Auto à Scionzier (74). Consultez nos annonces en ligne.">
    <meta property="og:url" content="https://www.clinikauto.fr/catalogue/catalogue.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Catalogue – Véhicules & Pièces d'Occasion | Clinik Auto Scionzier">
    <meta name="twitter:description" content="Véhicules d'occasion et pièces auto contrôlées chez Clinik Auto à Scionzier (74950).">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://www.clinikauto.fr/"},
        {"@type": "ListItem", "position": 2, "name": "Catalogue", "item": "https://www.clinikauto.fr/catalogue/catalogue.php"}
      ]
    }
    </script>
</head>
<body>
    <header>
        <div class="site-brand">
            <a class="site-brand-link" href="../index.html" aria-label="Clinik Auto accueil">
                <img class="site-logo" src="../assets/logo.png" alt="Logo Clinik Auto">
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
        <h2>Choisissez votre univers</h2>
        <p>Accedez aux vehicules d'occasion et aux pieces disponibles depuis deux espaces distincts. Chaque ligne d'annonce ouvre sa fiche detaillee avec galerie photo, renseignements complets et action directe vers le formulaire de contact.</p>

        <div class="catalog-split-grid">
            <section class="inventory-block">
                <div class="inventory-block-head">
                    <div>
                        <h3>Nos véhicules disponibles</h3>
                        <p>Consultez les annonces vehicules et ouvrez chaque fiche en detail.</p>
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
                        <p>Retrouvez les pieces selectionnees avec prix, compatibilite et reservation.</p>
                    </div>
                    <a class="cta-link cta-link-small" href="pieces.php">Voir les pieces →</a>
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