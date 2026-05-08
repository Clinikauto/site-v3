<?php
require_once dirname(__DIR__) . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

$service = strtolower(trim($_GET["service"] ?? ""));

$service_labels = [
    "revision" => "Révision",
    "reparation" => "Réparation",
    "vente" => "Nos services auto"
];

$service_label = $service_labels[$service] ?? "Toutes prestations";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Devis Gratuit | Révision & Réparation Auto – Clinik Auto Scionzier (74)</title>
    <meta name="description" content="Obtenez un devis gratuit pour la révision ou réparation de votre véhicule chez Clinik Auto à Scionzier (74950). Garage multimarque en Haute-Savoie. Réponse rapide.">
    <meta name="keywords" content="devis révision voiture Scionzier, devis réparation auto 74, tarif entretien véhicule Haute-Savoie, devis mécanique gratuit 74950, prix révision Cluses, devis auto Bonneville">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/devis/devis.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Devis Gratuit Révision & Réparation Auto | Clinik Auto Scionzier (74)">
    <meta property="og:description" content="Demandez un devis gratuit pour révision ou réparation auto chez Clinik Auto à Scionzier (74950). Garage multimarque Haute-Savoie.">
    <meta property="og:url" content="https://www.clinikauto.fr/devis/devis.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Devis Gratuit | Clinik Auto Scionzier (74)">
    <meta name="twitter:description" content="Devis gratuit révision & réparation auto chez Clinik Auto, Scionzier (74950). Réponse rapide.">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://www.clinikauto.fr/"},
        {"@type": "ListItem", "position": 2, "name": "Devis", "item": "https://www.clinikauto.fr/devis/devis.php"}
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
                <li><a href="../catalogue/catalogue.php">Catalogue</a></li>
                <li><a href="../contact/contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Demande de devis</h2>
        <p>Service sélectionné : <strong><?php echo htmlspecialchars($service_label); ?></strong></p>
        <p class="form-note">Cochez une ou plusieurs prestations. Le compteur s'incrémente automatiquement selon vos choix.</p>

        <form id="devis-form" class="quote-form" method="get" action="../contact/contact.php">
            <input type="hidden" name="prestations" id="prestations-hidden" value="">
            <input type="hidden" name="source_service" value="<?php echo htmlspecialchars($service_label); ?>">

            <div class="selection-panel">
                <span class="counter-badge">Prestations sélectionnées : <strong id="selected-count">0</strong></span>
            </div>

            <h3>Révision</h3>
            <div class="checklist-grid">
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Vidange moteur">Vidange moteur</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Changement filtre à huile">Changement filtre à huile</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Contrôle freins">Contrôle freins</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Contrôle batterie">Contrôle batterie</label>
            </div>

            <h3>Réparation</h3>
            <div class="checklist-grid">
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Diagnostic électronique">Diagnostic électronique</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Réparation embrayage">Réparation embrayage</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Réparation suspension">Réparation suspension</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Réparation climatisation">Réparation climatisation</label>
            </div>

            <h3>Nos services auto</h3>
            <div class="checklist-grid">
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Recherche véhicule d'occasion">Recherche véhicule d'occasion</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Contrôle avant achat">Contrôle avant achat</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Enlèvement de véhicule hors d'usage">Enlèvement de véhicule hors d'usage</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Contrôle Technique">Contrôle Technique</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Pré-Contrôle Technique">Pré-Contrôle Technique</label>
                <label class="check-item"><input type="checkbox" class="prestation-checkbox" value="Remorquage">Remorquage</label>
            </div>

            <div class="devis-form-actions">
                <button type="submit" id="devis-submit-btn">Valider votre demande de devis →</button>
                <button type="button" id="devis-continue-btn" class="btn-secondary">Continuer la navigation →</button>
            </div>
            <p class="form-note" id="devis-saved-notice" hidden>
                Des prestations d'un autre service sont déjà mémorisées et seront incluses lors de la validation.
            </p>
        </form>
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
    </footer>

    <script>
    (function () {
        var STORAGE_KEY = 'clinikauto_devis_prestations_v1';

        var form = document.getElementById('devis-form');
        var checkboxes = Array.from(document.querySelectorAll('.prestation-checkbox'));
        var countTarget = document.getElementById('selected-count');
        var hiddenInput = document.getElementById('prestations-hidden');
        var continueBtn = document.getElementById('devis-continue-btn');
        var savedNotice = document.getElementById('devis-saved-notice');

        /* --- Lecture / écriture localStorage --- */
        function loadSaved() {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                return raw ? JSON.parse(raw) : [];
            } catch (e) { return []; }
        }

        function saveCurrent(values) {
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(values)); } catch (e) {}
        }

        /* --- Fusionne les cases cochées actuellement + celles en mémoire --- */
        function mergedSelections() {
            var checked = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
            var saved = loadSaved();
            var all = saved.slice();
            checked.forEach(function (v) {
                if (all.indexOf(v) === -1) { all.push(v); }
            });
            return all;
        }

        /* --- Met à jour le compteur et l'input caché (cases actuelles seulement) --- */
        function updateCounter() {
            var checked = checkboxes.filter(function (cb) { return cb.checked; });
            countTarget.textContent = String(checked.length);
        }

        /* --- Restaure les cases depuis localStorage --- */
        function restoreCheckboxes() {
            var saved = loadSaved();
            if (saved.length === 0) { return; }
            var restored = 0;
            checkboxes.forEach(function (cb) {
                if (saved.indexOf(cb.value) !== -1) {
                    cb.checked = true;
                    restored++;
                }
            });
            /* Affiche le badge si des prestations viennent d'une autre page */
            var savedExternal = saved.filter(function (v) {
                return !checkboxes.some(function (cb) { return cb.value === v; });
            });
            if (savedExternal.length > 0 && savedNotice) {
                savedNotice.hidden = false;
            }
        }

        /* --- Sauvegarde à chaque changement de case --- */
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                updateCounter();
                saveCurrent(mergedSelections());
            });
        });

        /* --- Bouton "Continuer la navigation" --- */
        if (continueBtn) {
            continueBtn.addEventListener('click', function () {
                saveCurrent(mergedSelections());
                window.location.href = '../index.html';
            });
        }

        /* --- Soumission : injecte TOUTES les prestations accumulées --- */
        form.addEventListener('submit', function (event) {
            var all = mergedSelections();
            hiddenInput.value = all.join(', ');
            if (!hiddenInput.value) {
                event.preventDefault();
                alert('Veuillez sélectionner au moins une prestation pour continuer.');
                return;
            }
            /* Vide le localStorage après validation pour repartir de zéro */
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        });

        /* --- Init --- */
        restoreCheckboxes();
        updateCounter();
    })();
    </script>
</body>
</html>