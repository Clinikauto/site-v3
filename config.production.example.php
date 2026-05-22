<?php
// Clinik Auto - Exemple de configuration PRODUCTION
// Copiez ce fichier en config.php sur le serveur distant et remplacez chaque valeur __A_COMPLETER__.

// ===== INFORMATIONS DU GARAGE =====
define('GARAGE_NOM', 'Clinik Auto');
define('GARAGE_ADRESSE', '__A_COMPLETER__');
define('GARAGE_TEL', '__A_COMPLETER__');
define('GARAGE_EMAIL', 'clinikauto74@gmail.com');
define('GARAGE_HORAIRES', '__A_COMPLETER__');

// ===== CONFIGURATION BASE DE DONNEES =====
define('DB_HOST', '__A_COMPLETER__');
define('DB_PORT', 3306);
define('DB_USER', '__A_COMPLETER__');
define('DB_PASS', '__A_COMPLETER__');
define('DB_NAME', '__A_COMPLETER__');

// ===== CONFIGURATION EMAIL =====
define('EMAIL_EXPEDITEUR', 'smtp@clinikauto.fr');
define('SMTP_ENABLED', true);
define('SMTP_HOST', '__A_COMPLETER__');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '__A_COMPLETER__');
define('SMTP_PASSWORD', '__A_COMPLETER__');
define('SMTP_SECURE', 'tls'); // tls ou ssl

// ===== SYNCHRONISATION GOOGLE AGENDA =====
define('GOOGLE_CALENDAR_ENABLED', true);
define('GOOGLE_CALENDAR_ID', 'primary');
define('GOOGLE_CLIENT_ID', '__A_COMPLETER__');
define('GOOGLE_CLIENT_SECRET', '__A_COMPLETER__');
define('GOOGLE_REFRESH_TOKEN', '__A_COMPLETER__');

// ===== CHEMINS =====
define('COMPOSER_AUTOLOAD_PATH', __DIR__ . '/vendor/autoload.php');

// ===== ACCES ADMINISTRATEUR =====
define('ADMIN_LOGIN', '__A_COMPLETER__');
define('ADMIN_PASSWORD_HASH', '__A_COMPLETER__'); // hash password_hash(..., PASSWORD_DEFAULT)
define('ADMIN_PASSWORD_RESET_EMAIL', '__A_COMPLETER__');
define('ADMIN_HIDDEN_ENTRY_ENABLED', true);
define('ADMIN_HIDDEN_ENTRY_KEY', '__A_COMPLETER__');

// Mettre ici votre IP publique fixe et/ou celle du bureau.
// Exemples: ['203.0.113.10'] ou ['203.0.113.10', '198.51.100.22']
define('ADMIN_ALLOWED_IPS', ['__A_COMPLETER__']);
define('ADMIN_SECURITY_NOTICE', 'Acces reserve au proprietaire.');

// ===== SECURITE / COMPORTEMENT ADMIN =====
// En production: laisser true pour bloquer les actions sensibles si la DB tombe.
define('CATALOG_ADMIN_REQUIRE_DB', true);

// Timeout session admin (secondes). 1800 = 30 min.
define('ADMIN_SESSION_TIMEOUT_SECONDS', 1800);
