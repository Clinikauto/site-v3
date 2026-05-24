<?php
/**
 * Clinik Auto - Portail d'accès administrateur par code OTP 4 chiffres
 * Déclenché par Ctrl+Alt+N depuis l'accueil.
 * Un code à 4 chiffres aléatoire est envoyé par email, valable 10 minutes.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_store.php';

// Initialisation sécurisée de la session — adaptée pour le développement/production
// Détecte si la connexion est en HTTPS pour définir le flag `secure`.
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureFlag,
    'httponly' => true,
    'samesite' => 'Lax'
];
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    session_set_cookie_params(
        $cookieParams['lifetime'],
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// CSRF protection for admin gate
require_once __DIR__ . '/includes/security.php';
csrf_init();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_validate_request() && !gate_is_local_runtime()) {
        http_response_code(400);
        echo 'Requête invalide (CSRF)';
        exit;
    }
}

// ─── Constantes ───────────────────────────────────────────────────────────────
define('GATE_CODE_FILE',    __DIR__ . '/data/admin_gate_code.json');
define('GATE_CODE_TTL',     600); // 10 minutes
define('GATE_CODE_DIGITS',  4);
define('GATE_MAX_ATTEMPTS', 5);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function gate_load_state(): array
{
    if (!file_exists(GATE_CODE_FILE)) {
        return [];
    }
    $raw = file_get_contents(GATE_CODE_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function gate_save_state(array $data): void
{
    $dir = dirname(GATE_CODE_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(GATE_CODE_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function gate_clear_state(): void
{
    if (file_exists(GATE_CODE_FILE)) {
        unlink(GATE_CODE_FILE);
    }
}

function gate_generate_code(): string
{
    return str_pad((string) random_int(0, 9999), GATE_CODE_DIGITS, '0', STR_PAD_LEFT);
}

function gate_issue_code(): string
{
    $code = gate_generate_code();
    gate_save_state([
        'code'       => password_hash($code, PASSWORD_BCRYPT),
        'expires_at' => time() + GATE_CODE_TTL,
        'attempts'   => 0,
    ]);
    return $code;
}

function gate_code_is_valid(string $input): bool
{
    if ($input === '') {
        return false;
    }
    $state = gate_load_state();
    if (empty($state)) {
        return false;
    }
    if ((int) ($state['expires_at'] ?? 0) < time()) {
        gate_clear_state();
        return false;
    }
    $attempts = (int) ($state['attempts'] ?? 0);
    if ($attempts >= GATE_MAX_ATTEMPTS) {
        gate_clear_state();
        return false;
    }
    // Incrémenter les tentatives avant vérification
    $state['attempts'] = $attempts + 1;
    gate_save_state($state);

    if (password_verify($input, (string) ($state['code'] ?? ''))) {
        gate_clear_state();
        return true;
    }
    return false;
}

function gate_has_pending_code(): bool
{
    $state = gate_load_state();
    if (empty($state)) {
        return false;
    }
    if ((int) ($state['expires_at'] ?? 0) < time()) {
        gate_clear_state();
        return false;
    }
    if ((int) ($state['attempts'] ?? 0) >= GATE_MAX_ATTEMPTS) {
        gate_clear_state();
        return false;
    }
    return true;
}

function gate_send_code_email(string $code): bool
{
    $target = defined('ADMIN_PASSWORD_RESET_EMAIL') ? trim((string) ADMIN_PASSWORD_RESET_EMAIL) : '';
    if ($target === '' || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = 'Clinik Auto - Code d\'accès administrateur';
    $minutes = (int) (GATE_CODE_TTL / 60);
    $body =
        "Bonjour,\n\n" .
        "Votre code d'accès à l'espace administrateur Clinik Auto :\n\n" .
        "    ► " . $code . "\n\n" .
        "Ce code est valable " . $minutes . " minutes.\n" .
        "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n\n" .
        "— Clinik Auto";

    return catalog_send_email($target, $subject, $body, '');
}

function gate_is_local_runtime(): bool
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false || strpos($host, 'localhost:') === 0) {
        return true;
    }

    $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remote === '127.0.0.1' || $remote === '::1') {
        return true;
    }

    return false;
}

// ─── Routing ──────────────────────────────────────────────────────────────────

$action   = trim((string) ($_POST['action'] ?? ''));
$infoMsg  = '';
$errorMsg = '';
$step     = gate_has_pending_code() ? 'verify' : 'send'; // 'send' | 'verify'

// Déjà authentifié → aller directement à l'admin
if (!empty($_SESSION['catalog_admin_gate_ok'])) {
    header('Location: admin.php');
    exit;
}

// ── Action : envoyer le code ──────────────────────────────────────────────────
if ($action === 'send_code') {
    $code = gate_issue_code();
    if (gate_send_code_email($code)) {
        if (gate_is_local_runtime()) {
            $infoMsg = 'Mode local : code de connexion = ' . $code . ' (valable 10 minutes).';
        } else {
            $infoMsg = 'Code envoyé à l\'adresse email autorisée. Valable 10 minutes.';
        }
        $step = 'verify';
    } else {
        if (gate_is_local_runtime()) {
            $infoMsg = 'Mode local détecté : email indisponible. Code de test : ' . $code . ' (valable 10 minutes).';
            $step = 'verify';
        } else {
            gate_clear_state();
            $errorMsg = 'Erreur lors de l\'envoi de l\'email. Vérifiez la configuration SMTP.';
            $step = 'send';
        }
    }
}

// ── Action : renvoyer un nouveau code ────────────────────────────────────────
if ($action === 'resend_code') {
    gate_clear_state();
    $code = gate_issue_code();
    if (gate_send_code_email($code)) {
        if (gate_is_local_runtime()) {
            $infoMsg = 'Mode local : nouveau code de connexion = ' . $code . ' (valable 10 minutes).';
        } else {
            $infoMsg = 'Nouveau code envoyé.';
        }
        $step = 'verify';
    } else {
        if (gate_is_local_runtime()) {
            $infoMsg = 'Mode local détecté : email indisponible. Nouveau code de test : ' . $code . ' (valable 10 minutes).';
            $step = 'verify';
        } else {
            gate_clear_state();
            $errorMsg = 'Erreur lors de l\'envoi de l\'email.';
            $step = 'send';
        }
    }
}

// ── Action : vérifier le code ────────────────────────────────────────────────
if ($action === 'verify_code') {
    $input = trim((string) ($_POST['otp_code'] ?? ''));
    if ($input === '') {
        $errorMsg = 'Veuillez saisir le code.';
        $step = 'verify';
    } elseif (gate_code_is_valid($input)) {
        $_SESSION['catalog_admin_gate_ok'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $errorMsg = 'Code incorrect ou expiré. Réessayez ou demandez un nouveau code.';
        $step = gate_has_pending_code() ? 'verify' : 'send';
    }
}

// ─── Vue HTML ─────────────────────────────────────────────────────────────────
$garageName = defined('GARAGE_NOM') ? htmlspecialchars(GARAGE_NOM, ENT_QUOTES, 'UTF-8') : 'Clinik Auto';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Accès administrateur - <?= $garageName ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
    <?php if (function_exists('csrf_print_meta_and_js')) { csrf_print_meta_and_js(); } ?>
    <style>
        .gate-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f4;
        }
        .gate-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 380px;
            text-align: center;
        }
        .gate-card h1 {
            font-size: 1.3rem;
            margin-bottom: .4rem;
            color: #222;
        }
        .gate-card p.subtitle {
            color: #666;
            font-size: .92rem;
            margin-bottom: 1.5rem;
        }
        .gate-msg-info  { background:#e8f5e9; color:#2e7d32; border-radius:6px; padding:.7rem 1rem; margin-bottom:1rem; font-size:.92rem; }
        .gate-msg-error { background:#ffebee; color:#c62828; border-radius:6px; padding:.7rem 1rem; margin-bottom:1rem; font-size:.92rem; }
        .gate-input-otp {
            font-size: 2rem;
            letter-spacing: .5rem;
            text-align: center;
            width: 100%;
            border: 2px solid #ccc;
            border-radius: 8px;
            padding: .5rem .5rem;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        .gate-input-otp:focus { border-color: #1a73e8; outline: none; }
        .gate-btn-primary {
            width: 100%;
            padding: .75rem;
            background: #1a1a1a;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: .6rem;
        }
        .gate-btn-primary:hover { background: #333; }
        .gate-btn-secondary {
            background: none;
            border: none;
            color: #1a73e8;
            cursor: pointer;
            font-size: .88rem;
            text-decoration: underline;
            padding: 0;
        }
        .gate-btn-secondary:hover { color: #1558b0; }
        .gate-back { display:block; margin-top:1.2rem; font-size:.85rem; color:#999; text-decoration:none; }
        .gate-back:hover { color:#555; }
        .gate-lock-icon { font-size: 2.5rem; margin-bottom: .7rem; }
    </style>
</head>
<body>
<div class="gate-wrapper">
    <div class="gate-card">
        <div class="gate-lock-icon">🔐</div>
        <h1>Espace administrateur</h1>
        <p class="subtitle"><?= $garageName ?></p>

        <?php if ($infoMsg !== ''): ?>
            <div class="gate-msg-info"><?= htmlspecialchars($infoMsg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($errorMsg !== ''): ?>
            <div class="gate-msg-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($step === 'send'): ?>
            <!-- Étape 1 : demander l'envoi du code -->
            <p style="font-size:.9rem;color:#555;margin-bottom:1.2rem;">
                Un code à 4 chiffres sera envoyé à l'adresse email autorisée.
            </p>
            <form method="post" action="admin_gate.php">
                <input type="hidden" name="action" value="send_code">
                <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                <button type="submit" class="gate-btn-primary">Envoyer le code par email</button>
            </form>

        <?php else: ?>
            <!-- Étape 2 : saisir le code reçu -->
            <p style="font-size:.9rem;color:#555;margin-bottom:1rem;">
                Entrez le code à 4 chiffres reçu par email.
            </p>
            <form method="post" action="admin_gate.php" autocomplete="off">
                <input type="hidden" name="action" value="verify_code">
                <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                <input
                    type="text"
                    name="otp_code"
                    class="gate-input-otp"
                    maxlength="4"
                    pattern="[0-9]{4}"
                    inputmode="numeric"
                    autofocus
                    placeholder="0000"
                    required
                >
                <button type="submit" class="gate-btn-primary">Valider le code</button>
            </form>
            <form method="post" action="admin_gate.php" style="margin-top:.4rem;">
                <input type="hidden" name="action" value="resend_code">
                <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                <button type="submit" class="gate-btn-secondary">Renvoyer un nouveau code</button>
            </form>
        <?php endif; ?>

        <a href="index.html" class="gate-back">← Retour à l'accueil</a>
    </div>
</div>
</body>
</html>
