<?php
require_once __DIR__ . '/includes/catalog_store.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$showAdminReturn = catalog_is_admin_session_active();
catalog_track_visit('local_revision_scionzier');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Révision Auto Scionzier (74950) | Entretien & Vidange | Clinik Auto</title>
    <meta name="description" content="Révision auto à Scionzier (74950) chez Clinik Auto : vidange, filtres, freins, distribution, contrôle complet. Devis gratuit en ligne – 06 20 18 56 27. Ouvert du lundi au samedi.">
    <meta name="keywords" content="révision auto Scionzier, révision voiture 74950, entretien auto Scionzier, vidange Scionzier, révision constructeur Scionzier, freinage Scionzier, distribution voiture 74950, garage révision Haute-Savoie">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <meta name="geo.position" content="46.0608;6.5394">
    <link rel="canonical" href="https://www.clinikauto.fr/revision-scionzier.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Révision Auto Scionzier (74950) | Clinik Auto">
    <meta property="og:description" content="Révision, entretien et vidange auto à Scionzier. Clinik Auto – garage multimarque. Devis gratuit – 06 20 18 56 27.">
    <meta property="og:url" content="https://www.clinikauto.fr/revision-scionzier.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="assets/style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "BreadcrumbList",
                "itemListElement": [
                    { "@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://www.clinikauto.fr/" },
                    { "@type": "ListItem", "position": 2, "name": "Révision auto Scionzier", "item": "https://www.clinikauto.fr/revision-scionzier.php" }
                ]
            },
            {
                "@type": "AutoRepair",
                "@id": "https://www.clinikauto.fr/#garage",
                "name": "Clinik Auto",
                "description": "Garage automobile à Scionzier (74950) spécialisé dans la révision, l'entretien et la réparation multimarque en Haute-Savoie.",
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
                    { "@type": "City", "name": "Scionzier" },
                    { "@type": "City", "name": "Cluses" },
                    { "@type": "City", "name": "Bonneville" },
                    { "@type": "City", "name": "Sallanches" }
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
                "@type": "Service",
                "serviceType": "Révision automobile",
                "provider": { "@id": "https://www.clinikauto.fr/#garage" },
                "name": "Révision auto Scionzier",
                "description": "Révision complète ou partielle de votre véhicule à Scionzier (74950) : vidange, filtres, freins, distribution, contrôle général.",
                "areaServed": { "@type": "City", "name": "Scionzier" },
                "offers": {
                    "@type": "Offer",
                    "priceCurrency": "EUR",
                    "description": "Devis gratuit sur demande"
                }
            },
            {
                "@type": "WebPage",
                "url": "https://www.clinikauto.fr/revision-scionzier.php",
                "name": "Révision auto à Scionzier (74950) – Clinik Auto",
                "description": "Révision, vidange et entretien auto à Scionzier chez Clinik Auto. Devis gratuit en ligne.",
                "isPartOf": { "@id": "https://www.clinikauto.fr/#website" },
                "about": { "@id": "https://www.clinikauto.fr/#garage" }
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
            <img class="site-logo" src="assets/logo.png" alt="Logo Clinik Auto">
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

<main class="page-main-hero">
    <section>
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb-list">
                <li><a href="index.html">Accueil</a></li>
                <li aria-current="page">Révision auto Scionzier</li>
            </ol>
        </nav>
        <span class="hero-badge">🔧 Révision & Entretien</span>
        <h1>Révision auto à Scionzier (74950) – Clinik Auto</h1>
        <p>Clinik Auto réalise la <strong>révision de votre véhicule à Scionzier</strong> : vidange, remplacement des filtres, contrôle des freins, vérification de la distribution et bilan complet de l'état mécanique. Garage indépendant multimarque, nous garantissons un travail soigné à un tarif juste et transparent.</p>
    </section>

    <section class="spaced-section">
        <h2>Que comprend une révision chez Clinik Auto ?</h2>
        <div class="services-grid">
            <div class="service-card">
                <span class="service-icon">🛢️</span>
                <h3>Vidange & Filtres</h3>
                <p>Vidange huile moteur, remplacement du filtre à huile, filtre à air, filtre d'habitacle et filtre à carburant si nécessaire.</p>
            </div>
            <div class="service-card">
                <span class="service-icon">🛑</span>
                <h3>Freinage</h3>
                <p>Contrôle et remplacement des plaquettes, disques et liquide de frein. Vérification du frein à main.</p>
            </div>
            <div class="service-card">
                <span class="service-icon">⚙️</span>
                <h3>Distribution</h3>
                <p>Inspection ou remplacement de la courroie de distribution selon le kilométrage et les préconisations constructeur.</p>
            </div>
            <div class="service-card">
                <span class="service-icon">🔍</span>
                <h3>Bilan complet</h3>
                <p>Contrôle de la direction, des suspensions, des pneumatiques, de la batterie et du niveau des liquides (refroidissement, freins, direction assistée).</p>
            </div>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Révision : à quelle fréquence ?</h2>
        <p>La fréquence de révision dépend de votre constructeur et de votre kilométrage. En règle générale :</p>
        <ul class="reasons-list" style="margin-top:0.75rem">
            <li>✓ <strong>Vidange :</strong> tous les 10 000 à 15 000 km (selon l'huile et le véhicule)</li>
            <li>✓ <strong>Révision petite :</strong> tous les 15 000 à 20 000 km</li>
            <li>✓ <strong>Révision grande :</strong> tous les 30 000 à 60 000 km</li>
            <li>✓ <strong>Distribution :</strong> entre 60 000 et 120 000 km selon la marque</li>
        </ul>
        <p style="margin-top:1rem;">En cas de doute, apportez simplement votre carnet d'entretien lors de votre passage : nous vous conseillons sur les opérations prioritaires et vous proposons un devis sans engagement.</p>
    </section>

    <section class="spaced-section">
        <h2>Pourquoi choisir Clinik Auto pour votre révision à Scionzier ?</h2>
        <ul class="reasons-list">
            <li>✓ Garage indépendant : tarifs inférieurs aux réseaux franchisés</li>
            <li>✓ Devis gratuit et transparent avant toute intervention</li>
            <li>✓ Toutes marques : Peugeot, Renault, Citroën, Ford, Toyota, Volkswagen, BMW…</li>
            <li>✓ Pièces de qualité (origine ou équivalent constructeur)</li>
            <li>✓ Accueil du lundi au vendredi + samedi matin</li>
            <li>✓ Historique de vos interventions conservé pour un meilleur suivi</li>
        </ul>
    </section>

    <section class="cta-section">
        <h2>Demandez un devis de révision</h2>
        <p>Indiquez votre véhicule et le type d'entretien souhaité : nous vous répondons dans les meilleurs délais avec un devis détaillé.</p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;margin-top:1rem;">
            <a href="devis/devis.php?service=revision" class="cta-link">Devis révision gratuit</a>
            <a href="rdv/rdv.php" class="cta-link-secondary">Prendre rendez-vous</a>
            <a href="tel:+33620185627" class="cta-link-secondary">📞 06 20 18 56 27</a>
        </div>
    </section>

    <section class="spaced-section">
        <h2>Questions fréquentes – Révision à Scionzier</h2>
        <div class="review-card">
            <p><strong>Combien de temps dure une révision ?</strong></p>
            <p>Une révision simple (vidange + filtres + contrôle) dure généralement entre 1h et 2h. Une révision complète incluant la distribution peut prendre une journée. Nous vous informons du délai lors de la prise de rendez-vous.</p>
            <p><strong>Peut-on attendre sur place pendant la révision ?</strong></p>
            <p>Oui, nous disposons d'un espace d'attente. Pour les interventions longues, certains clients préfèrent déposer leur véhicule le matin et le récupérer en fin de journée.</p>
            <p><strong>Utilisez-vous des pièces d'origine ?</strong></p>
            <p>Nous utilisons des pièces d'origine constructeur ou des pièces de qualité équivalente (marques homologuées). Nous vous précisons toujours l'origine des pièces dans le devis.</p>
            <p><strong>La révision chez vous annule-t-elle la garantie constructeur ?</strong></p>
            <p>Non. Depuis le règlement européen d'exemption par catégorie, vous pouvez faire entretenir votre véhicule sous garantie dans n'importe quel garage indépendant agréé, à condition d'utiliser des pièces de qualité équivalente. Clinik Auto respecte ces exigences.</p>
        </div>
    </section>

    <section class="spaced-section" style="text-align:center">
        <h2>Nous intervenons aussi près de chez vous</h2>
        <p>Clinik Auto est accessible depuis toute la vallée de l'Arve.</p>
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
</body>
</html>
