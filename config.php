<?php
// Configuration de Clinik Auto
// À modifier avec vos propres paramètres o2switch

$catalogHost = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
$catalogRemoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$catalogIsLocalRuntime =
	$catalogHost === 'localhost' ||
	strpos($catalogHost, '127.0.0.1') !== false ||
	$catalogRemoteAddr === '127.0.0.1' ||
	$catalogRemoteAddr === '::1';

define('CATALOG_IS_LOCAL_RUNTIME', $catalogIsLocalRuntime);

function catalog_env_value($key, $default = '')
{
	$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
	if ($value === false || $value === null || $value === '') {
		return $default;
	}
	return (string) $value;
}

function catalog_load_dotenv($dotenvPath)
{
	if (!is_readable($dotenvPath)) {
		return;
	}

	$lines = @file($dotenvPath, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		return;
	}

	foreach ($lines as $line) {
		$trimmed = trim((string) $line);
		if ($trimmed === '' || strpos($trimmed, '#') === 0) {
			continue;
		}

		$separatorPos = strpos($trimmed, '=');
		if ($separatorPos === false || $separatorPos < 1) {
			continue;
		}

		$key = trim(substr($trimmed, 0, $separatorPos));
		$value = trim(substr($trimmed, $separatorPos + 1));
		if ($key === '') {
			continue;
		}

		$firstChar = substr($value, 0, 1);
		$lastChar = substr($value, -1);
		if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
			$value = substr($value, 1, -1);
		}

		if (getenv($key) === false && !isset($_ENV[$key]) && !isset($_SERVER[$key])) {
			$_ENV[$key] = $value;
			$_SERVER[$key] = $value;
			@putenv($key . '=' . $value);
		}
	}
}

catalog_load_dotenv(__DIR__ . '/.env');

// ===== INFORMATIONS DU GARAGE =====
define('GARAGE_NOM', 'Clinik Auto');
define('GARAGE_ADRESSE', '118 Clos des Teppes, 74950 Scionzier');
define('GARAGE_TEL', '06 20 18 56 27');
define('GARAGE_EMAIL', 'clinikauto74@gmail.com');
define('GARAGE_HORAIRES', 'Lun–Ven : 9h–12h / 14h–18h | Sam : 9h–12h | Dim : Fermé');

// ===== CONFIGURATION BASE DE DONNÉES =====
// À adapter selon l'environnement local ou o2switch
define('DB_HOST', catalog_env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) catalog_env_value('DB_PORT', '3307'));
define('DB_USER', catalog_env_value('DB_USER', 'root'));
define('DB_PASS', catalog_env_value('DB_PASS', 'root'));
define('DB_NAME', catalog_env_value('DB_NAME', 'clinikauto'));

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
define('SMTP_PASSWORD', catalog_env_value('SMTP_PASSWORD', ''));
define('SMTP_SECURE', 'tls'); // tls ou ssl

// ===== SYNCHRONISATION GOOGLE AGENDA =====
// Active la synchronisation bidirectionnelle automatique des rendez-vous.
// En local: false par defaut (evite les alertes inutiles sans credentials).
// En production: true par defaut (si credentials Google renseignes).
define('GOOGLE_CALENDAR_ENABLED', CATALOG_IS_LOCAL_RUNTIME ? false : true);
define('GOOGLE_CALENDAR_ID', 'primary');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', catalog_env_value('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REFRESH_TOKEN', catalog_env_value('GOOGLE_REFRESH_TOKEN', ''));

// Chemin vers l'autoload Composer (PHPMailer)
define('COMPOSER_AUTOLOAD_PATH', __DIR__ . '/vendor/autoload.php');

// ===== ACCES ADMINISTRATEUR =====
define('ADMIN_LOGIN', 'clinikauto74@gmail.com');
define('ADMIN_PASSWORD_HASH', catalog_env_value('ADMIN_PASSWORD_HASH', '$2y$10$FCHO3tzKz8.5hpztYE1dnO/DELLAvvRc5DomNYErk5lMm6Nk6VW8O'));
define('ADMIN_PASSWORD_RESET_EMAIL', 'clinikauto74@gmail.com');
define('ADMIN_HIDDEN_ENTRY_ENABLED', CATALOG_IS_LOCAL_RUNTIME ? false : true);
define('ADMIN_HIDDEN_ENTRY_KEY', catalog_env_value('ADMIN_HIDDEN_ENTRY_KEY', 'CLINIKAUTO-ACCES-2026'));
// Laisser vide pour autoriser l'acces de n'importe quelle IP (recommande si IP non fixe).
// Si vous avez une IP fixe, vous pouvez restreindre: ['203.0.113.10']
define('ADMIN_ALLOWED_IPS', []);
define('ADMIN_SECURITY_NOTICE', 'Accès réservé au propriétaire.');

// ===== SECURITE / COMPORTEMENT ADMIN =====
// En local: false pour autoriser la bascule JSON si la DB est indisponible.
// En production: true pour bloquer les actions sensibles en cas de panne DB.
$catalogRequireDbDefault = CATALOG_IS_LOCAL_RUNTIME ? '0' : '1';
define('CATALOG_ADMIN_REQUIRE_DB', catalog_env_value('CATALOG_ADMIN_REQUIRE_DB', $catalogRequireDbDefault) === '1');

