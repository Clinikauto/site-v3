<?php
// Configuration de Clinik Auto
// À modifier avec vos propres paramètres Webator

// ===== INFORMATIONS DU GARAGE =====
define('GARAGE_NOM', 'Clinik Auto');
define('GARAGE_ADRESSE', '118 Clos des Teppes, 74950 Scionzier');
define('GARAGE_TEL', '06 20 18 56 27');
define('GARAGE_EMAIL', 'clinikauto74@gmail.com');
define('GARAGE_HORAIRES', 'Lun–Ven : 9h–12h / 14h–18h | Sam : 9h–12h | Dim : Fermé');

// ===== CONFIGURATION BASE DE DONNÉES =====
// À activer une fois sur Webator
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'clinikauto');

// Fonction pour se connecter à la BD
// function connexion_db() {
//     $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
//     if ($conn->connect_error) {
//         die("Erreur de connexion : " . $conn->connect_error);
//     }
//     $conn->set_charset("utf8");
//     return $conn;
// }

// ===== CONFIGURATION EMAIL =====
// Pour envoyer des emails depuis le serveur
define('EMAIL_EXPEDITEUR', 'clinikauto74@gmail.com');

// Active l'envoi SMTP (recommande en production)
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'clinikauto74@gmail.com');
define('SMTP_PASSWORD', '');
define('SMTP_SECURE', 'tls'); // tls ou ssl

// ===== SYNCHRONISATION GOOGLE AGENDA =====
// Active la synchronisation bidirectionnelle automatique des rendez-vous.
define('GOOGLE_CALENDAR_ENABLED', true);
define('GOOGLE_CALENDAR_ID', 'primary');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REFRESH_TOKEN', '');

// Chemin vers l'autoload Composer (PHPMailer)
define('COMPOSER_AUTOLOAD_PATH', __DIR__ . '/vendor/autoload.php');

// ===== ACCES ADMINISTRATEUR =====
define('ADMIN_LOGIN', 'clinikauto74@gmail.com');
define('ADMIN_PASSWORD_HASH', '$2y$10$XlTLQAAQbQTp/IWJbKYIOuATYEYgNnf.PL5klBKj2hyq22fWjaqNe');
define('ADMIN_PASSWORD_RESET_EMAIL', 'clinikauto74@gmail.com');
define('ADMIN_HIDDEN_ENTRY_ENABLED', true);
define('ADMIN_HIDDEN_ENTRY_KEY', 'CLINIKAUTO-ACCES-2026');
define('ADMIN_ALLOWED_IPS', ['127.0.0.1', '::1']);
define('ADMIN_SECURITY_NOTICE', 'Accès réservé au propriétaire.');

