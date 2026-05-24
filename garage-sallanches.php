<?php
require_once __DIR__ . '/includes/catalog_store.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$showAdminReturn = catalog_is_admin_session_active();
catalog_track_visit('local_sallanches');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage Auto proche Sallanches (74700) | Révision, Réparation | Clinik Auto Scionzier</title>
    <meta name="description" content="Clinik Auto, garage à 20 min de Sallanches (74700) à Scionzier. Révision, entretien, réparation multimarque, vente VO & pièces auto. Devis gratuit – 06 20 18 56 27.">
    <meta name="keywords" content="garage Sallanches, révision auto Sallanches 74700, réparation voiture Sallanches, entretien auto Sallanches Haute-Savoie, mécano Sallanches, VO Sallanches, pièces occasion Sallanches 74">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Sallanches">
    <link rel="canonical" href="https://www.clinikauto.fr/garage-sallanches.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Garage Auto proche Sallanches (74700) | Clinik Auto">
    <meta property="og:description" content="Votre garage automobile à 20 min de Sallanches à Scionzier (74950). Révision, réparation, vente VO. Devis gratuit.">
    <meta property="og:url" content="https://www.clinikauto.fr/garage-sallanches.php">
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
                    { "@type": "ListItem", "position": 2, "name": "Garage proche Sallanches", "item": "https://www.clinikauto.fr/garage-sallanches.php" }
                ]
            },
            {
                "@type": "AutoRepair",
                "@id": "https://www.clinikauto.fr/#garage",
                "name": "Clinik Auto",
                "description": "Garage automobile à Scionzier (74950), desservant Sallanches, Cluses, Bonneville et toute la Haute-Savoie.",
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
                    { "@type": "City", "name": "Sallanches" },
                    { "@type": "City", "name": "Scionzier" },
                    { "@type": "City", "name": "Cluses" },
                    { "@type": "City", "name": "Combloux" },
                    { "@type": "City", "name": "Megève" }
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
                "url": "https://www.clinikauto.fr/garage-sallanches.php",
                "name": "Garage auto proche Sallanches (74700) – Clinik Auto Scionzier",
                "description": "Clinik Auto, garage auto à Scionzier à 20 min de Sallanches : révision, réparation, vente VO, pièces occasion.",
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
                <li aria-current="page">Garage proche Sallanches</li>
            </ol>
        </nav>
        <span class="hero-badge">📍 À 20 min de Sallanches</span>
        <h1>Votre garage automobile à proximité de Sallanches (74700)</h1>
        <p>Clinik Auto, situé au 118 Clos des Teppes à <strong>Scionzier (74950)</strong>, accueille les automobilistes de <strong>Sallanches</strong> et du pays du Mont-Blanc. Notre garage multimarque propose révision, entretien, réparation, vente de véhicules d'occasion et pièces auto contrôlées à des tarifs compétitifs.</p>
    </section>

    <section class="spaced-section">
        <h2>Nos services pour les habitants de Sallanches</h2>
        <div class="services-grid">
            <a class="service-card service-card-link" href="devis/devis.php?service=revision">
                <span class="service-icon">🔧</span>
                <h3>Révision & Entretien</h3>
                <p>Révision complète ou partielle, vidange, freins, distribution. Tarifs clairs, devis gratuit avant intervention.</p>
            </a>
            <a class="service-card service-card-link" href="devis/devis.php?service=reparation">
                <span class="service-icon">🛠️</span>
                <h3>Réparation Auto</h3>
                <p>Panne, témoin allumé, bruit suspect ? Diagnostic électronique et mécanique toutes marques.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/occasion.php">
                <span class="service-icon">🚗</span>
                <h3>Achat VO</h3>
                <p>Recherche de voiture d'occasion adaptée à votre budget et vos besoins en montagne.</p>
            </a>
            <a class="service-card service-card-link" href="catalogue/pieces.php">
                <span class="service-icon">⚙️</span>
                <h3>Pièces Auto</h3>
                <p>Large stock de pièces d'occasion contrôlées : jantes, freinage, carrosserie, optiques, moteur.</p>
            </a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Pourquoi venir chez Clinik Auto depuis Sallanches ?</h2>
        <ul class="reasons-list">
            <li>✓ <strong>20 minutes en voiture</strong> depuis Sallanches via la D1205 / A40</li>
            <li>✓ Tarifs inférieurs aux concessions et chaînes de franchise</li>
            <li>✓ Garage indépendant : interlocuteur unique, relation de confiance</li>
            <li>✓ Réservation en ligne disponible 24h/24</li>
            <li>✓ Accueil du lundi au samedi matin</li>
            <li>✓ Connaissance des spécificités mécaniques liées à la conduite en altitude</li>
        </ul>
        <p style="margin-top:1rem;">Nos clients venant de <strong>Sallanches, Combloux, Cordon, Passy et Megève</strong> apprécient notre approche humaine et notre réactivité. Que ce soit pour un entretien de routine ou une réparation d'urgence, nous restons disponibles et transparents.</p>
    </section>

    <section class="spaced-section">
        <h2>Comment venir depuis Sallanches ?</h2>
        <p>Depuis <strong>Sallanches (74700)</strong>, prenez la direction de Cluses par la <strong>D1205</strong> puis continuez jusqu'à Scionzier (environ 20 minutes). L'atelier Clinik Auto est situé au <strong>118 Clos des Teppes, 74950 Scionzier</strong>. Parking gratuit devant l'atelier.</p>
        <p style="margin-top:0.75rem;">Pour gagner du temps, pensez à <a href="rdv/rdv.php">réserver votre créneau en ligne</a> : vous choisissez la date et l'heure, nous vous confirmons rapidement.</p>
    </section>

    <section class="cta-section">
        <h2>Devis ou prise de rendez-vous</h2>
        <p>Vous habitez Sallanches ou les environs ? Obtenez un devis gratuit ou réservez votre passage dès maintenant.</p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;margin-top:1rem;">
            <a href="devis/devis.php" class="cta-link">Demande de devis gratuit</a>
            <a href="rdv/rdv.php" class="cta-link-secondary">Prendre rendez-vous</a>
            <a href="tel:+33620185627" class="cta-link-secondary">📞 06 20 18 56 27</a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Questions fréquentes – Garage proche Sallanches</h2>
        <div class="review-card">
            <p><strong>Gérez-vous les véhicules de montagne (4x4, SUV, chaînes) ?</strong></p>
            <p>Oui, nous accueillons tous types de véhicules incluant les SUV et les 4x4 courants. Pour les équipements spécifiques hiver (chaînes, pneus neige), n'hésitez pas à nous contacter pour vérifier nos disponibilités.</p>
            <p><strong>Pouvez-vous m'aider à trouver un véhicule adapté à la conduite en montagne ?</strong></p>
            <p>Absolument. Lors de votre demande de VO, précisez vos besoins (traction, hauteur de sol, etc.) et nous orienterons notre sélection en conséquence.</p>
            <p><strong>Faut-il être client régulier pour avoir un bon suivi ?</strong></p>
            <p>Tous nos clients bénéficient du même niveau d'attention. Nous conservons un historique de vos interventions pour un meilleur suivi sur la durée.</p>
        </div>
    </section>

    <section class="spaced-section" style="text-align:center">
        <h2>Autres villes desservies</h2>
        <p>Clinik Auto accueille aussi les clients de <strong>Cluses, Bonneville et Scionzier</strong>.</p>
        <p style="margin-top:0.8rem; display:flex; gap:0.6rem; justify-content:center; flex-wrap:wrap;">
            <a href="garage-cluses.php" class="cta-link-secondary">Garage proche Cluses</a>
            <a href="garage-bonneville.php" class="cta-link-secondary">Garage proche Bonneville</a>
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
