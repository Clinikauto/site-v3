<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Politique cookies | Clinik Auto</title>
    <link rel="canonical" href="https://www.clinikauto.fr/politique-cookies.php">
    <link rel="stylesheet" href="assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
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
                <li><a href="contact/contact.php">Contact</a></li>
                <li><a href="rdv/rdv.php">Rendez-vous</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Politique cookies</h2>
            <p>Cette page explique l'utilisation des cookies sur le site Clinik Auto.</p>
        </section>

        <section class="spaced-section">
            <h3>1. Cookies utilises</h3>
            <p>Nous utilisons des cookies techniques necessaires au fonctionnement du site et, si vous l'acceptez, des cookies de mesure d'audience (Google Analytics).</p>

            <h3>2. Finalite</h3>
            <p>Les cookies de mesure d'audience nous aident a comprendre la frequentation du site et a ameliorer son contenu.</p>

            <h3>3. Consentement</h3>
            <p>Vous pouvez accepter ou refuser les cookies d'audience depuis la banniere cookies. Vous pouvez modifier ce choix a tout moment via le bouton Cookies en bas de page.</p>

            <h3>4. Duree de conservation</h3>
            <p>Le choix de consentement est conserve localement dans votre navigateur.</p>

            <h3>5. Contact</h3>
            <p>Pour toute question: <a href="mailto:clinikauto74@gmail.com">clinikauto74@gmail.com</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Clinik Auto. Tous droits reserves.</p>
    </footer>
</body>
</html>
