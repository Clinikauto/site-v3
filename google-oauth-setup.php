<?php
/**
 * Clinik Auto - Assistant de configuration OAuth Google Calendar
 * Génère et valide les tokens d'authentification pour l'API Google Calendar.
 */

require_once __DIR__ . '/includes/security.php';

// CSRF: init and validate POST (toléré en local de dev)
csrf_init();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_validate_request() && !CATALOG_IS_LOCAL_RUNTIME) {
        http_response_code(400);
        echo 'Requête invalide (CSRF)';
        exit;
    }
}

// Garantir démarrage de la session si nécessaire (includes/security.php l'initialise normalement)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Configuration
const CLIENT_ID_PLACEHOLDER = 'xxx.apps.googleusercontent.com';
const CLIENT_SECRET_PLACEHOLDER = 'GOCSPX-...';
const REDIRECT_URI = 'https://www.clinikauto.fr/google-oauth-setup.php';
const GOOGLE_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';
const GOOGLE_TOKEN_URI = 'https://oauth2.googleapis.com/token';

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function escape_html($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function request_google_access_token($clientId, $clientSecret, $code) {
    $payload = [
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => GOOGLE_TOKEN_URI,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload),
        CURLOPT_SSL_VERIFYPEER => false, // Local dev fallback
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [false, "Erreur cURL: $error"];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [false, 'Réponse Google invalide: ' . substr((string) $response, 0, 100)];
    }

    if (isset($data['error'])) {
        return [false, 'Erreur Google: ' . ($data['error_description'] ?? $data['error'])];
    }

    return [true, $data];
}

// ─────────────────────────────────────────────────────────────────────────────
// Traitement POST
// ─────────────────────────────────────────────────────────────────────────────

$step = (int) ($_POST['step'] ?? 1);
$errorMsg = '';
$infoMsg = '';
$tokens = null;
$authUrl = '';

$savedClientId = $_SESSION['gc_client_id'] ?? '';
$savedClientSecret = $_SESSION['gc_client_secret'] ?? '';

// Réinitialisation explicite depuis l'UI
if (isset($_POST['reset']) && $_POST['reset'] === '1') {
    unset($_SESSION['gc_tokens']);
    header('Location: ' . REDIRECT_URI);
    exit;
}

// Étape 2 : Générer URL d'autorisation
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = trim((string) ($_POST['client_id'] ?? ''));
    $clientSecret = trim((string) ($_POST['client_secret'] ?? ''));

    if (!$clientId || !$clientSecret) {
        $errorMsg = 'Veuillez remplir Client ID et Client Secret.';
        $step = 2;
    } else {
        // Forcer un nouveau cycle OAuth complet si un ancien résultat existe.
        unset($_SESSION['gc_tokens']);
        $_SESSION['gc_client_id'] = $clientId;
        $_SESSION['gc_client_secret'] = $clientSecret;

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'include_granted_scopes' => 'false',
        ];
        $authUrl = GOOGLE_AUTH_URI . '?' . http_build_query($params);
        $infoMsg = 'Cliquez sur le bouton ci-dessous pour autoriser Clinik Auto à accéder à votre Google Agenda.';
        $step = 2;
    }
}

// Étape 3 : Traiter le callback et récupérer le refresh token
$code = $_GET['code'] ?? '';
if ($code) {
    $clientId = $_SESSION['gc_client_id'] ?? '';
    $clientSecret = $_SESSION['gc_client_secret'] ?? '';

    if (!$clientId || !$clientSecret) {
        $errorMsg = 'Session expirée. Recommencez depuis l\'étape 1.';
    } else {
        [$ok, $result] = request_google_access_token($clientId, $clientSecret, $code);

        if ($ok) {
            $tokens = $result;
            $step = 3;
            if (!empty($tokens['refresh_token'])) {
                $_SESSION['gc_tokens'] = $result;
                $infoMsg = 'Authentification réussie ! Votre Refresh Token est prêt.';
            } else {
                unset($_SESSION['gc_tokens']);
                $errorMsg = 'Google a valide l\'authentification mais n\'a pas renvoye de refresh token. Cliquez sur Recommencer puis re-validez l\'acces avec consentement.';
            }
        } else {
            $errorMsg = $result;
            $step = 2;
        }
    }
}

// Récupérer tokens de session si présents
if (isset($_SESSION['gc_tokens'])) {
    $tokens = $_SESSION['gc_tokens'];
    $step = 3;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Google Calendar OAuth Setup - Clinik Auto</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .container { background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); padding: 2rem; max-width: 600px; width: 100%; }
        h1 { color: #333; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .subtitle { color: #999; margin-bottom: 1.5rem; }
        .step-number { display: inline-block; background: #667eea; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; margin-bottom: 1rem; }
        h2 { color: #333; margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1.3rem; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        label { display: block; font-weight: 600; color: #333; margin-bottom: 0.5rem; }
        input[type="text"], input[type="email"], input[type="password"] { padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s; }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 0.8rem 1.5rem; background: #667eea; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: background 0.3s; }
        .btn:hover { background: #5568d3; }
        .btn-secondary { background: #ccc; color: #333; }
        .btn-secondary:hover { background: #bbb; }
        .error { background: #fee; border-left: 4px solid #f44; padding: 1rem; border-radius: 6px; color: #c33; margin-bottom: 1rem; }
        .success { background: #efe; border-left: 4px solid #4f4; padding: 1rem; border-radius: 6px; color: #3a3; margin-bottom: 1rem; }
        .info { background: #eef; border-left: 4px solid #44f; padding: 1rem; border-radius: 6px; color: #338; margin-bottom: 1rem; }
        pre { background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; overflow: auto; font-size: 0.9rem; margin: 1rem 0; }
        code { background: #f5f5f5; padding: 0.2rem 0.4rem; border-radius: 3px; font-family: 'Courier New', monospace; }
        .button-group { display: flex; gap: 1rem; }
        .button-group button { flex: 1; }
        .step-indicator { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
        .step-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: white; background: #ddd; }
        .step-dot.active { background: #667eea; }
        .step-dot.completed { background: #4caf50; }
    </style>
    <?php if (function_exists('csrf_print_meta_and_js')) { csrf_print_meta_and_js(); } ?>
</head>
<body>
<div class="container">
    <h1>🔐 Google Calendar OAuth</h1>
    <p class="subtitle">Configuration de l'authentification pour Clinik Auto</p>

    <div class="step-indicator">
        <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>">1</div>
        <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>">2</div>
        <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
    </div>

    <?php if ($errorMsg): ?>
        <div class="error">⚠️ <?= escape_html($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($infoMsg): ?>
        <div class="info">ℹ️ <?= escape_html($infoMsg) ?></div>
    <?php endif; ?>

    <?php if ($step < 2): ?>
        <h2>Étape 1 — Créer une application Google</h2>
        <p>Avant de continuer, vous devez :</p>
        <ol style="margin-left: 1.5rem; margin-bottom: 1.5rem;">
            <li>Aller sur <a href="https://console.developers.google.com" target="_blank">Google Cloud Console</a></li>
            <li>Créer un projet : "Clinik Auto"</li>
            <li>Activer l'API "Google Calendar"</li>
            <li>Créer des identifiants OAuth 2.0 (Type: Application Web)</li>
            <li>Ajouter ce URI en tant que redirect autorisé : <code><?= escape_html(REDIRECT_URI) ?></code></li>
            <li>Télécharger le JSON avec Client ID et Client Secret</li>
        </ol>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
            <button class="btn" type="submit">Continuer vers l'étape 2 →</button>
        </form>

    <?php elseif ($step === 2 && !$authUrl): ?>
        <h2>Étape 2 — Saisir les identifiants</h2>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
            <label for="client_id">Client ID</label>
            <input type="text" id="client_id" name="client_id" value="<?= escape_html($savedClientId) ?>" placeholder="xxx.apps.googleusercontent.com" required>
            <label for="client_secret">Client Secret</label>
            <input type="text" id="client_secret" name="client_secret" value="<?= escape_html($savedClientSecret) ?>" placeholder="GOCSPX-..." required>
            <button type="submit" class="btn">Générer l'URL d'autorisation →</button>
        </form>

    <?php elseif ($step === 2 && $authUrl): ?>
        <h2>Étape 2 — Autoriser l'accès</h2>
        <p>Cliquez ci-dessous pour autoriser Clinik Auto à accéder à votre Google Agenda :</p>
        <a class="btn" href="<?= escape_html($authUrl) ?>">Autoriser l'accès à Google Agenda →</a>

    <?php elseif ($step === 3): ?>
        <h2>Étape 3 — Refresh Token obtenu</h2>

        <?php if ($tokens && !empty($tokens['refresh_token'])): ?>
            <div class="success">✅ Authentification réussie ! Copiez le Refresh Token ci-dessous dans votre <strong>config.php</strong>.</div>

            <label>Refresh Token :</label>
            <pre id="rt"><?= escape_html($tokens['refresh_token']) ?></pre>
            <button type="button" class="btn" onclick="navigator.clipboard.writeText(document.getElementById('rt').innerText); alert('Copié !')">📋 Copier le Refresh Token</button>

            <h2 style="margin-top: 1.5rem;">Mise à jour de config.php</h2>
            <p>Modifiez ces lignes dans <code>config.php</code> :</p>
            <pre>define('GOOGLE_CALENDAR_ENABLED', true);
define('GOOGLE_CALENDAR_ID', 'primary');
define('GOOGLE_CLIENT_ID',     '<?= escape_html($_SESSION['gc_client_id'] ?? '...CLIENT_ID...') ?>');
define('GOOGLE_CLIENT_SECRET', '<?= escape_html($_SESSION['gc_client_secret'] ?? '...SECRET...') ?>');
define('GOOGLE_REFRESH_TOKEN', '<?= escape_html($tokens['refresh_token']) ?>');</pre>

            <div class="button-group">
                <form method="POST" style="flex: 1;">
                    <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                    <button type="submit" class="btn" name="reset" value="1">🔄 Recommencer</button>
                </form>
                <a href="admin.php" class="btn btn-secondary" style="text-decoration: none; text-align: center;">✓ Accueil Admin</a>
            </div>
        <?php else: ?>
            <div class="error">Erreur : Aucun token reçu. Veuillez recommencer.</div>
            <form method="POST">
                <?php echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8') . '">'; ?>
                <button type="submit" class="btn">Recommencer</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</div>
</body>
</html>