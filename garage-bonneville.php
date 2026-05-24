<?php
require_once __DIR__ . '/includes/catalog_store.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$showAdminReturn = catalog_is_admin_session_active();
catalog_track_visit('local_bonneville');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Auto proche Bonneville (74130) | Révision, Réparation | Clinik Auto Scionzier</title>
    <meta name="description" content="Clinik Auto, garage à 15 min de Bonneville (74130) à Scionzier. Révision, entretien, réparation multimarque, vente VO & pièces auto d'occasion. Devis gratuit – 06 20 18 56 27.">
    <meta name="keywords" content="garage Bonneville, révision auto Bonneville 74130, réparation voiture Bonneville, entretien auto Bonneville Haute-Savoie, garage mécanique Bonneville, VO Bonneville, pièces occasion Bonneville 74">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Bonneville">
    <link rel="canonical" href="https://www.clinikauto.fr/garage-bonneville.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Garage Auto proche Bonneville (74130) | Clinik Auto">
    <meta property="og:description" content="Votre garage automobile à 15 min de Bonneville à Scionzier (74950). Révision, réparation, vente VO. Devis gratuit.">
    <meta property="og:url" content="https://www.clinikauto.fr/garage-bonneville.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.avif">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <link rel="icon" type="image/png" href="assets/logo.avif">
    <link rel="stylesheet" href="assets/style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "BreadcrumbList",
                "itemListElement": [
                    { "@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://www.clinikauto.fr/" },
                    { "@type": "ListItem", "position": 2, "name": "Garage proche Bonneville", "item": "https://www.clinikauto.fr/garage-bonneville.php" }
                ]
            },
            {
                "@type": "AutoRepair",
                "@id": "https://www.clinikauto.fr/#garage",
                "name": "Clinik Auto",
                "description": "Garage automobile à Scionzier (74950), desservant Bonneville, Cluses, Sallanches et toute la vallée de l'Arve.",
                "url": "https://www.clinikauto.fr/",
                "logo": "https://www.clinikauto.fr/assets/logo.png",
                "telephone": "+33620185627",
                "email": "clinikauto74@gmail.com",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "118 Clos des Teppes",
                    "addressLocality": "Scionzier",
                    "postalCode": "74950",
                    "addressRegion": "Haute-Savoie",
                    "addressCountry": "FR"
                },
                "geo": { "@type": "GeoCoordinates", "latitude": 46.0608, "longitude": 6.5394 },
                "areaServed": [
                    { "@type": "City", "name": "Bonneville" },
                    { "@type": "City", "name": "Scionzier" },
                    { "@type": "City", "name": "Cluses" },
                    { "@type": "City", "name": "Marignier" },
                    { "@type": "City", "name": "Thyez" }
                ],
                "openingHoursSpecification": [
                    { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"], "opens": "09:00", "closes": "12:00" },
                    { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"], "opens": "14:00", "closes": "18:00" },
                    { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Saturday"], "opens": "09:00", "closes": "12:00" }
                ],
                "priceRange": "€€",
                "sameAs": ["https://www.facebook.com/Clinik.Auto"]
            },
            {
                "@type": "WebPage",
                "url": "https://www.clinikauto.fr/garage-bonneville.php",
                "name": "Garage auto proche Bonneville (74130) – Clinik Auto Scionzier",
                "description": "Clinik Auto, garage auto à Scionzier à 15 min de Bonneville : révision, réparation, vente VO, pièces occasion.",
                "isPartOf": { "@id": "https://www.clinikauto.fr/#website" },
                "about": { "@id": "https://www.clinikauto.fr/#garage" }
            }
        ]
    }
    </script>
    <?php echo catalog_get_google_analytics_script(); ?>
</head>
<body class="public-page">
<header>
    <div class="site-brand">
        <a class="site-brand-link" href="index.html" aria-label="Clinik Auto accueil">
            <img class="site-logo" src="assets/logo.avif" alt="Logo Clinik Auto">
        </a>
    </div>
    <nav>
        <ul>
            <li><a href="index.html">Accueil</a></li>
            <li><a href="catalogue/catalogue.php">Catalogue</a></li>
            <li><a href="devis/devis.php">Devis gratuit</a></li>
            <li><a href="rdv/rdv.php">Rendez-vous</a></li>
            <li><a href="contact/contact.php">Contact</a></li>
            <?php if ($showAdminReturn): ?><li><a href="admin.php">Retour admin</a></li><?php endif; ?>
        </ul>
    </nav>
</header>

<main>
    <section>
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb-list">
                <li><a href="index.html">Accueil</a></li>
                <li aria-current="page">Garage proche Bonneville</li>
            </ol>
        </nav>
        <span class="hero-badge">📍 À 15 min de Bonneville</span>
        <h1>Votre garage automobile à proximité de Bonneville (74130)</h1>
        <p>Clinik Auto est situé au 118 Clos des Teppes à <strong>Scionzier (74950)</strong>, à seulement 15 minutes de <strong>Bonneville</strong>. Notre atelier multimarque propose révision, entretien, réparation automobile, vente de véhicules d'occasion et pièces auto contrôlées pour tous les automobilistes de la région.</p>
    </section>

    <section class="spaced-section">
        <h2>Nos services pour les habitants de Bonneville</h2>
        <div class="services-grid">
            <a class="service-card service-card-link" href="devis/devis.php?service=revision">
                <span class="service-icon">🔧</span>
                <h3>Révision & Entretien</h3>
                <p>Révision constructeur, vidange, distribution, filtres. Interventions rapides sur toutes marques de véhicules.</p>
            </a>
            <a class="service-card service-card-link" href="devis/devis.php?service=reparation">
                <span class="service-icon">🛠️</span>
                <h3>Réparation Auto</h3>
                <p>Diagnostic, freinage, embrayage, suspension, moteur. Devis précis et transparent avant intervention.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/occasion.php">
                <span class="service-icon">🚗</span>
                <h3>Vente VO</h3>
                <p>Sélection de véhicules d'occasion contrôlés et garantis, visibles à Scionzier sur rendez-vous.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/pieces.php">
                <span class="service-icon">⚙️</span>
                <h3>Pièces Occasion</h3>
                <p>Stock de pièces détachées contrôlées : jantes, freinage, carrosserie, moteur. Commande possible en ligne.</p>
            </a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Pourquoi faire confiance à Clinik Auto depuis Bonneville ?</h2>
        <ul class="reasons-list">
            <li>✓ <strong>15 minutes en voiture</strong> depuis Bonneville par la D1205 / A40</li>
            <li>✓ Tarifs compétitifs et devis gratuit en ligne dans les meilleurs délais</li>
            <li>✓ Garage indépendant : meilleur rapport qualité-prix que les réseaux franchisés</li>
            <li>✓ Équipe expérimentée sur toutes marques</li>
            <li>✓ Ouvert du lundi au vendredi + samedi matin</li>
            <li>✓ Vente et recherche de VO selon votre budget</li>
        </ul>
        <p style="margin-top:1rem;">Les automobilistes venant de <strong>Bonneville, Marignier, Thyez, Viuz-en-Sallaz</strong> et du bassin de la Fillière nous font confiance pour l'entretien régulier de leur véhicule. Notre situation à Scionzier est un axe central en Haute-Savoie.</p>
    </section>

    <section class="spaced-section">
        <h2>Comment venir depuis Bonneville ?</h2>
        <p>Depuis <strong>Bonneville (74130)</strong>, prenez la direction de Cluses/Scionzier via la <strong>D1205</strong> (environ 15 minutes). L'atelier est au <strong>118 Clos des Teppes, 74950 Scionzier</strong>. Parking gratuit sur place.</p>
        <p style="margin-top:0.75rem;">Vous pouvez aussi <a href="rdv/rdv.php">prendre rendez-vous en ligne</a> pour choisir le créneau qui vous convient et éviter l'attente à votre arrivée.</p>
    </section>

    <section class="cta-section">
        <h2>Devis ou rendez-vous depuis Bonneville</h2>
        <p>Un entretien à prévoir ? Une réparation urgente ? Contactez Clinik Auto dès maintenant.</p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;margin-top:1rem;">
            <a href="devis/devis.php" class="cta-link">Demande de devis gratuit</a>
            <a href="rdv/rdv.php" class="cta-link-secondary">Prendre rendez-vous</a>
            <a href="tel:+33620185627" class="cta-link-secondary">📞 06 20 18 56 27</a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Questions fréquentes – Garage proche Bonneville</h2>
        <div class="review-card">
            <p><strong>Intervenez-vous sur les véhicules récents ou seulement les anciens modèles ?</strong></p>
            <p>Clinik Auto intervient sur les véhicules de toutes générations : récents, anciens, thermiques ou hybrides légers. Nous disposons d'un outil de diagnostic multimarque adapté aux modèles actuels.</p>
            <p><strong>Proposez-vous un véhicule de remplacement pendant les réparations ?</strong></p>
            <p>Nous ne disposons pas de flotte de prêt permanente, mais nous pouvons vous orienter selon les disponibilités. Contactez-nous lors de votre prise de rendez-vous pour en discuter.</p>
            <p><strong>Peut-on déposer son véhicule le matin avant l'ouverture ?</strong></p>
            <p>Contactez-nous pour convenir d'un dépôt anticipé. Nous essayons de nous adapter aux contraintes horaires de nos clients travaillant dans la région.</p>
        </div>
    </section>

    <section class="spaced-section" style="text-align:center">
        <h2>Autres villes desservies</h2>
        <p>Clinik Auto est également proche de <strong>Cluses, Sallanches et Scionzier</strong>. Consultez nos pages dédiées.</p>
        <p style="margin-top:0.8rem; display:flex; gap:0.6rem; justify-content:center; flex-wrap:wrap;">
            <a href="garage-cluses.php" class="cta-link-secondary">Garage proche Cluses</a>
            <a href="garage-sallanches.php" class="cta-link-secondary">Garage proche Sallanches</a>
            <a href="faq.php" class="cta-link-secondary">Questions frequentes</a>
        </p>
    </section>
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
        <a href="https://www.facebook.com/Clinik.Auto" target="_blank" rel="noopener noreferrer" aria-label="Page Facebook Clinik Auto">&#x1F4D8; Suivez-nous sur Facebook</a>
    </p>
    <p class="footer-copy">&copy; 2026 Clinik Auto. Tous droits r&eacute;serv&eacute;s.</p>
</footer>
<script src="assets/conversion-tracking.js" defer></script>
</body>
</html>
