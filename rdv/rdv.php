<?php
require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/includes/catalog_store.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('rdv');

$rdvStructuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebPage',
            '@id' => 'https://www.clinikauto.fr/rdv/rdv.php#webpage',
            'url' => 'https://www.clinikauto.fr/rdv/rdv.php',
            'name' => 'Prendre rendez-vous | Clinik Auto',
            'description' => 'Réservation de rendez-vous pour entretien, diagnostic, révision ou retrait chez Clinik Auto.',
            'about' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ]
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://www.clinikauto.fr/rdv/rdv.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://www.clinikauto.fr/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Rendez-vous', 'item' => 'https://www.clinikauto.fr/rdv/rdv.php']
            ]
        ],
        [
            '@type' => 'Service',
            'name' => 'Prise de rendez-vous garage',
            'serviceType' => 'Rendez-vous atelier automobile',
            'provider' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ],
            'availableChannel' => [
                '@type' => 'ServiceChannel',
                'serviceUrl' => 'https://www.clinikauto.fr/rdv/rdv.php'
            ],
            'areaServed' => 'Haute-Savoie'
        ]
    ]
];

date_default_timezone_set("Europe/Paris");

function get_easter_date(int $year): DateTimeImmutable
{
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return new DateTimeImmutable(sprintf("%04d-%02d-%02d", $year, $month, $day));
}

function get_holidays_for_year(int $year): array
{
    $easter = get_easter_date($year);
    $fixed = [
        sprintf("%04d-01-01", $year),
        sprintf("%04d-05-01", $year),
        sprintf("%04d-05-08", $year),
        sprintf("%04d-07-14", $year),
        sprintf("%04d-08-15", $year),
        sprintf("%04d-11-01", $year),
        sprintf("%04d-11-11", $year),
        sprintf("%04d-12-25", $year),
    ];

    $moving = [
        $easter->modify("+1 day")->format("Y-m-d"),   // Lundi de Paques
        $easter->modify("+39 days")->format("Y-m-d"), // Ascension
        $easter->modify("+50 days")->format("Y-m-d"), // Lundi de Pentecote
    ];

    return array_merge($fixed, $moving);
}

function is_closed_day(string $date): bool
{
    $day = DateTimeImmutable::createFromFormat("Y-m-d", $date);
    if (!$day) {
        return true;
    }

    $year = (int) $day->format("Y");
    $holidays = get_holidays_for_year($year);
    $is_sunday = $day->format("N") === "7";
    return $is_sunday || in_array($date, $holidays, true);
}

function get_slots_for_date(string $date): array
{
    if (is_closed_day($date)) {
        return [];
    }

    $day = DateTimeImmutable::createFromFormat("Y-m-d", $date);
    if (!$day) {
        return [];
    }

    $weekday = $day->format("N");
    if ($weekday === "6") {
        return ["09:00", "10:00", "11:00"];
    }

    return ["09:00", "10:00", "11:00", "14:00", "15:00", "16:00", "17:00"];
}

$message = "";
$error_message = "";
$recognized_message = "";
$recognized_incomplete_message = "";
$prefill_notice = trim((string) ($_SESSION['catalog_contact_success_notice'] ?? ''));
unset($_SESSION['catalog_contact_success_notice']);

function rdv_customer_type($value)
{
    return $value === 'professional' ? 'professional' : 'individual';
}

function rdv_identity_label($customerType, $field)
{
    $isProfessional = rdv_customer_type($customerType) === 'professional';
    if ($field === 'nom') {
        return $isProfessional ? 'Raison sociale' : 'Nom';
    }
    if ($field === 'prenom') {
        return $isProfessional ? 'Nom du contact' : 'Prenom';
    }
    return $field;
}
$tomorrow = (new DateTimeImmutable("tomorrow"))->format("Y-m-d");
$form = [
    "customer_type" => "individual",
    "nom"       => "",
    "prenom"    => "",
    "adresse"   => "",
    "code_postal" => "",
    "ville"     => "",
    "telephone" => "",
    "email"     => "",
    "date"      => "",
    "heure"     => "",
    "objet"     => "",
    "request_context_type" => "",
    "linked_request_id" => "",
    "linked_annonce_id" => "",
    "linked_title" => "",
];

foreach (["customer_type", "nom", "prenom", "adresse", "code_postal", "ville", "telephone", "email", "objet", "request_context_type", "linked_request_id", "linked_annonce_id", "linked_title"] as $prefill_key) {
    if (isset($_GET[$prefill_key]) && $form[$prefill_key] === "") {
        $form[$prefill_key] = trim((string) $_GET[$prefill_key]);
    }
}
$form['customer_type'] = rdv_customer_type($form['customer_type']);

$identity_from_cookie = trim((string) ($_COOKIE[catalog_identity_cookie_name()] ?? ""));
$known_profile = catalog_get_customer_profile([
    "email" => trim((string) ($form["email"] !== "" ? $form["email"] : $identity_from_cookie)),
    "phone" => trim((string) $form["telephone"]),
    "registration" => ""
]);
if (is_array($known_profile)) {
    $form["customer_type"] = rdv_customer_type((string) ($known_profile["customer_type"] ?? $form["customer_type"]));
    if ($form["nom"] === "") {
        $form["nom"] = (string) ($known_profile["lastname"] ?? "");
    }
    if ($form["prenom"] === "") {
        $form["prenom"] = (string) ($known_profile["firstname"] ?? "");
    }
    if ($form["telephone"] === "") {
        $form["telephone"] = (string) ($known_profile["phone"] ?? "");
    }
    if ($form["email"] === "") {
        $form["email"] = (string) ($known_profile["email"] ?? "");
    }
    if ($form["adresse"] === "") {
        $form["adresse"] = (string) ($known_profile["address_line"] ?? "");
    }
    if ($form["code_postal"] === "") {
        $form["code_postal"] = (string) ($known_profile["postal_code"] ?? "");
    }
    if ($form["ville"] === "") {
        $form["ville"] = (string) ($known_profile["city"] ?? "");
    }
    $welcome_firstname = trim((string) ($known_profile["firstname"] ?? ""));
    if ($welcome_firstname === '') {
        $welcome_firstname = trim((string) ($form["prenom"] ?? ''));
    }
    if ($welcome_firstname !== '') {
        $recognized_message = "Bonjour " . $welcome_firstname . ", bienvenue chez Clinik Auto. Que pouvons-nous faire pour vous ?";
    }

    $missingForKnownProfile = [];
    foreach (["nom", "prenom", "telephone", "adresse"] as $requiredField) {
        if (trim((string) ($form[$requiredField] ?? '')) === '') {
            $missingForKnownProfile[] = rdv_identity_label($form['customer_type'], $requiredField);
        }
    }
    if (!empty($missingForKnownProfile)) {
        $recognized_incomplete_message = "Profil reconnu mais incomplet. Merci de compléter: " . implode(', ', $missingForKnownProfile) . ".";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? "");
    }
    $form['customer_type'] = rdv_customer_type($form['customer_type']);

    $required = ["nom", "prenom", "telephone", "email", "date", "heure", "objet"];
    $missing  = array_filter($required, fn($f) => $form[$f] === "");

    if (!empty($missing)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email saisie n'est pas valide.";
    } elseif ((function_exists('mb_strlen') ? mb_strlen($form["objet"]) : strlen($form["objet"])) > 500) {
            $error_message = "L'objet de votre demande ne doit pas dépasser 500 caractères.";
    } else {
        $selected_day = DateTimeImmutable::createFromFormat("Y-m-d", $form["date"]);
            if (!$selected_day) {
                $error_message = "La date sélectionnée est invalide.";
            } elseif ($form["date"] < $tomorrow) {
                $error_message = "La date de rendez-vous doit être au minimum le lendemain.";
            } elseif (is_closed_day($form["date"])) {
                $error_message = "Le garage est fermé à cette date (dimanche ou jour férié). Choisissez une autre date.";
        } else {
            $allowed_slots = get_slots_for_date($form["date"]);
            if (!in_array($form["heure"], $allowed_slots, true)) {
                    $error_message = "Le créneau choisi ne correspond pas aux horaires d'ouverture.";
            }
        }
    }

    if ($error_message === "") {
        if (defined("DB_HOST") && defined("DB_USER") && defined("DB_PASS") && defined("DB_NAME")) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $dbPort = defined('DB_PORT') ? (int) DB_PORT : 3306;
            $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $dbPort);
            if (!$conn->connect_error) {
                $conn->set_charset("utf8mb4");
                $service = $form["objet"];
                $requestContextType = trim((string) $form['request_context_type']);
                $linkedAnnonceId = (int) ($form['linked_annonce_id'] !== '' ? $form['linked_annonce_id'] : 0);
                $linkedAnnonceValue = $linkedAnnonceId > 0 ? $linkedAnnonceId : null;
                $linkedRequestId = trim((string) $form['linked_request_id']);
                $stmt = $conn->prepare("INSERT INTO rendez_vous (nom, email, telephone, address_line, postal_code, city, date, heure, service, status, request_context_type, linked_annonce_id, linked_request_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'En attente', ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssssssssis", $form["nom"], $form["email"], $form["telephone"], $form["adresse"], $form["code_postal"], $form["ville"], $form["date"], $form["heure"], $service, $requestContextType, $linkedAnnonceValue, $linkedRequestId);
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->close();
            }
        }

        catalog_save_customer_profile([
            "customer_type" => $form["customer_type"],
            "firstname" => $form["prenom"],
            "lastname" => $form["nom"],
            "address_line" => $form["adresse"],
            "postal_code" => $form["code_postal"],
            "city" => $form["ville"],
            "email" => $form["email"],
            "phone" => $form["telephone"],
            "registration" => ""
        ], "rdv");

        if ($form["email"] !== "" && filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {
            setcookie(catalog_identity_cookie_name(), strtolower(trim((string) $form["email"])), time() + 31536000, "/", "", false, true);
        }

        if (defined('GOOGLE_CALENDAR_ENABLED') && GOOGLE_CALENDAR_ENABLED) {
            catalog_google_calendar_sync_bidirectional(true);
        }

        $nom = htmlspecialchars($form["nom"]);
        $prenom = htmlspecialchars($form["prenom"]);
        $identityMessage = $prenom . ' ' . $nom;
        if ($form['customer_type'] === 'professional') {
            $identityMessage = $nom . ' (contact: ' . $prenom . ')';
        }
        $message = "Merci $identityMessage, votre demande de rendez-vous a bien été prise en compte. Nous vous confirmons votre créneau dans les meilleurs délais.";
        $form = array_fill_keys(array_keys($form), "");
        $form['customer_type'] = 'individual';
    }
}

function ev($v) { return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"); }
$holidays_this_year = get_holidays_for_year((int) date("Y"));
$holidays_next_year = get_holidays_for_year((int) date("Y") + 1);
$all_holidays = array_values(array_unique(array_merge($holidays_this_year, $holidays_next_year)));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous Garage Scionzier (74950) | Révision & Entretien en Ligne | Clinik Auto</title>
    <meta name="description" content="Réservez votre rendez-vous en ligne chez Clinik Auto, garage à Scionzier (74950). Révision, entretien, réparation – créneaux disponibles en temps réel. Proche Cluses & Bonneville.">
    <meta name="keywords" content="rendez-vous garage Scionzier, prise RDV mécanicien 74, réserver révision voiture Haute-Savoie, rendez-vous entretien auto 74950, garage Cluses RDV en ligne">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/rdv/rdv.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Prendre Rendez-vous | Clinik Auto Scionzier (74)">
    <meta property="og:description" content="Réservez votre créneau en ligne chez Clinik Auto à Scionzier (74950). Disponibilités en temps réel, réponse rapide garantie.">
    <meta property="og:url" content="https://www.clinikauto.fr/rdv/rdv.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Prendre Rendez-vous | Clinik Auto Scionzier (74)">
    <meta name="twitter:description" content="Réservez votre créneau en ligne chez Clinik Auto, Scionzier (74950). Réponse rapide garantie.">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/postal-city.js" defer></script>
    <script src="../assets/customer-type.js" defer></script>
    <script type="application/ld+json">
        <?php echo json_encode($rdvStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <?php echo catalog_get_google_analytics_script(); ?>
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
                <li><a href="../catalogue/catalogue.php">Catalogue</a></li>
                <li><a href="../contact/contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Prendre rendez-vous au garage</h1>
        <p>Remplissez le formulaire ci-dessous, nous vous confirmons votre créneau dans les meilleurs délais.</p>
        <?php if ($prefill_notice !== '') { echo "<p class='success-message'>" . htmlspecialchars($prefill_notice) . "</p>"; } ?>
        <?php if ($message)       { echo "<p class='success-message'>" . htmlspecialchars($message) . "</p>"; } ?>
        <?php if ($error_message) { echo "<p class='error-message'>"   . htmlspecialchars($error_message) . "</p>"; } ?>
        <?php if ($recognized_message !== '') { echo "<p class='success-message'>" . htmlspecialchars($recognized_message) . "</p>"; } ?>
        <?php if ($recognized_incomplete_message !== '') { echo "<p class='error-message'>" . htmlspecialchars($recognized_incomplete_message) . "</p>"; } ?>
        <form method="post">
            <div data-customer-type-context>
            <input type="hidden" name="customer_type" value="<?php echo ev($form['customer_type']); ?>" data-customer-type-input>
            <input type="hidden" name="request_context_type" value="<?php echo ev($form['request_context_type']); ?>">
            <input type="hidden" name="linked_request_id" value="<?php echo ev($form['linked_request_id']); ?>">
            <input type="hidden" name="linked_annonce_id" value="<?php echo ev($form['linked_annonce_id']); ?>">
            <input type="hidden" name="linked_title" value="<?php echo ev($form['linked_title']); ?>">
            <?php if ($form['linked_title'] !== ''): ?>
                <p class="form-helper">Demande liée : <?php echo ev($form['linked_title']); ?></p>
            <?php endif; ?>
            <label class="checkbox-toggle">
                <input type="checkbox" value="1" data-customer-type-checkbox <?php echo $form['customer_type'] === 'professional' ? 'checked' : ''; ?>>
                Je remplis ce formulaire en tant que professionnel
            </label>
            <label><span data-type-label-target data-individual-label="Nom" data-professional-label="Raison sociale"><?php echo ev(rdv_identity_label($form['customer_type'], 'nom')); ?></span>
                <input type="text" name="nom" placeholder="Votre nom complet" data-type-placeholder-target data-individual-placeholder="Votre nom complet" data-professional-placeholder="Raison sociale de l'entreprise" value="<?php echo ev($form['nom']); ?>" required>
            </label>
            <label><span data-type-label-target data-individual-label="Prénom" data-professional-label="Nom du contact"><?php echo ev(rdv_identity_label($form['customer_type'], 'prenom')); ?></span>
                <input type="text" name="prenom" placeholder="Votre prénom" data-type-placeholder-target data-individual-placeholder="Votre prénom" data-professional-placeholder="Nom et prénom du contact" value="<?php echo ev($form['prenom']); ?>" required>
            </label>
            <label>Téléphone
                <input type="tel" name="telephone" placeholder="06 12 34 56 78" value="<?php echo ev($form['telephone']); ?>" required>
            </label>
            <label>Email
                <input type="email" name="email" placeholder="votre@email.fr" value="<?php echo ev($form['email']); ?>" required>
            </label>
            <label>Adresse
                <input type="text" name="adresse" placeholder="Votre adresse complète" value="<?php echo ev($form['adresse']); ?>">
            </label>
            <div data-postal-city-group data-postal-endpoint="../postal_lookup.php">
                <label>Code postal
                    <input type="text" name="code_postal" inputmode="numeric" maxlength="10" placeholder="74950" value="<?php echo ev($form['code_postal']); ?>" data-postal-code-input>
                </label>
                <label>Ville
                    <input type="text" name="ville" list="rdv-postal-city-list" placeholder="Scionzier" value="<?php echo ev($form['ville']); ?>" data-city-input>
                </label>
                <datalist id="rdv-postal-city-list"></datalist>
                <p class="form-helper" data-postal-city-status></p>
            </div>
            <label>Date souhaitée
                <input type="date" id="date_rdv" name="date" value="<?php echo ev($form['date']); ?>" min="<?php echo $tomorrow; ?>" required>
            </label>
            <label>Heure souhaitée
                <select id="heure_rdv" name="heure" required>
                    <option value="" disabled <?php echo $form['heure'] === '' ? 'selected' : ''; ?>>Choisissez un créneau&hellip;</option>
                </select>
            </label>
            <label>Objet de votre demande
                <textarea name="objet" rows="4" placeholder="Décrivez l'objet de votre demande (500 caractères max)" maxlength="500" required><?php echo ev($form['objet']); ?></textarea>
            </label>
            <button type="submit">Confirmer mon rendez-vous →</button>
            <p class="form-note">La demande est sans engagement: nous validons votre creneau avec vous avant confirmation finale.</p>
            </div>
        </form>
        <script>
        (function () {
            const dateInput = document.getElementById('date_rdv');
            const hourSelect = document.getElementById('heure_rdv');
            const selectedHour = <?php echo json_encode($form['heure']); ?>;
            const minDate = <?php echo json_encode($tomorrow); ?>;
            const holidays = new Set(<?php echo json_encode($all_holidays); ?>);

            const weekdaySlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];
            const saturdaySlots = ['09:00', '10:00', '11:00'];

            function isClosed(dateStr) {
                if (!dateStr) return true;
                const d = new Date(dateStr + 'T00:00:00');
                const day = d.getDay();
                return day === 0 || holidays.has(dateStr);
            }

            function slotsFor(dateStr) {
                if (!dateStr || isClosed(dateStr)) return [];
                const d = new Date(dateStr + 'T00:00:00');
                return d.getDay() === 6 ? saturdaySlots : weekdaySlots;
            }

            function refillHours() {
                const selectedDate = dateInput.value;
                const slots = slotsFor(selectedDate);
                const previous = hourSelect.value || selectedHour;

                hourSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.disabled = true;
                placeholder.selected = true;
                if (selectedDate && isClosed(selectedDate)) {
                    placeholder.textContent = 'Date indisponible (dimanche ou jour férié)';
                } else if (selectedDate && slots.length === 0) {
                    placeholder.textContent = 'Aucun créneau disponible';
                } else {
                    placeholder.textContent = 'Choisissez un créneau…';
                }
                hourSelect.appendChild(placeholder);

                slots.forEach(function (slot) {
                    const opt = document.createElement('option');
                    opt.value = slot;
                    opt.textContent = slot.replace(':', 'h');
                    if (slot === previous) {
                        opt.selected = true;
                        placeholder.selected = false;
                    }
                    hourSelect.appendChild(opt);
                });
            }

            dateInput.min = minDate;
            if (dateInput.value && dateInput.value < minDate) {
                dateInput.value = '';
            }

            dateInput.addEventListener('change', refillHours);
            refillHours();
        })();
        </script>
        <script src="../assets/conversion-tracking.js" defer></script>
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