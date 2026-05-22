<?php
require_once dirname(__DIR__) . "/config.php";
require_once dirname(__DIR__) . "/includes/catalog_store.php";
require_once dirname(__DIR__) . "/includes/security.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF: init and validate POST (tolerated in local development)
csrf_init();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_validate_request() && !CATALOG_IS_LOCAL_RUNTIME) {
        http_response_code(400);
        echo 'Requête invalide (CSRF)';
        exit;
    }
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('contact');

$contactStructuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'ContactPage',
            '@id' => 'https://www.clinikauto.fr/contact/contact.php#webpage',
            'url' => 'https://www.clinikauto.fr/contact/contact.php',
            'name' => 'Contact Clinik Auto',
            'description' => 'Coordonnées, formulaire de contact et demande de devis Clinik Auto à Scionzier.',
            'about' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ]
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://www.clinikauto.fr/contact/contact.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://www.clinikauto.fr/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Contact', 'item' => 'https://www.clinikauto.fr/contact/contact.php']
            ]
        ],
        [
            '@type' => 'AutoRepair',
            '@id' => 'https://www.clinikauto.fr/#garage',
            'name' => 'Clinik Auto',
            'url' => 'https://www.clinikauto.fr/',
            'telephone' => '+33620185627',
            'email' => 'clinikauto74@gmail.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '118 Clos des Teppes',
                'postalCode' => '74950',
                'addressLocality' => 'Scionzier',
                'addressCountry' => 'FR'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => '+33620185627',
                'email' => 'clinikauto74@gmail.com',
                'areaServed' => 'FR',
                'availableLanguage' => ['fr']
            ]
        ]
    ]
];

if (defined("COMPOSER_AUTOLOAD_PATH") && file_exists(COMPOSER_AUTOLOAD_PATH)) {
    require_once COMPOSER_AUTOLOAD_PATH;
}

function send_devis_email($destinataire, $safe_sender, $email_subject, $email_body)
{
    return catalog_send_email($destinataire, $email_subject, $email_body, $safe_sender);
}

function contact_request_context($form_data)
{
    $action = trim((string) ($form_data["contact_action"] ?? ""));

    if ($action === "vehicle_visit") {
        return "Réservation visite véhicule";
    }

    if ($action === "part_reservation") {
        if (($form_data["acompte_confirme"] ?? "") === "1") {
            return "Réservation pièce - acompte confirmé";
        }
        return "Réservation pièce d'occasion";
    }

    if (!empty($form_data["prestations"] ?? "")) {
        return "Demande de devis";
    }

    return "Demande de contact";
}

function contact_customer_type($value)
{
    return $value === 'professional' ? 'professional' : 'individual';
}

function contact_identity_label($customerType, $field)
{
    $isProfessional = contact_customer_type($customerType) === 'professional';
    if ($field === 'nom') {
        return $isProfessional ? 'Raison sociale' : 'Nom';
    }
    if ($field === 'prenom') {
        return $isProfessional ? 'Nom du contact' : 'Prenom';
    }
    return $field;
}

$message = "";
$error_message = "";
$is_review = false;
$recognized_message = "";
$recognized_incomplete_message = "";

$form_data = [
    "customer_type" => "individual",
    "nom" => "",
    "prenom" => "",
    "adresse" => "",
    "code_postal" => "",
    "ville" => "",
    "email" => "",
    "telephone" => "",
    "immatriculation" => "",
    "sans_vehicule" => "",
    "contact_action" => "",
    "annonce_id" => "",
    "annonce_type" => "",
    "annonce_title" => "",
    "annonce_price" => "",
    "date_essai" => "",
    "acompte_montant" => "",
    "acompte_confirme" => "",
    "virement_compte_id" => "",
    "virement_confirme_client" => "",
    "selected_parts_count" => "",
    "selected_parts_ids" => "",
    "selected_parts_titles" => "",
    "acompte_total" => "",
    "selected_vehicles_count" => "",
    "selected_vehicles_ids" => "",
    "selected_vehicles_titles" => "",
    "sujet" => "",
    "prestations" => "",
    "message" => ""
];

$prestations_query = trim($_GET["prestations"] ?? "");
$source_service_query = trim($_GET["source_service"] ?? "");
$subject_query = trim($_GET["sujet"] ?? "");
$message_query = trim($_GET["message"] ?? "");
$contact_action_query = trim($_GET["contact_action"] ?? "");
$annonce_id_query = trim($_GET["annonce_id"] ?? "");
$annonce_type_query = trim($_GET["annonce_type"] ?? "");
$annonce_title_query = trim($_GET["annonce_title"] ?? "");
$annonce_price_query = trim($_GET["annonce_price"] ?? "");
$acompte_montant_query = trim($_GET["acompte_montant"] ?? "");
$acompte_confirme_query = trim($_GET["acompte_confirme"] ?? "");
$virement_compte_id_query = trim($_GET["virement_compte_id"] ?? "");
$virement_confirme_client_query = trim($_GET["virement_confirme_client"] ?? "");
$selected_parts_count_query = trim($_GET["selected_parts_count"] ?? "");
$selected_parts_ids_query = trim($_GET["selected_parts_ids"] ?? "");
$selected_parts_titles_query = trim($_GET["selected_parts_titles"] ?? "");
$acompte_total_query = trim($_GET["acompte_total"] ?? "");
$selected_vehicles_count_query = trim($_GET["selected_vehicles_count"] ?? "");
$selected_vehicles_ids_query = trim($_GET["selected_vehicles_ids"] ?? "");
$selected_vehicles_titles_query = trim($_GET["selected_vehicles_titles"] ?? "");
$customer_type_query = contact_customer_type(trim($_GET["customer_type"] ?? "individual"));
$nom_query = trim($_GET["nom"] ?? "");
$prenom_query = trim($_GET["prenom"] ?? "");
$adresse_query = trim($_GET["adresse"] ?? "");
$code_postal_query = trim($_GET["code_postal"] ?? "");
$ville_query = trim($_GET["ville"] ?? "");
$email_query = trim($_GET["email"] ?? "");
$telephone_query = trim($_GET["telephone"] ?? "");
$immatriculation_query = trim($_GET["immatriculation"] ?? "");

if ($prestations_query !== "") {
    $form_data["prestations"] = $prestations_query;
    $form_data["message"] = "Je souhaite un devis pour : " . $prestations_query;
}
if ($source_service_query !== "") {
    $form_data["sujet"] = "Devis - " . $source_service_query;
}
if ($subject_query !== "") {
    $form_data["sujet"] = $subject_query;
}
if ($message_query !== "") {
    $form_data["message"] = $message_query;
}
if ($contact_action_query !== "") {
    $form_data["contact_action"] = $contact_action_query;
}
if ($annonce_id_query !== "") {
    $form_data["annonce_id"] = $annonce_id_query;
}
if ($annonce_type_query !== "") {
    $form_data["annonce_type"] = $annonce_type_query;
}
if ($annonce_title_query !== "") {
    $form_data["annonce_title"] = $annonce_title_query;
}
if ($annonce_price_query !== "") {
    $form_data["annonce_price"] = $annonce_price_query;
}
if ($acompte_montant_query !== "") {
    $form_data["acompte_montant"] = $acompte_montant_query;
}
if ($acompte_confirme_query === "1") {
    $form_data["acompte_confirme"] = "1";
}
if ($virement_compte_id_query !== "") {
    $form_data["virement_compte_id"] = $virement_compte_id_query;
}
if ($virement_confirme_client_query === "1") {
    $form_data["virement_confirme_client"] = "1";
    $form_data["acompte_confirme"] = "1";
}
if ($selected_parts_count_query !== "") {
    $form_data["selected_parts_count"] = preg_replace('/[^0-9]/', '', $selected_parts_count_query);
}
if ($selected_parts_ids_query !== "") {
    $form_data["selected_parts_ids"] = preg_replace('/[^0-9,]/', '', $selected_parts_ids_query);
}
if ($selected_parts_titles_query !== "") {
    $form_data["selected_parts_titles"] = trim($selected_parts_titles_query);
}
if ($acompte_total_query !== "") {
    $normalizedTotal = str_replace(',', '.', $acompte_total_query);
    if (is_numeric($normalizedTotal)) {
        $form_data["acompte_total"] = number_format((float) $normalizedTotal, 2, '.', '');
        $form_data["acompte_montant"] = $form_data["acompte_total"];
    }
}
if ($selected_vehicles_count_query !== "") {
    $form_data["selected_vehicles_count"] = preg_replace('/[^0-9]/', '', $selected_vehicles_count_query);
}
if ($selected_vehicles_ids_query !== "") {
    $form_data["selected_vehicles_ids"] = preg_replace('/[^0-9,]/', '', $selected_vehicles_ids_query);
}
if ($selected_vehicles_titles_query !== "") {
    $form_data["selected_vehicles_titles"] = trim($selected_vehicles_titles_query);
}
if ($customer_type_query !== "") {
    $form_data["customer_type"] = $customer_type_query;
}
if ($nom_query !== "") {
    $form_data["nom"] = $nom_query;
}
if ($prenom_query !== "") {
    $form_data["prenom"] = $prenom_query;
}
if ($adresse_query !== "") {
    $form_data["adresse"] = $adresse_query;
}
if ($code_postal_query !== "") {
    $form_data["code_postal"] = $code_postal_query;
}
if ($ville_query !== "") {
    $form_data["ville"] = $ville_query;
}
if ($email_query !== "") {
    $form_data["email"] = $email_query;
}
if ($telephone_query !== "") {
    $form_data["telephone"] = $telephone_query;
}
if ($immatriculation_query !== "") {
    $form_data["immatriculation"] = $immatriculation_query;
}

$identity_from_cookie = trim((string) ($_COOKIE[catalog_identity_cookie_name()] ?? ""));
$known_profile = catalog_get_customer_profile([
    "email" => trim((string) ($form_data["email"] !== "" ? $form_data["email"] : $identity_from_cookie)),
    "phone" => trim((string) $form_data["telephone"]),
    "registration" => trim((string) $form_data["immatriculation"])
]);
if (is_array($known_profile)) {
    $form_data["customer_type"] = contact_customer_type((string) ($known_profile["customer_type"] ?? $form_data["customer_type"]));
    if ($form_data["nom"] === "") {
        $form_data["nom"] = (string) ($known_profile["lastname"] ?? "");
    }
    if ($form_data["prenom"] === "") {
        $form_data["prenom"] = (string) ($known_profile["firstname"] ?? "");
    }
    if ($form_data["adresse"] === "") {
        $form_data["adresse"] = (string) ($known_profile["address_line"] ?? "");
    }
    if ($form_data["email"] === "") {
        $form_data["email"] = (string) ($known_profile["email"] ?? "");
    }
    if ($form_data["code_postal"] === "") {
        $form_data["code_postal"] = (string) ($known_profile["postal_code"] ?? "");
    }
    if ($form_data["ville"] === "") {
        $form_data["ville"] = (string) ($known_profile["city"] ?? "");
    }
    if ($form_data["telephone"] === "") {
        $form_data["telephone"] = (string) ($known_profile["phone"] ?? "");
    }
    if ($form_data["immatriculation"] === "") {
        $form_data["immatriculation"] = (string) ($known_profile["registration"] ?? "");
    }
    $welcome_firstname = trim((string) ($known_profile["firstname"] ?? ""));
    if ($welcome_firstname === '') {
        $welcome_firstname = trim((string) ($form_data["prenom"] ?? ''));
    }
    if ($welcome_firstname !== '') {
        $recognized_message = "Bonjour " . $welcome_firstname . ", bienvenue chez Clinik Auto. Que pouvons-nous faire pour vous ?";
    }

    $missingForKnownProfile = [];
    foreach (["nom", "prenom", "adresse", "telephone"] as $requiredField) {
        if (trim((string) ($form_data[$requiredField] ?? '')) === '') {
            $missingForKnownProfile[] = contact_identity_label($form_data["customer_type"], $requiredField);
        }
    }
    if (!empty($missingForKnownProfile)) {
        $recognized_incomplete_message = "Profil reconnu mais incomplet. Merci de compléter: " . implode(', ', $missingForKnownProfile) . ".";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_action = $_POST["form_action"] ?? "review";

    foreach ($form_data as $key => $value) {
        $form_data[$key] = trim($_POST[$key] ?? "");
    }
    $form_data["customer_type"] = contact_customer_type($form_data["customer_type"]);

    // La case à cocher envoie "1" si cochée, sinon le champ est absent du POST
    $form_data["sans_vehicule"] = isset($_POST["sans_vehicule"]) ? "1" : "";
    $forcedTransferConfirmed = (($form_data["virement_confirme_client"] ?? "") === "1");
    $form_data["acompte_confirme"] = (isset($_POST["acompte_confirme"]) || $forcedTransferConfirmed) ? "1" : "";

    // Si la demande ne concerne pas un véhicule, redirection immédiate vers le formulaire RDV
    // avec récupération des informations déjà saisies (même partielles)
    if ($form_action === "review" && $form_data["sans_vehicule"] === "1") {
        $rdv_query = http_build_query([
            "customer_type" => $form_data["customer_type"],
            "nom" => $form_data["nom"],
            "prenom" => $form_data["prenom"],
            "adresse" => $form_data["adresse"],
            "code_postal" => $form_data["code_postal"],
            "ville" => $form_data["ville"],
            "telephone" => $form_data["telephone"],
            "email" => $form_data["email"],
        ]);
        header("Location: ../rdv/rdv.php?" . $rdv_query);
        exit;
    }

    $required_fields = [
        "nom",
        "prenom",
        "adresse",
        "email",
        "telephone",
        "sujet",
        "message"
    ];
    // Prestations obligatoires seulement si le champ est affiché (venu du parcours devis)
    if ($form_data["prestations"] !== "") {
        $required_fields[] = "prestations";
    }
    if ($form_data["contact_action"] === "vehicle_visit") {
        $required_fields[] = "date_essai";
    }
    // Immatriculation obligatoire sauf si la demande ne concerne pas un véhicule
    if ($form_data["sans_vehicule"] !== "1") {
        $required_fields[] = "immatriculation";
    }

    $missing = [];
    foreach ($required_fields as $field) {
        if ($form_data[$field] === "") {
            $missing[] = $field;
        }
    }

    // Validation format email
    $email_invalide = false;
    if ($form_data["email"] !== "" && !filter_var($form_data["email"], FILTER_VALIDATE_EMAIL)) {
        $email_invalide = true;
    }

    if ($form_action === "edit") {
        $is_review = false;
    } elseif (!empty($missing)) {
        $error_message = "Veuillez remplir tous les champs obligatoires avant de continuer.";
    } elseif ($email_invalide) {
        $error_message = "L'adresse email saisie n'est pas valide. Exemple attendu : prenom@domaine.fr";
    } elseif ($form_action === "review") {
        catalog_save_customer_profile([
            "customer_type" => $form_data["customer_type"],
            "firstname" => $form_data["prenom"],
            "lastname" => $form_data["nom"],
            "address_line" => $form_data["adresse"],
            "postal_code" => $form_data["code_postal"],
            "city" => $form_data["ville"],
            "email" => $form_data["email"],
            "phone" => $form_data["telephone"],
            "registration" => $form_data["immatriculation"]
        ], "contact_review");
        $is_review = true;
    } elseif ($form_action === "submit") {
        $email_sent = false;
        $db_saved = false;
        $vehicle_request_saved = false;
        $vehicle_request_active = false;
        $vehicle_request_id = '';
        $reservation_marked = false;
        $part_request_saved = false;
        $part_request_id = '';
        $request_type = contact_request_context($form_data);

        if (
            $form_data["contact_action"] === "vehicle_visit" &&
            (int) ($form_data["annonce_id"] ?? 0) > 0
        ) {
            list($vehicle_request_saved, $vehicle_request_active, $vehicle_note, $vehicle_request_id) = catalog_register_vehicle_request((int) $form_data["annonce_id"], [
                "firstname" => $form_data["prenom"],
                "lastname" => $form_data["nom"],
                "email" => $form_data["email"],
                "phone" => $form_data["telephone"],
                "desired_date" => $form_data["date_essai"],
                "message" => $form_data["message"]
            ]);
        }

        catalog_save_customer_profile([
            "customer_type" => $form_data["customer_type"],
            "firstname" => $form_data["prenom"],
            "lastname" => $form_data["nom"],
            "address_line" => $form_data["adresse"],
            "postal_code" => $form_data["code_postal"],
            "city" => $form_data["ville"],
            "email" => $form_data["email"],
            "phone" => $form_data["telephone"],
            "registration" => $form_data["immatriculation"]
        ], "contact");

        if ($form_data["email"] !== "" && filter_var($form_data["email"], FILTER_VALIDATE_EMAIL)) {
            setcookie(catalog_identity_cookie_name(), strtolower(trim((string) $form_data["email"])), time() + 31536000, "/", "", false, true);
        }

        if (
            $form_data["contact_action"] === "part_reservation" &&
            (int) ($form_data["annonce_id"] ?? 0) > 0
        ) {
            list($part_request_saved, $reservation_marked, $part_note, $part_request_id) = catalog_register_part_request(
                (int) $form_data["annonce_id"],
                [
                    "firstname" => $form_data["prenom"],
                    "lastname" => $form_data["nom"],
                    "email" => $form_data["email"],
                    "phone" => $form_data["telephone"],
                    "message" => $form_data["message"]
                ],
                ($form_data["acompte_confirme"] === "1")
            );
        }

        $destinataire = defined("GARAGE_EMAIL") ? GARAGE_EMAIL : "clinikauto74@gmail.com";
        $email_subject = "[" . $request_type . "] " . $form_data["sujet"];
        $annonce_resume = $form_data["annonce_title"] !== ""
            ? ($form_data["annonce_title"] . ($form_data["annonce_price"] !== "" ? " (" . $form_data["annonce_price"] . " EUR)" : ""))
            : "N/A";
        $email_body =
            "Nouvelle demande reçue via formulaire contact\n\n" .
            "Type de demande automatique: " . $request_type . "\n" .
            "Type client: " . ($form_data["customer_type"] === 'professional' ? 'Professionnel' : 'Particulier') . "\n" .
            contact_identity_label($form_data["customer_type"], "nom") . ": " . $form_data["nom"] . "\n" .
            contact_identity_label($form_data["customer_type"], "prenom") . ": " . $form_data["prenom"] . "\n" .
            "Adresse: " . $form_data["adresse"] . "\n" .
            "Code postal: " . $form_data["code_postal"] . "\n" .
            "Ville: " . $form_data["ville"] . "\n" .
            "Email: " . $form_data["email"] . "\n" .
            "Téléphone: " . $form_data["telephone"] . "\n" .
            "Immatriculation: " . $form_data["immatriculation"] . "\n" .
            "Annonce liée: " . $annonce_resume . "\n" .
            "Type annonce: " . $form_data["annonce_type"] . "\n" .
            "Acompte confirmé: " . (($form_data["acompte_confirme"] === "1") ? "Oui" : "Non") . "\n" .
            "Acompte attendu (30%): " . ($form_data["acompte_montant"] !== "" ? $form_data["acompte_montant"] . " EUR" : "N/A") . "\n" .
            "Articles pièces sélectionnés: " . ($form_data["selected_parts_count"] !== "" ? $form_data["selected_parts_count"] : "N/A") . "\n" .
            "IDs pièces sélectionnées: " . ($form_data["selected_parts_ids"] !== "" ? $form_data["selected_parts_ids"] : "N/A") . "\n" .
            "Titres pièces sélectionnées: " . ($form_data["selected_parts_titles"] !== "" ? $form_data["selected_parts_titles"] : "N/A") . "\n" .
            "Véhicules sélectionnés (nb): " . ($form_data["selected_vehicles_count"] !== "" ? $form_data["selected_vehicles_count"] : "N/A") . "\n" .
            "IDs véhicules sélectionnés: " . ($form_data["selected_vehicles_ids"] !== "" ? $form_data["selected_vehicles_ids"] : "N/A") . "\n" .
            "Titres véhicules sélectionnés: " . ($form_data["selected_vehicles_titles"] !== "" ? $form_data["selected_vehicles_titles"] : "N/A") . "\n" .
            "Date d'essai souhaitée: " . ($form_data["date_essai"] !== "" ? $form_data["date_essai"] : "N/A") . "\n" .
            "Sujet: " . $form_data["sujet"] . "\n" .
            "Prestations: " . $form_data["prestations"] . "\n\n" .
            "Message:\n" . $form_data["message"] . "\n";

        $safe_sender = preg_replace('/[\r\n]+/', '', $form_data["email"]);
        $email_sent = send_devis_email($destinataire, $safe_sender, $email_subject, $email_body);

        if (defined("DB_HOST") && defined("DB_USER") && defined("DB_PASS") && defined("DB_NAME")) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $dbPort = defined('DB_PORT') ? (int) DB_PORT : 3306;
            $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $dbPort);
            if (!$conn->connect_error) {
                $conn->set_charset("utf8mb4");
                $stmt = $conn->prepare(
                    "INSERT INTO demandes_devis (customer_type, nom, prenom, adresse, postal_code, city, email, telephone, immatriculation, sujet, prestations, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssssssssss",
                        $form_data["customer_type"],
                        $form_data["nom"],
                        $form_data["prenom"],
                        $form_data["adresse"],
                        $form_data["code_postal"],
                        $form_data["ville"],
                        $form_data["email"],
                        $form_data["telephone"],
                        $form_data["immatriculation"],
                        $form_data["sujet"],
                        $form_data["prestations"],
                        $form_data["message"]
                    );
                    $db_saved = $stmt->execute();
                    $stmt->close();
                }
                $conn->close();
            }
        }

        if ($email_sent || $db_saved || $reservation_marked || $vehicle_request_saved || $part_request_saved) {

            $shouldRedirectToRdv = false;
            $rdvContext = [];
            if ($form_data["contact_action"] === "part_reservation" && $part_request_saved && $reservation_marked && $part_request_id !== '') {
                $shouldRedirectToRdv = true;
                $rdvContext = [
                    'request_context_type' => 'part_reservation',
                    'linked_request_id' => $part_request_id,
                    'linked_annonce_id' => (string) ((int) ($form_data['annonce_id'] ?? 0)),
                    'linked_title' => (string) ($form_data['annonce_title'] ?? ''),
                    'objet' => 'Retrait / réservation pièce - ' . (string) ($form_data['annonce_title'] ?? 'Pièce')
                ];
            }
            if ($form_data["contact_action"] === "vehicle_visit" && $vehicle_request_saved && $vehicle_request_active && $vehicle_request_id !== '') {
                $shouldRedirectToRdv = true;
                $rdvContext = [
                    'request_context_type' => 'vehicle_visit',
                    'linked_request_id' => $vehicle_request_id,
                    'linked_annonce_id' => (string) ((int) ($form_data['annonce_id'] ?? 0)),
                    'linked_title' => (string) ($form_data['annonce_title'] ?? ''),
                    'objet' => 'Essai véhicule - ' . (string) ($form_data['annonce_title'] ?? 'Véhicule')
                ];
            }

            if ($shouldRedirectToRdv) {
                $_SESSION['catalog_contact_success_notice'] = 'Demande enregistrée. Merci de choisir maintenant votre rendez-vous.';
                $rdvQuery = http_build_query(array_merge([
                    'customer_type' => $form_data['customer_type'],
                    'nom' => $form_data['nom'],
                    'prenom' => $form_data['prenom'],
                    'adresse' => $form_data['adresse'],
                    'code_postal' => $form_data['code_postal'],
                    'ville' => $form_data['ville'],
                    'telephone' => $form_data['telephone'],
                    'email' => $form_data['email']
                ], $rdvContext));
                header('Location: ../rdv/rdv.php?' . $rdvQuery);
                exit;
            }

            $identityMessage = htmlspecialchars($form_data["prenom"]) . " " . htmlspecialchars($form_data["nom"]);
            if ($form_data["customer_type"] === 'professional') {
                $identityMessage = htmlspecialchars($form_data["nom"]) . " (contact: " . htmlspecialchars($form_data["prenom"]) . ")";
            }
            $message = "Merci " . $identityMessage . ", votre demande a bien été enregistrée.";
            if (!$email_sent) {
                $message .= " Nous l'avons bien reçue, mais l'envoi de notification e-mail est temporairement indisponible.";
            }
            if ($reservation_marked) {
                $message .= " La pièce est maintenant marquée comme indisponible suite à la confirmation de l'acompte.";
            }
            if ($part_request_saved && !$reservation_marked && $form_data["contact_action"] === "part_reservation") {
                if (!empty($part_note)) {
                    $message .= ' ' . htmlspecialchars((string) $part_note, ENT_QUOTES, 'UTF-8') . '.';
                } else {
                    $message .= " Votre demande est enregistrée par ordre de priorité. Si la pièce se libère, nous vous contacterons.";
                }
            }
            if ($vehicle_request_saved) {
                if (!empty($vehicle_request_active)) {
                    $message .= " Votre demande d'essai devient prioritaire et un dossier de transaction est ouvert.";
                } else {
                    $message .= " Votre demande d'essai est enregistrée dans la file d'attente. Nous vous contacterons si le dossier en cours n'aboutit pas.";
                }
            }
            $form_data = [
                "customer_type" => "individual",
                "nom" => "",
                "prenom" => "",
                "adresse" => "",
                "code_postal" => "",
                "ville" => "",
                "email" => "",
                "telephone" => "",
                "immatriculation" => "",
                "sans_vehicule" => "",
                "contact_action" => "",
                "annonce_id" => "",
                "annonce_type" => "",
                "annonce_title" => "",
                "annonce_price" => "",
                "date_essai" => "",
                "acompte_montant" => "",
                "acompte_confirme" => "",
                "virement_compte_id" => "",
                "virement_confirme_client" => "",
                "selected_parts_count" => "",
                "selected_parts_ids" => "",
                "selected_parts_titles" => "",
                "acompte_total" => "",
                "selected_vehicles_count" => "",
                "selected_vehicles_ids" => "",
                "selected_vehicles_titles" => "",
                "sujet" => "",
                "prestations" => "",
                "message" => ""
            ];
        } else {
            $error_message = "Envoi indisponible pour le moment. Verifiez la configuration SMTP/BDD dans config.php.";
        }
    }
}

function e($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$virement_lock_mode = (
    ($form_data["contact_action"] ?? "") === "part_reservation" &&
    ($form_data["virement_confirme_client"] ?? "") === "1"
);
$virement_account = null;
if ($virement_lock_mode && trim((string) ($form_data["virement_compte_id"] ?? "")) !== "") {
    $virement_account = catalog_bank_account_find_by_id((string) $form_data["virement_compte_id"]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Garage Clinik Auto Scionzier (74950) | 06 20 18 56 27 | Devis Gratuit</title>
    <meta name="description" content="Contactez Clinik Auto à Scionzier (74950) : ☎ 06 20 18 56 27 – clinikauto74@gmail.com. Devis gratuit pour révision, réparation auto ou réservation de pièces & véhicules VO.">
    <meta name="keywords" content="contact garage Scionzier, devis révision 74, devis réparation auto Haute-Savoie, garage téléphone 74950, email garage Cluses, devis gratuit mécanique">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/contact/contact.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Contact & Devis Gratuit | Clinik Auto Scionzier (74)">
    <meta property="og:description" content="Contactez Clinik Auto à Scionzier (74950) par téléphone ou email. Devis gratuit pour révision, réparation ou achat de véhicule.">
    <meta property="og:url" content="https://www.clinikauto.fr/contact/contact.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Contact & Devis | Clinik Auto Scionzier (74)">
    <meta name="twitter:description" content="Contactez Clinik Auto à Scionzier. Tél : 06 20 18 56 27. Devis gratuit en ligne.">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/postal-city.js" defer></script>
    <script src="../assets/customer-type.js" defer></script>
    <script type="application/ld+json">
        <?php echo json_encode($contactStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <?php echo catalog_get_google_analytics_script(); ?>
    <?php if (function_exists('csrf_print_meta_and_js')) { csrf_print_meta_and_js(); } ?>
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
                <li><a href="contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="contact-page">
        <section>
            <p class="contact-kicker">Parlons de votre véhicule</p>
            <h1>Contact Clinik Auto</h1>
            <p class="contact-hero-lead">Décrivez votre besoin en 2 minutes. Notre équipe vous répond rapidement pour organiser un devis, un essai ou une intervention atelier.</p>
            <div class="contact-hero-actions">
                <a href="tel:0620185627" class="cta-link">Appeler maintenant</a>
                <a href="../devis/devis.php" class="cta-link-secondary">Demander un devis en ligne</a>
            </div>
        </section>

        <div class="contact-layout">
            <div class="contact-column contact-info-card">
                <h2>Informations pratiques</h2>
                <ul class="contact-info-list">
                    <li><strong>Adresse :</strong> 118 Clos des Teppes, 74950 Scionzier</li>
                    <li><strong>Téléphone :</strong> <a href="tel:0620185627">06 20 18 56 27</a></li>
                    <li><strong>Email :</strong> <a href="mailto:clinikauto74@gmail.com">clinikauto74@gmail.com</a></li>
                    <li><strong>Horaires :</strong> Lundi - Vendredi : 9h00-12h00 / 14h00-18h00, Samedi : 9h00-12h00, Dimanche : Ferme</li>
                </ul>
                <div class="contact-actions">
                    <div class="map-embed">
                        <iframe
                            src="https://www.google.com/maps?q=118+Clos+des+Teppes%2C+74950+Scionzier&output=embed"
                            title="Plan Clinik Auto"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <a href="https://www.google.com/maps/dir/?api=1&amp;destination=118+Clos+des+Teppes%2C+74950+Scionzier" class="cta-link cta-inline" target="_blank" rel="noopener noreferrer">Lancer le trajet GPS →</a>
                    <p class="contact-trust-note"><span>3 repères clients</span> Devis clair, délais annoncés et suivi réel du dossier : vous savez toujours où en est votre voiture.</p>
                </div>
            </div>
            <div class="contact-column contact-form-card">
                <h2>Formulaire de contact</h2>
                <p class="contact-form-intro">Renseignez vos coordonnées et votre demande. Vous verrez un récapitulatif avant validation finale.</p>
                <?php if ($message) { echo "<p class='success-message'>$message</p>"; } ?>
                <?php if ($error_message) { echo "<p class='error-message'>" . e($error_message) . "</p>"; } ?>
                <?php if ($recognized_message !== "") { echo "<p class='success-message'>" . e($recognized_message) . "</p>"; } ?>
                <?php if ($recognized_incomplete_message !== "") { echo "<p class='error-message'>" . e($recognized_incomplete_message) . "</p>"; } ?>

                <?php if ($is_review): ?>
                <div class="review-card">
                    <h4>Recapitulatif de votre demande</h4>
                    <p><strong>Type de demande :</strong> <?php echo e(contact_request_context($form_data)); ?></p>
                    <p><strong>Type client :</strong> <?php echo $form_data["customer_type"] === 'professional' ? 'Professionnel' : 'Particulier'; ?></p>
                    <p><strong><?php echo e(contact_identity_label($form_data["customer_type"], "nom")); ?> :</strong> <?php echo e($form_data["nom"]); ?></p>
                    <p><strong><?php echo e(contact_identity_label($form_data["customer_type"], "prenom")); ?> :</strong> <?php echo e($form_data["prenom"]); ?></p>
                    <p><strong>Adresse :</strong> <?php echo e($form_data["adresse"]); ?></p>
                    <p><strong>Code postal :</strong> <?php echo e($form_data["code_postal"]); ?></p>
                    <p><strong>Ville :</strong> <?php echo e($form_data["ville"]); ?></p>
                    <p><strong>Email :</strong> <?php echo e($form_data["email"]); ?></p>
                    <p><strong>Telephone :</strong> <?php echo e($form_data["telephone"]); ?></p>
                    <?php if ($form_data["sans_vehicule"] === "1"): ?>
                    <p><em>Demande sans véhicule</em></p>
                    <?php elseif ($form_data["immatriculation"] !== ""): ?>
                    <p><strong>Immatriculation :</strong> <?php echo e($form_data["immatriculation"]); ?></p>
                    <?php endif; ?>
                    <p><strong>Sujet :</strong> <?php echo e($form_data["sujet"]); ?></p>
                    <?php if ($form_data["prestations"] !== ""): ?>
                    <p><strong>Prestations :</strong> <?php echo e($form_data["prestations"]); ?></p>
                    <?php endif; ?>
                    <?php if ($form_data["annonce_title"] !== ""): ?>
                    <p><strong>Annonce liée :</strong> <?php echo e($form_data["annonce_title"]); ?><?php echo $form_data["annonce_price"] !== "" ? " (" . e($form_data["annonce_price"]) . " EUR)" : ""; ?></p>
                    <?php endif; ?>
                    <?php if ($form_data["contact_action"] === "vehicle_visit" && $form_data["date_essai"] !== ""): ?>
                    <p><strong>Date d'essai souhaitée :</strong> <?php echo e($form_data["date_essai"]); ?></p>
                    <?php endif; ?>
                    <?php if ($form_data["contact_action"] === "part_reservation"): ?>
                    <p><strong>Acompte 30 % confirmé :</strong> <?php echo $form_data["acompte_confirme"] === "1" ? "Oui" : "Non"; ?></p>
                    <?php if ($form_data["selected_parts_count"] !== ""): ?>
                    <p><strong>Nombre d'articles sélectionnés :</strong> <?php echo e($form_data["selected_parts_count"]); ?></p>
                    <?php endif; ?>
                    <?php if ($form_data["acompte_total"] !== ""): ?>
                    <p><strong>Acompte total à virer :</strong> <?php echo e(number_format((float) $form_data["acompte_total"], 2, ',', ' ')); ?> EUR</p>
                    <?php endif; ?>
                    <?php if ($form_data["selected_parts_titles"] !== ""): ?>
                    <p><strong>Articles sélectionnés :</strong> <?php echo e($form_data["selected_parts_titles"]); ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($form_data["contact_action"] === "vehicle_visit" && $form_data["selected_vehicles_count"] !== ""): ?>
                    <p><strong>Véhicules sélectionnés :</strong> <?php echo e($form_data["selected_vehicles_count"]); ?></p>
                    <?php if ($form_data["selected_vehicles_titles"] !== ""): ?>
                    <p><strong>Détail véhicules :</strong> <?php echo e($form_data["selected_vehicles_titles"]); ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                    <p><strong>Message :</strong> <?php echo nl2br(e($form_data["message"])); ?></p>

                    <form method="post" class="review-actions">
                        <?php foreach ($form_data as $key => $value): ?>
                            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                        <?php endforeach; ?>
                        <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                        <button type="submit" name="form_action" value="submit">Valider votre demande de devis →</button>
                        <button class="btn-secondary" type="submit" name="form_action" value="edit">Modifier les informations</button>
                    </form>
                </div>
                <?php else: ?>
                <form method="post">
                    <input type="hidden" name="form_action" value="review">
                    <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                    <input type="hidden" name="contact_action" value="<?php echo e($form_data["contact_action"]); ?>">
                    <input type="hidden" name="annonce_id" value="<?php echo e($form_data["annonce_id"]); ?>">
                    <input type="hidden" name="annonce_type" value="<?php echo e($form_data["annonce_type"]); ?>">
                    <input type="hidden" name="annonce_title" value="<?php echo e($form_data["annonce_title"]); ?>">
                    <input type="hidden" name="annonce_price" value="<?php echo e($form_data["annonce_price"]); ?>">
                    <input type="hidden" name="acompte_montant" value="<?php echo e($form_data["acompte_montant"]); ?>">
                    <input type="hidden" name="selected_parts_count" value="<?php echo e($form_data["selected_parts_count"]); ?>">
                    <input type="hidden" name="selected_parts_ids" value="<?php echo e($form_data["selected_parts_ids"]); ?>">
                    <input type="hidden" name="selected_parts_titles" value="<?php echo e($form_data["selected_parts_titles"]); ?>">
                    <input type="hidden" name="acompte_total" value="<?php echo e($form_data["acompte_total"]); ?>">
                    <input type="hidden" name="selected_vehicles_count" value="<?php echo e($form_data["selected_vehicles_count"]); ?>">
                    <input type="hidden" name="selected_vehicles_ids" value="<?php echo e($form_data["selected_vehicles_ids"]); ?>">
                    <input type="hidden" name="selected_vehicles_titles" value="<?php echo e($form_data["selected_vehicles_titles"]); ?>">
                    <div data-customer-type-context>
                    <input type="hidden" name="customer_type" value="<?php echo e($form_data["customer_type"]); ?>" data-customer-type-input>
                    <label class="checkbox-toggle">
                        <input type="checkbox" value="1" data-customer-type-checkbox <?php echo $form_data["customer_type"] === 'professional' ? 'checked' : ''; ?>>
                        Je remplis ce formulaire en tant que professionnel
                    </label>
                    <label><span data-type-label-target data-individual-label="Nom" data-professional-label="Raison sociale"><?php echo e(contact_identity_label($form_data["customer_type"], "nom")); ?></span>
                        <input type="text" name="nom" placeholder="Votre nom complet" data-type-placeholder-target data-individual-placeholder="Votre nom complet" data-professional-placeholder="Raison sociale de l'entreprise" value="<?php echo e($form_data["nom"]); ?>" required>
                    </label>
                    <label><span data-type-label-target data-individual-label="Prénom" data-professional-label="Nom du contact"><?php echo e(contact_identity_label($form_data["customer_type"], "prenom")); ?></span>
                        <input type="text" name="prenom" placeholder="Votre prénom" data-type-placeholder-target data-individual-placeholder="Votre prénom" data-professional-placeholder="Nom et prénom du contact" value="<?php echo e($form_data["prenom"]); ?>" required>
                    </label>
                    <label>Adresse
                        <input type="text" name="adresse" placeholder="Votre adresse complète" value="<?php echo e($form_data["adresse"]); ?>" required>
                    </label>
                    <div data-postal-city-group data-postal-endpoint="../postal_lookup.php">
                        <label>Code postal
                            <input type="text" name="code_postal" inputmode="numeric" maxlength="10" placeholder="74950" value="<?php echo e($form_data["code_postal"]); ?>" data-postal-code-input>
                        </label>
                        <label>Ville
                            <input type="text" name="ville" list="contact-postal-city-list" placeholder="Scionzier" value="<?php echo e($form_data["ville"]); ?>" data-city-input>
                        </label>
                        <datalist id="contact-postal-city-list"></datalist>
                        <p class="form-helper" data-postal-city-status></p>
                    </div>
                    <label>Email
                        <input type="email" name="email" placeholder="votre@email.fr" value="<?php echo e($form_data["email"]); ?>" required>
                    </label>
                    <label>Téléphone
                        <input type="tel" name="telephone" placeholder="06 12 34 56 78" value="<?php echo e($form_data["telephone"]); ?>" required>
                    </label>
                    <label class="checkbox-toggle">
                        <input type="checkbox" name="sans_vehicule" id="sans_vehicule" value="1" onchange="toggleImmat(this)" <?php echo $form_data["sans_vehicule"] === "1" ? "checked" : ""; ?>>
                        Ma demande ne concerne pas un véhicule
                    </label>
                    <div id="immat-field">
                        <label>Immatriculation du véhicule
                            <input type="text" name="immatriculation" id="immatriculation" placeholder="AA-123-BB" value="<?php echo e($form_data["immatriculation"]); ?>" required>
                        </label>
                    </div>
                    <script>
                    function toggleImmat(cb) {
                        var field = document.getElementById('immat-field');
                        var input = document.getElementById('immatriculation');
                        var optionalForRdv = ['nom', 'prenom', 'adresse', 'email', 'telephone', 'sujet', 'message'];

                        function setRequired(name, required) {
                            var el = document.querySelector('[name="' + name + '"]');
                            if (!el) return;
                            if (required) {
                                el.setAttribute('required', 'required');
                            } else {
                                el.removeAttribute('required');
                            }
                        }

                        if (cb.checked) {
                            field.style.display = 'none';
                            input.removeAttribute('required');
                            input.value = '';
                            optionalForRdv.forEach(function(name) { setRequired(name, false); });
                        } else {
                            field.style.display = '';
                            input.setAttribute('required', 'required');
                            optionalForRdv.forEach(function(name) { setRequired(name, true); });
                        }
                    }
                    // Appliquer l'état initial au chargement
                    (function() {
                        var cb = document.getElementById('sans_vehicule');
                        if (cb) toggleImmat(cb);
                    })();
                    </script>
                    <label>Sujet
                        <input type="text" name="sujet" placeholder="Objet de votre message" value="<?php echo e($form_data["sujet"]); ?>" required>
                    </label>
                    <?php if ($form_data["contact_action"] === "vehicle_visit"): ?>
                    <label>Date souhaitée pour l'essai
                        <input type="date" name="date_essai" value="<?php echo e($form_data["date_essai"]); ?>" required>
                    </label>
                    <?php endif; ?>
                    <?php if ($form_data["contact_action"] === "part_reservation"): ?>
                        <?php if ($virement_lock_mode): ?>
                            <input type="hidden" name="acompte_confirme" value="1">
                            <input type="hidden" name="virement_confirme_client" value="1">
                            <input type="hidden" name="virement_compte_id" value="<?php echo e($form_data["virement_compte_id"]); ?>">
                            <p class="success-message">Confirmation du virement recue. Le parcours continue vers la validation du rendez-vous.</p>
                            <?php if (is_array($virement_account)): ?>
                                <p class="form-note">
                                    <strong>Compte confirme :</strong> <?php echo e((string) ($virement_account['label'] ?? 'Compte principal')); ?>
                                    (IBAN <?php echo e((string) ($virement_account['iban'] ?? '')); ?>)
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <label class="checkbox-toggle">
                                <input type="checkbox" name="acompte_confirme" value="1" <?php echo $form_data["acompte_confirme"] === "1" ? "checked" : ""; ?>>
                                J'ai déjà effectué le virement instantané de l'acompte (30 %)
                            </label>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($form_data["prestations"] !== ""): ?>
                    <label>Prestations sélectionnées
                        <textarea name="prestations" rows="4" readonly><?php echo e($form_data["prestations"]); ?></textarea>
                    </label>
                    <?php else: ?>
                    <input type="hidden" name="prestations" value="">
                    <?php endif; ?>
                    <label>Message
                        <textarea name="message" rows="5" placeholder="Décrivez votre demande..." required><?php echo e($form_data["message"]); ?></textarea>
                    </label>
                    <button type="submit">Continuer vers le récapitulatif →</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
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