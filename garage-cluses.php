<?php
require_once __DIR__ . '/includes/catalog_store.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$showAdminReturn = catalog_is_admin_session_active();
catalog_track_visit('local_cluses');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Auto proche Cluses (74300) | Révision, Réparation | Clinik Auto Scionzier</title>
    <meta name="description" content="Clinik Auto, garage à 5 min de Cluses (74300) à Scionzier. Révision, entretien, réparation multimarque, vente VO & pièces auto d'occasion. Devis gratuit – 06 20 18 56 27.">
    <meta name="keywords" content="garage Cluses, révision auto Cluses 74300, réparation voiture Cluses, entretien auto Cluses Haute-Savoie, garage mécanique Cluses, mécano Cluses, VO Cluses, pièces occasion Cluses">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Cluses">
    <link rel="canonical" href="https://www.clinikauto.fr/garage-cluses.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Garage Auto proche Cluses (74300) | Clinik Auto">
    <meta property="og:description" content="Votre garage automobile à 5 min de Cluses à Scionzier (74950). Révision, réparation, vente VO. Devis gratuit.">
    <meta property="og:url" content="https://www.clinikauto.fr/garage-cluses.php">
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
                    { "@type": "ListItem", "position": 2, "name": "Garage proche Cluses", "item": "https://www.clinikauto.fr/garage-cluses.php" }
                ]
            },
            {
                "@type": "AutoRepair",
                "@id": "https://www.clinikauto.fr/#garage",
                "name": "Clinik Auto",
                "description": "Garage automobile à Scionzier (74950), à 5 min de Cluses. Révision, entretien, réparation multimarque, vente de véhicules d'occasion et pièces auto contrôlées.",
                "url": "https://www.clinikauto.fr/",
                "logo": "https://www.clinikauto.fr/assets/logo.avif",
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
                    { "@type": "City", "name": "Cluses" },
                    { "@type": "City", "name": "Scionzier" },
                    { "@type": "City", "name": "Marnaz" },
                    { "@type": "City", "name": "Nancy-sur-Cluses" }
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
                "url": "https://www.clinikauto.fr/garage-cluses.php",
                "name": "Garage auto proche Cluses (74300) – Clinik Auto Scionzier",
                "description": "Clinik Auto, garage auto à Scionzier à 5 min de Cluses : révision, réparation, vente VO, pièces occasion. Devis gratuit.",
                "isPartOf": { "@id": "https://www.clinikauto.fr/#website" },
                "about": { "@id": "https://www.clinikauto.fr/#garage" },
                "breadcrumb": { "@type": "BreadcrumbList" }
            }
        ]
    }
    </script>
    <?php echo catalog_get_google_analytics_script(); ?>
    <?php if (function_exists('csrf_print_meta_and_js')) { csrf_print_meta_and_js(); } ?>
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
            <li><a href="contact/contact.php">Contact</a></li>
            <?php if ($showAdminReturn): ?><li><a href="admin.php">Retour admin</a></li><?php endif; ?>
        </ul>
    </nav>
</header>

<main class="page-main-hero">
    <section>
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb-list">
                <li><a href="index.html">Accueil</a></li>
                <li aria-current="page">Garage proche Cluses</li>
            </ol>
        </nav>
        <span class="hero-badge">📍 À 5 min de Cluses</span>
        <h1>Votre garage automobile à proximité de Cluses (74300)</h1>
        <p>Clinik Auto, situé au 118 Clos des Teppes à <strong>Scionzier (74950)</strong>, est votre garage de confiance à quelques minutes de <strong>Cluses</strong>. Révision, entretien, réparation multimarque, vente de véhicules d'occasion et pièces auto contrôlées : nous couvrons tous vos besoins automobile en Haute-Savoie.</p>
    </section>

    <section class="spaced-section">
        <h2>Nos services pour les habitants de Cluses</h2>
        <div class="services-grid">
            <a class="service-card service-card-link" href="devis/devis.php?service=revision">
                <span class="service-icon">🔧</span>
                <h3>Révision & Entretien</h3>
                <p>Révision constructeur, vidange, filtres, courroie de distribution. Tarifs compétitifs et transparents pour les automobilistes de Cluses.</p>
            </a>
            <a class="service-card service-card-link" href="devis/devis.php?service=reparation">
                <span class="service-icon">🛠️</span>
                <h3>Réparation Auto</h3>
                <p>Diagnostic électronique, freinages, embrayage, direction, moteur. Toutes marques acceptées.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/occasion.php">
                <span class="service-icon">🚗</span>
                <h3>Vente de VO</h3>
                <p>Véhicules d'occasion contrôlés et garantis. Venez les voir sur rendez-vous à Scionzier, à 5 min de Cluses.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/pieces.php">
                <span class="service-icon">⚙️</span>
                <h3>Pièces Auto Occasion</h3>
                <p>Jantes, freinage, moteur, carrosserie : pièces détachées contrôlées disponibles en stock. Commande en ligne ou sur place.</p>
            </a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Pourquoi choisir Clinik Auto depuis Cluses ?</h2>
        <ul class="reasons-list">
            <li>✓ <strong>5 minutes en voiture</strong> depuis le centre de Cluses via la D1205</li>
            <li>✓ Garage multimarque sans rendez-vous forcé – disponibilité rapide</li>
            <li>✓ Devis gratuit en ligne, réponse dans les meilleurs délais</li>
            <li>✓ Tarifs transparents, sans surprise</li>
            <li>✓ Prise en charge complète : entretien, réparation, vente, pièces</li>
            <li>✓ Ouvert le samedi matin</li>
        </ul>
        <p style="margin-top:1rem;">Nos clients de Cluses apprécient la facilité d'accès à notre atelier depuis la vallée de l'Arve. Que vous habitiez <strong>Cluses, Nancy-sur-Cluses ou Marnaz</strong>, Clinik Auto est votre garage de proximité en Haute-Savoie.</p>
    </section>

    <section class="spaced-section">
        <h2>Comment venir depuis Cluses ?</h2>
        <p>Depuis le centre de Cluses, prenez la direction de Scionzier par la <strong>D1205</strong>. L'atelier Clinik Auto se trouve au <strong>118 Clos des Teppes, 74950 Scionzier</strong>, facilement accessible depuis la vallée de l'Arve. Parking gratuit sur place.</p>
        <p style="margin-top:0.75rem;">Besoin de vous organiser ? Vous pouvez <a href="contact/contact.php">nous contacter</a> et nous pouvons, dans certains cas, organiser le convoyage de votre véhicule.</p>
    </section>

    <section class="cta-section">
        <h2>Prenez rendez-vous ou demandez un devis</h2>
        <p>Vous habitez Cluses ou ses environs ? Contactez-nous dès maintenant pour un devis gratuit ou une réservation de créneau.</p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;margin-top:1rem;">
            <a href="devis/devis.php" class="cta-link">Demande de devis gratuit</a>
            <a href="contact/contact.php">Prendre rendez-vous</a>
            <a href="tel:+33620185627" class="cta-link-secondary">📞 06 20 18 56 27</a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Questions fréquentes – Garage proche Cluses</h2>
        <div class="review-card">
            <p><strong>Faut-il prendre rendez-vous pour venir depuis Cluses ?</strong></p>
            <p>Non, vous pouvez passer directement à l'atelier, mais pour les réparations importantes ou les révisions complètes, un rendez-vous permet de vous garantir un accueil optimal et un délai d'attente minimal.</p>
            <p><strong>Faites-vous les contrôles techniques ?</strong></p>
            <p>Non, Clinik Auto réalise l'entretien, la réparation et la préparation de votre véhicule avant contrôle technique. Nous pouvons vous orienter vers un centre CT partenaire proche de Scionzier.</p>
            <p><strong>Quels sont les délais pour une révision ?</strong></p>
            <p>Une révision standard est généralement réalisée en une demi-journée. Pour les interventions plus complexes, nous vous communiquons un délai estimé lors du devis.</p>
        </div>
    </section>

    <section class="spaced-section" style="text-align:center">
        <h2>Zones desservies autour de Scionzier</h2>
        <p>En plus de <strong>Cluses</strong>, notre garage accueille les automobilistes venant de <strong>Bonneville, Sallanches, Marnaz, Nancy-sur-Cluses, Thyez, Marignier</strong> et de tout le bassin de l'Arve.</p>
        <p style="margin-top:0.8rem; display:flex; gap:0.6rem; justify-content:center; flex-wrap:wrap;">
            <a href="garage-bonneville.php" class="cta-link-secondary">Garage proche Bonneville</a>
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
<button class="site-back-top-floating" id="site-back-top" aria-label="Retour en haut de la page">↑</button>
<script>!function(){var b=document.getElementById('site-back-top');if(!b)return;window.addEventListener('scroll',function(){b.classList.toggle('is-visible',window.scrollY>420);},{passive:true});b.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});}();</script>
<script src="assets/conversion-tracking.js" defer></script>
</body>
</html>
