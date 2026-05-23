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

$catalogDefaultDbPort = CATALOG_IS_LOCAL_RUNTIME ? '3307' : '3306';

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

		$_ENV[$key] = $value;
		$_SERVER[$key] = $value;
		@putenv($key . '=' . $value);
	}
}

catalog_load_dotenv(__DIR__ . '/.env');

// ===== INFORMATIONS DU GARAGE =====
define('GARAGE_NOM', 'Clinik Auto');
define('GARAGE_ADRESSE', '118 Clos des Teppes, 74950 Scionzier');
define('GARAGE_TEL', '06 20 18 56 27');
define('GARAGE_EMAIL', 'contact@clinikauto.fr');
define('GARAGE_HORAIRES', 'Lun–Ven : 9h–12h / 14h–18h | Sam : 9h–12h | Dim : Fermé');

// ===== CONFIGURATION BASE DE DONNÉES =====
// À adapter selon l'environnement local ou o2switch
define('DB_HOST', catalog_env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) catalog_env_value('DB_PORT', $catalogDefaultDbPort));
define('DB_USER', catalog_env_value('DB_USER', 'root'));
define('DB_PASS', catalog_env_value('DB_PASS', 'root'));
define('DB_NAME', catalog_env_value('DB_NAME', 'clinikauto'));
define('DB_SOCKET', catalog_env_value('DB_SOCKET', ''));

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
define('EMAIL_EXPEDITEUR', catalog_env_value('EMAIL_EXPEDITEUR', 'clinikauto74@gmail.com'));

// Active l'envoi SMTP (recommandé en production). En développement par défaut désactivé.
define('SMTP_ENABLED', catalog_env_value('SMTP_ENABLED', (CATALOG_IS_LOCAL_RUNTIME ? '0' : '1')) === '1');
define('SMTP_HOST', catalog_env_value('SMTP_HOST', 'mail.clinikauto.fr'));
define('SMTP_PORT', (int) catalog_env_value('SMTP_PORT', '465'));
define('SMTP_USERNAME', catalog_env_value('SMTP_USERNAME', 'smtp@clinikauto.fr'));
define('SMTP_PASSWORD', catalog_env_value('SMTP_PASSWORD', ''));
define('SMTP_SECURE', catalog_env_value('SMTP_SECURE', 'ssl'));

// ===== SYNCHRONISATION GOOGLE AGENDA =====
// Active la synchronisation bidirectionnelle automatique des rendez-vous.
// En local: false par defaut (evite les alertes inutiles sans credentials).
// En production: true par defaut (si credentials Google renseignes).
define('GOOGLE_CALENDAR_ENABLED', CATALOG_IS_LOCAL_RUNTIME ? false : true);
define('GOOGLE_CALENDAR_ID', 'primary');
// Charger les identifiants Google depuis l'environnement (.env) pour le dev
define('GOOGLE_CLIENT_ID', catalog_env_value('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', catalog_env_value('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REFRESH_TOKEN', catalog_env_value('GOOGLE_REFRESH_TOKEN', ''));

// Chemin vers l'autoload Composer (PHPMailer)
define('COMPOSER_AUTOLOAD_PATH', __DIR__ . '/vendor/autoload.php');

// ===== MODE D'EXECUTION SECURISE (DRY-RUN) =====
// En staging/dev, activez DRY_RUN_MODE=1 pour empêcher tout envoi d'email
// ou écriture en base de données jusqu'à la validation manuelle.
define('DRY_RUN_MODE', catalog_env_value('DRY_RUN_MODE', (CATALOG_IS_LOCAL_RUNTIME ? '1' : '0')) === '1');

// ===== ACCES ADMINISTRATEUR =====
define('ADMIN_LOGIN', 'clinikauto74@gmail.com');
define('ADMIN_PASSWORD_HASH', catalog_env_value('ADMIN_PASSWORD_HASH', '$2y$10$FCHO3tzKz8.5hpztYE1dnO/DELLAvvRc5DomNYErk5lMm6Nk6VW8O'));
define('ADMIN_PASSWORD_RESET_EMAIL', 'admin@clinikauto.fr');
define('ADMIN_HIDDEN_ENTRY_ENABLED', CATALOG_IS_LOCAL_RUNTIME ? false : true);
define('ADMIN_HIDDEN_ENTRY_KEY', catalog_env_value('ADMIN_HIDDEN_ENTRY_KEY', '2mdopgLxiG4CN6PE7BcsHj5urJXyfzD8'));
// En développement local autoriser localhost explicitement pour pouvoir accéder à la zone admin.
// En production laisser vide ou renseigner les IP autorisées (ex: ['203.0.113.10']).
if (CATALOG_IS_LOCAL_RUNTIME) {
	define('ADMIN_ALLOWED_IPS', ['127.0.0.1', '::1']);
} else {
	define('ADMIN_ALLOWED_IPS', []);
}
define('ADMIN_SECURITY_NOTICE', 'Accès réservé au propriétaire.');

// ===== SECURITE / COMPORTEMENT ADMIN =====
// En local: false pour autoriser la bascule JSON si la DB est indisponible.
// En production: true pour bloquer les actions sensibles en cas de panne DB.
$catalogRequireDbDefault = CATALOG_IS_LOCAL_RUNTIME ? '0' : '1';
define('CATALOG_ADMIN_REQUIRE_DB', catalog_env_value('CATALOG_ADMIN_REQUIRE_DB', $catalogRequireDbDefault) === '1');

// Durcissement des cookies de session : s'applique avant tout session_start()
$sess_secure = (!CATALOG_IS_LOCAL_RUNTIME) && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
@session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => $sess_secure,
	'httponly' => true,
	'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}

// Charger l'autoload Composer s'il est présent (utile pour PHPMailer en dev)
if (is_readable(COMPOSER_AUTOLOAD_PATH)) {
	require_once COMPOSER_AUTOLOAD_PATH;
}

