<?php

$catalogConfigPath = dirname(__DIR__) . '/config.php';
if (is_file($catalogConfigPath)) {
    require_once $catalogConfigPath;
}

function catalog_admin_session_timeout_seconds()
{
    $configured = defined('ADMIN_SESSION_TIMEOUT_SECONDS') ? (int) ADMIN_SESSION_TIMEOUT_SECONDS : 1800;
    return $configured > 120 ? $configured : 120;
}

function catalog_admin_session_fingerprint()
{
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return hash('sha256', $ua . '|' . $ip);
}

function catalog_is_admin_session_active()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['catalog_admin']) || $_SESSION['catalog_admin'] !== true) {
        return false;
    }

    if (!isset($_SESSION['catalog_admin_gate_ok']) || $_SESSION['catalog_admin_gate_ok'] !== true) {
        return false;
    }

    $fingerprint = (string) ($_SESSION['catalog_admin_fingerprint'] ?? '');
    if ($fingerprint === '' || !hash_equals($fingerprint, catalog_admin_session_fingerprint())) {
        return false;
    }

    $lastActivity = (int) ($_SESSION['catalog_admin_last_activity'] ?? 0);
    if ($lastActivity <= 0) {
        return false;
    }

    if ((time() - $lastActivity) > catalog_admin_session_timeout_seconds()) {
        return false;
    }

    $_SESSION['catalog_admin_last_activity'] = time();
    return true;
}

function catalog_google_is_local_runtime()
{
    $httpHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (in_array($httpHost, ['127.0.0.1:8001', 'localhost:8001', '127.0.0.1', 'localhost'], true)) {
        return true;
    }

    $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    return PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR === '\\';
}

function catalog_google_curl_apply_local_ssl_fallback($ch)
{
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}

define('CATALOG_STORE_PATH', dirname(__DIR__) . '/data/catalog_data.json');

function catalog_get_google_analytics_script()
{
    $gaId = (string) (defined('GOOGLE_ANALYTICS_ID') ? GOOGLE_ANALYTICS_ID : '');
    if (empty($gaId)) {
        return '';
    }

    $escaped = htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8');
    return <<<GA
<!-- Cookie consent + Google Analytics 4 -->
<style>
    .cookie-banner-clinik {
        position: fixed;
        left: 16px;
        right: 16px;
        bottom: 16px;
        z-index: 99999;
        background: #111827;
        color: #f9fafb;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,.35);
        font-size: 14px;
        line-height: 1.45;
        display: none;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .cookie-banner-clinik p {
        margin: 0;
        max-width: 720px;
    }
    .cookie-banner-clinik a {
        color: #93c5fd;
        text-decoration: underline;
    }
    .cookie-banner-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .cookie-btn-clinik {
        border: 0;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: 700;
    }
    .cookie-btn-accept { background: #16a34a; color: #fff; }
    .cookie-btn-reject { background: #374151; color: #fff; }
    .cookie-btn-settings {
        position: fixed;
        right: 16px;
        bottom: 16px;
        z-index: 99998;
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        background: #111827;
        color: #fff;
        cursor: pointer;
        font-size: 13px;
        display: none;
    }
</style>
<script>
    (function () {
        var GA_ID = '{$escaped}';
        var CONSENT_KEY = 'clinikauto_cookie_analytics_v1';
        var gaLoaded = false;

        function eraseCookie(name) {
            var hostParts = window.location.hostname.split('.');
            var domains = [window.location.hostname];
            if (hostParts.length > 2) {
                domains.push('.' + hostParts.slice(-2).join('.'));
            }
            var paths = ['/', window.location.pathname || '/'];
            for (var i = 0; i < domains.length; i++) {
                for (var j = 0; j < paths.length; j++) {
                    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=' + paths[j] + '; domain=' + domains[i];
                }
            }
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        }

        function disableAnalyticsCookies() {
            window['ga-disable-' + GA_ID] = true;
            eraseCookie('_ga');
            eraseCookie('_gid');
            eraseCookie('_gat');
        }

        function loadAnalytics() {
            if (gaLoaded || window['ga-disable-' + GA_ID]) {
                return;
            }
            gaLoaded = true;
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID);
            document.head.appendChild(s);

            window.dataLayer = window.dataLayer || [];
            window.gtag = function(){ window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', GA_ID, { anonymize_ip: true });
        }

        function setConsent(value) {
            try {
                localStorage.setItem(CONSENT_KEY, value);
            } catch (e) {}
        }

        function getConsent() {
            try {
                return localStorage.getItem(CONSENT_KEY) || '';
            } catch (e) {
                return '';
            }
        }

        function hideBanner(showSettingsButton) {
            var banner = document.getElementById('cookie-banner-clinik');
            var settings = document.getElementById('cookie-settings-clinik');
            if (banner) {
                banner.style.display = 'none';
            }
            if (settings) {
                settings.style.display = showSettingsButton ? 'inline-block' : 'none';
            }
        }

        function showBanner() {
            var banner = document.getElementById('cookie-banner-clinik');
            var settings = document.getElementById('cookie-settings-clinik');
            if (banner) {
                banner.style.display = 'flex';
            }
            if (settings) {
                settings.style.display = 'none';
            }
        }

        function setupRejectedReprompt() {
            if (window.__clinikCookieRepromptBound) {
                return;
            }
            window.__clinikCookieRepromptBound = true;

            var reprompt = function () {
                if (getConsent() === 'rejected') {
                    showBanner();
                }
            };

            document.addEventListener('click', reprompt, true);
            document.addEventListener('submit', reprompt, true);
            document.addEventListener('keydown', reprompt, true);
        }

        function mountBanner() {
            if (document.getElementById('cookie-banner-clinik')) {
                return;
            }
            var banner = document.createElement('div');
            banner.id = 'cookie-banner-clinik';
            banner.className = 'cookie-banner-clinik';
            banner.innerHTML = '' +
                '<p>Nous utilisons des cookies de mesure d\'audience pour améliorer le site. Vous pouvez accepter ou refuser. <a href="/politique-cookies.php">Politique cookies</a>.</p>' +
                '<div class="cookie-banner-actions">' +
                    '<button type="button" class="cookie-btn-clinik cookie-btn-reject" id="cookie-reject-clinik">Refuser</button>' +
                    '<button type="button" class="cookie-btn-clinik cookie-btn-accept" id="cookie-accept-clinik">Accepter</button>' +
                '</div>';
            document.body.appendChild(banner);

            var settings = document.createElement('button');
            settings.type = 'button';
            settings.id = 'cookie-settings-clinik';
            settings.className = 'cookie-btn-settings';
            settings.textContent = 'Cookies';
            document.body.appendChild(settings);

            document.getElementById('cookie-accept-clinik').addEventListener('click', function () {
                setConsent('accepted');
                window['ga-disable-' + GA_ID] = false;
                loadAnalytics();
                hideBanner(false);
            });

            document.getElementById('cookie-reject-clinik').addEventListener('click', function () {
                setConsent('rejected');
                disableAnalyticsCookies();
                hideBanner(true);
            });

            settings.addEventListener('click', function () {
                showBanner();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            mountBanner();
            setupRejectedReprompt();
            var consent = getConsent();
            if (consent === 'accepted') {
                loadAnalytics();
                hideBanner(false);
            } else if (consent === 'rejected') {
                disableAnalyticsCookies();
                hideBanner(true);
            } else {
                showBanner();
            }
        });
    })();
</script>
<!-- End cookie consent + Google Analytics 4 -->
GA;
}

function catalog_set_runtime_error($message)
{
    $GLOBALS['catalog_runtime_error'] = trim((string) $message);
}

function catalog_append_runtime_error($message)
{
    $message = trim((string) $message);
    if ($message === '') {
        return;
    }

    $current = trim((string) ($GLOBALS['catalog_runtime_error'] ?? ''));
    if ($current === '') {
        $GLOBALS['catalog_runtime_error'] = $message;
        return;
    }

    $GLOBALS['catalog_runtime_error'] = $current . ' ' . $message;
}

function catalog_get_runtime_error()
{
    return (string) ($GLOBALS['catalog_runtime_error'] ?? '');
}

function catalog_store_path()
{
    return CATALOG_STORE_PATH;
}

function catalog_bank_accounts_file_path()
{
    return dirname(__DIR__) . '/data/admin_bank_accounts.json';
}

function catalog_bank_accounts_default()
{
    return [
        [
            'id' => 'bank_default',
            'label' => 'Compte principal',
            'beneficiary' => defined('GARAGE_NOM') ? (string) GARAGE_NOM : 'Clinik Auto',
            'iban' => '',
            'bic' => '',
            'bank_name' => '',
            'note' => 'Virement instantané uniquement. Merci d\'indiquer la référence de votre commande.',
            'is_active' => true,
            'is_default' => true,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ]
    ];
}

function catalog_bank_account_normalize($account)
{
    $normalized = [
        'id' => trim((string) ($account['id'] ?? '')),
        'label' => trim((string) ($account['label'] ?? '')),
        'beneficiary' => trim((string) ($account['beneficiary'] ?? '')),
        'iban' => strtoupper(str_replace(' ', '', trim((string) ($account['iban'] ?? '')))),
        'bic' => strtoupper(str_replace(' ', '', trim((string) ($account['bic'] ?? '')))),
        'bank_name' => trim((string) ($account['bank_name'] ?? '')),
        'note' => trim((string) ($account['note'] ?? '')),
        'is_active' => !empty($account['is_active']),
        'is_default' => !empty($account['is_default']),
        'created_at' => trim((string) ($account['created_at'] ?? '')),
        'updated_at' => trim((string) ($account['updated_at'] ?? ''))
    ];

    if ($normalized['id'] === '') {
        $normalized['id'] = 'bank_' . substr(sha1($normalized['label'] . '|' . microtime(true) . '|' . mt_rand()), 0, 12);
    }
    if ($normalized['label'] === '') {
        $normalized['label'] = 'Compte sans nom';
    }
    if ($normalized['created_at'] === '') {
        $normalized['created_at'] = date('c');
    }
    if ($normalized['updated_at'] === '') {
        $normalized['updated_at'] = date('c');
    }

    return $normalized;
}

function catalog_bank_accounts_load()
{
    $defaults = catalog_bank_accounts_default();
    $path = catalog_bank_accounts_file_path();
    if (!file_exists($path)) {
        return $defaults;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $defaults;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $accounts = [];
    $hasDefault = false;
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }
        $normalized = catalog_bank_account_normalize($item);
        if ($normalized['is_default']) {
            $hasDefault = true;
        }
        $accounts[] = $normalized;
    }

    if (empty($accounts)) {
        return $defaults;
    }

    if (!$hasDefault) {
        $accounts[0]['is_default'] = true;
    }

    return $accounts;
}

function catalog_bank_accounts_save($accounts)
{
    $path = catalog_bank_accounts_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $normalized = [];
    $hasDefault = false;
    foreach ((array) $accounts as $item) {
        if (!is_array($item)) {
            continue;
        }
        $account = catalog_bank_account_normalize($item);
        if ($account['is_default']) {
            if ($hasDefault) {
                $account['is_default'] = false;
            } else {
                $hasDefault = true;
            }
        }
        $normalized[] = $account;
    }

    if (empty($normalized)) {
        $normalized = catalog_bank_accounts_default();
    }

    if (!$hasDefault && !empty($normalized)) {
        $normalized[0]['is_default'] = true;
    }

    $payload = json_encode(array_values($normalized), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return false;
    }

    return @file_put_contents($path, $payload) !== false;
}

function catalog_bank_account_upsert($payload)
{
    $id = trim((string) ($payload['id'] ?? ''));
    $label = trim((string) ($payload['label'] ?? ''));
    $beneficiary = trim((string) ($payload['beneficiary'] ?? ''));
    $iban = strtoupper(str_replace(' ', '', trim((string) ($payload['iban'] ?? ''))));
    $bic = strtoupper(str_replace(' ', '', trim((string) ($payload['bic'] ?? ''))));
    $bankName = trim((string) ($payload['bank_name'] ?? ''));
    $note = trim((string) ($payload['note'] ?? ''));
    $isActive = !empty($payload['is_active']);
    $setDefault = !empty($payload['set_default']);

    if ($label === '' || $beneficiary === '' || $iban === '') {
        return [false, 'Libelle, beneficiaire et IBAN sont obligatoires.', null];
    }

    $accounts = catalog_bank_accounts_load();
    $updated = null;
    $found = false;

    foreach ($accounts as &$account) {
        if ((string) ($account['id'] ?? '') !== $id || $id === '') {
            continue;
        }
        $found = true;
        $account['label'] = $label;
        $account['beneficiary'] = $beneficiary;
        $account['iban'] = $iban;
        $account['bic'] = $bic;
        $account['bank_name'] = $bankName;
        $account['note'] = $note;
        $account['is_active'] = $isActive;
        $account['updated_at'] = date('c');
        if ($setDefault) {
            $account['is_default'] = true;
        }
        $updated = $account;
        break;
    }
    unset($account);

    if (!$found) {
        $newAccount = catalog_bank_account_normalize([
            'id' => $id,
            'label' => $label,
            'beneficiary' => $beneficiary,
            'iban' => $iban,
            'bic' => $bic,
            'bank_name' => $bankName,
            'note' => $note,
            'is_active' => $isActive,
            'is_default' => $setDefault,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ]);
        $accounts[] = $newAccount;
        $updated = $newAccount;
    }

    if ($setDefault && $updated) {
        foreach ($accounts as &$account) {
            $account['is_default'] = ((string) ($account['id'] ?? '') === (string) ($updated['id'] ?? ''));
        }
        unset($account);
    }

    if (!catalog_bank_accounts_save($accounts)) {
        return [false, 'Impossible d\'enregistrer le compte bancaire.', null];
    }

    return [true, $found ? 'Compte bancaire mis a jour.' : 'Compte bancaire ajoute.', $updated];
}

function catalog_bank_account_delete($id)
{
    $id = trim((string) $id);
    if ($id === '') {
        return [false, 'Compte bancaire invalide.'];
    }

    $accounts = catalog_bank_accounts_load();
    if (count($accounts) <= 1) {
        return [false, 'Conservez au moins un compte bancaire.'];
    }

    $found = false;
    $filtered = [];
    foreach ($accounts as $account) {
        if ((string) ($account['id'] ?? '') === $id) {
            $found = true;
            continue;
        }
        $filtered[] = $account;
    }

    if (!$found) {
        return [false, 'Compte bancaire introuvable.'];
    }

    $hasDefault = false;
    foreach ($filtered as $account) {
        if (!empty($account['is_default'])) {
            $hasDefault = true;
            break;
        }
    }
    if (!$hasDefault && !empty($filtered)) {
        $filtered[0]['is_default'] = true;
    }

    if (!catalog_bank_accounts_save($filtered)) {
        return [false, 'Suppression impossible pour le moment.'];
    }

    return [true, 'Compte bancaire supprime.'];
}

function catalog_bank_account_set_default($id)
{
    $id = trim((string) $id);
    if ($id === '') {
        return [false, 'Compte bancaire invalide.'];
    }

    $accounts = catalog_bank_accounts_load();
    $found = false;
    foreach ($accounts as &$account) {
        $isTarget = ((string) ($account['id'] ?? '') === $id);
        $account['is_default'] = $isTarget;
        if ($isTarget) {
            $account['updated_at'] = date('c');
            $found = true;
        }
    }
    unset($account);

    if (!$found) {
        return [false, 'Compte bancaire introuvable.'];
    }

    if (!catalog_bank_accounts_save($accounts)) {
        return [false, 'Selection du compte impossible pour le moment.'];
    }

    return [true, 'Compte bancaire selectionne pour la popup client.'];
}

function catalog_bank_account_selected()
{
    $accounts = catalog_bank_accounts_load();
    foreach ($accounts as $account) {
        if (!empty($account['is_default']) && !empty($account['is_active'])) {
            return $account;
        }
    }
    foreach ($accounts as $account) {
        if (!empty($account['is_active'])) {
            return $account;
        }
    }
    return $accounts[0] ?? null;
}

function catalog_bank_account_find_by_id($id)
{
    $id = trim((string) $id);
    if ($id === '') {
        return null;
    }

    foreach (catalog_bank_accounts_load() as $account) {
        if ((string) ($account['id'] ?? '') === $id) {
            return $account;
        }
    }

    return null;
}

function catalog_devis_config_file_path()
{
    return dirname(__DIR__) . '/data/devis_config.json';
}

function catalog_devis_config_default()
{
    return [
        'categories' => [
            [
                'id' => 'entretien-revision',
                'title' => 'Entretien & Revision',
                'icon' => '🔄',
                'hidden_on_devis' => false,
                'options' => [
                    ['label' => 'Vidange moteur', 'unavailable_on_devis' => false, 'icon' => '🛢️'],
                    ['label' => 'Changement filtre a huile', 'unavailable_on_devis' => false, 'icon' => '🧴'],
                    ['label' => 'Controle freins', 'unavailable_on_devis' => false, 'icon' => '🛑'],
                    ['label' => 'Controle batterie', 'unavailable_on_devis' => false, 'icon' => '🔋']
                ]
            ],
            [
                'id' => 'reparation-diagnostic',
                'title' => 'Reparation & Diagnostic',
                'icon' => '🔩',
                'hidden_on_devis' => false,
                'options' => [
                    ['label' => 'Diagnostic electronique', 'unavailable_on_devis' => false, 'icon' => '🧪'],
                    ['label' => 'Reparation embrayage', 'unavailable_on_devis' => false, 'icon' => '⚙️'],
                    ['label' => 'Reparation suspension', 'unavailable_on_devis' => false, 'icon' => '🔩'],
                    ['label' => 'Reparation climatisation', 'unavailable_on_devis' => false, 'icon' => '❄️']
                ]
            ],
            [
                'id' => 'services-auto',
                'title' => 'Nos services auto',
                'icon' => '⭐',
                'hidden_on_devis' => false,
                'options' => [
                    ['label' => 'Recherche vehicule d\'occasion', 'unavailable_on_devis' => false, 'icon' => '🚗'],
                    ['label' => 'Controle avant achat', 'unavailable_on_devis' => false, 'icon' => '🔎'],
                    ['label' => 'Enlevement VHU', 'unavailable_on_devis' => false, 'icon' => '🚚'],
                    ['label' => 'Controle Technique', 'unavailable_on_devis' => false, 'icon' => '✅'],
                    ['label' => 'Pre-Controle Technique', 'unavailable_on_devis' => false, 'icon' => '☑️'],
                    ['label' => 'Remorquage', 'unavailable_on_devis' => false, 'icon' => '🪝']
                ]
            ]
        ]
    ];
}

function catalog_devis_config_normalize($config)
{
    $defaults = catalog_devis_config_default();
    $categories = [];
    $seenIds = [];

    foreach ((array) ($config['categories'] ?? []) as $category) {
        if (!is_array($category)) {
            continue;
        }

        $title = trim((string) ($category['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $id = trim((string) ($category['id'] ?? ''));
        $id = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($id));
        $id = trim((string) preg_replace('/-+/', '-', (string) $id), '-');
        if ($id === '') {
            $id = 'cat-' . substr(sha1($title . '|' . microtime(true) . '|' . mt_rand()), 0, 10);
        }
        if (isset($seenIds[$id])) {
            $id = $id . '-' . substr(sha1((string) mt_rand()), 0, 4);
        }

        $icon = trim((string) ($category['icon'] ?? ''));
        if ($icon === '') {
            $icon = '🛠️';
        }

        $options = [];
        $seenOptions = [];
        foreach ((array) ($category['options'] ?? []) as $option) {
            // Support both legacy string format and new object format
            if (is_array($option)) {
                $label = trim((string) ($option['label'] ?? ''));
                $unavailable = !empty($option['unavailable_on_devis']);
                $icon = trim((string) ($option['icon'] ?? ''));
            } else {
                $label = trim((string) $option);
                $unavailable = false;
                $icon = '';
            }
            
            if ($label === '') {
                continue;
            }

            $normalizedLabel = strtolower($label);
            if (isset($seenOptions[$normalizedLabel])) {
                continue;
            }

            $seenOptions[$normalizedLabel] = true;
            $options[] = [
                'label' => $label,
                'unavailable_on_devis' => $unavailable,
                'icon' => $icon
            ];
        }

        if (empty($options)) {
            continue;
        }

        $seenIds[$id] = true;
        $categories[] = [
            'id' => $id,
            'title' => $title,
            'icon' => $icon,
            'hidden_on_devis' => !empty($category['hidden_on_devis']),
            'options' => $options
        ];
    }

    if (empty($categories)) {
        return $defaults;
    }

    return ['categories' => $categories];
}

function catalog_devis_config_load()
{
    $path = catalog_devis_config_file_path();
    if (!file_exists($path)) {
        return catalog_devis_config_default();
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return catalog_devis_config_default();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return catalog_devis_config_default();
    }

    return catalog_devis_config_normalize($decoded);
}

function catalog_devis_config_save($config)
{
    $path = catalog_devis_config_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $normalized = catalog_devis_config_normalize(is_array($config) ? $config : []);
    $payload = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return false;
    }

    return @file_put_contents($path, $payload) !== false;
}

function catalog_db_has_column($connection, $table, $column)
{
    $table = $connection->real_escape_string($table);
    $column = $connection->real_escape_string($column);
    $result = $connection->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if (!$result) {
        return false;
    }
    $exists = $result->num_rows > 0;
    $result->free();
    return $exists;
}

function catalog_postal_reference_source_path()
{
    $workspaceRoot = dirname(dirname(__DIR__));
    $candidates = [
        dirname(__DIR__) . '/data/postal_codes.csv',
        $workspaceRoot . '/base de nées la poste france/019HexaSmal.csv',
        $workspaceRoot . '/base de nees la poste france/019HexaSmal.csv'
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    $globMatches = glob($workspaceRoot . '/*/019HexaSmal.csv');
    if (is_array($globMatches)) {
        foreach ($globMatches as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
    }

    return '';
}

function catalog_to_utf8($value)
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }

    if (preg_match('//u', $value)) {
        return $value;
    }

    if (function_exists('mb_convert_encoding')) {
        return (string) mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
    }

    if (function_exists('iconv')) {
        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
        if ($converted !== false) {
            return (string) $converted;
        }
    }

    return $value;
}

function catalog_postal_city_display_name($communeName, $line5)
{
    $communeName = trim((string) $communeName);
    $line5 = trim((string) $line5);
    return $line5 !== '' ? $line5 : $communeName;
}

function catalog_postal_reference_ensure_loaded($connection)
{
    static $importChecked = false;

    if ($importChecked) {
        return;
    }
    $importChecked = true;

    $sourcePath = catalog_postal_reference_source_path();
    if ($sourcePath === '' || !is_file($sourcePath)) {
        return;
    }

    $result = $connection->query('SELECT COUNT(*) AS total FROM postal_code_reference');
    $row = $result ? $result->fetch_assoc() : null;
    $referenceCount = (int) ($row['total'] ?? 0);
    if ($result) {
        $result->free();
    }

    $currentMtime = (int) @filemtime($sourcePath);
    $metaResult = $connection->query('SELECT source_mtime FROM postal_code_reference_meta WHERE id = 1 LIMIT 1');
    $metaRow = $metaResult ? $metaResult->fetch_assoc() : null;
    $knownMtime = (int) ($metaRow['source_mtime'] ?? 0);
    if ($metaResult) {
        $metaResult->free();
    }

    if ($referenceCount > 0 && $knownMtime === $currentMtime) {
        return;
    }

    $handle = @fopen($sourcePath, 'rb');
    if (!$handle) {
        catalog_append_runtime_error('Import code postal impossible: lecture CSV refusee.');
        return;
    }

    $connection->query('TRUNCATE TABLE postal_code_reference');

    $header = fgetcsv($handle, 0, ';');
    if (!is_array($header)) {
        fclose($handle);
        catalog_append_runtime_error('Import code postal impossible: entete CSV invalide.');
        return;
    }

    $insert = $connection->prepare(
        'INSERT INTO postal_code_reference (insee_code, commune_name, postal_code, routing_label, line5, city_name) VALUES (?, ?, ?, ?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE commune_name = VALUES(commune_name), routing_label = VALUES(routing_label), line5 = VALUES(line5), city_name = VALUES(city_name)'
    );
    if (!$insert) {
        fclose($handle);
        catalog_append_runtime_error('Import code postal impossible: preparation SQL refusee.');
        return;
    }

    $rowsImported = 0;
    while (($rowData = fgetcsv($handle, 0, ';')) !== false) {
        if (!is_array($rowData) || count($rowData) < 4) {
            continue;
        }

        $inseeCode = trim(catalog_to_utf8((string) ($rowData[0] ?? '')));
        $communeName = trim(catalog_to_utf8((string) ($rowData[1] ?? '')));
        $postalCode = preg_replace('/\D+/', '', (string) ($rowData[2] ?? ''));
        $routingLabel = trim(catalog_to_utf8((string) ($rowData[3] ?? '')));
        $line5 = trim(catalog_to_utf8((string) ($rowData[4] ?? '')));
        $cityName = catalog_postal_city_display_name($communeName, $line5);

        if ($postalCode === '' || $cityName === '') {
            continue;
        }

        $insert->bind_param('ssssss', $inseeCode, $communeName, $postalCode, $routingLabel, $line5, $cityName);
        if ($insert->execute()) {
            $rowsImported++;
        }
    }

    fclose($handle);
    $insert->close();

    $meta = $connection->prepare(
        'INSERT INTO postal_code_reference_meta (id, source_name, source_mtime, imported_at, row_count) VALUES (1, ?, ?, NOW(), ?) '
        . 'ON DUPLICATE KEY UPDATE source_name = VALUES(source_name), source_mtime = VALUES(source_mtime), imported_at = VALUES(imported_at), row_count = VALUES(row_count)'
    );
    if ($meta) {
        $sourceName = basename($sourcePath);
        $meta->bind_param('sii', $sourceName, $currentMtime, $rowsImported);
        $meta->execute();
        $meta->close();
    }
}

function catalog_lookup_cities_by_postal_code_csv($postalCode, $limit = 20)
{
    static $postalIndex = null;

    $postalCode = preg_replace('/\D+/', '', (string) $postalCode);
    if ($postalCode === '') {
        return [];
    }

    if ($postalIndex === null) {
        $postalIndex = [];
        $sourcePath = catalog_postal_reference_source_path();
        if ($sourcePath !== '' && is_file($sourcePath)) {
            $handle = @fopen($sourcePath, 'rb');
            if ($handle) {
                fgetcsv($handle, 0, ';');
                while (($rowData = fgetcsv($handle, 0, ';')) !== false) {
                    if (!is_array($rowData) || count($rowData) < 4) {
                        continue;
                    }

                    $rowPostalCode = preg_replace('/\D+/', '', (string) ($rowData[2] ?? ''));
                    if ($rowPostalCode === '') {
                        continue;
                    }

                    $communeName = trim(catalog_to_utf8((string) ($rowData[1] ?? '')));
                    $line5 = trim(catalog_to_utf8((string) ($rowData[4] ?? '')));
                    $cityName = catalog_postal_city_display_name($communeName, $line5);
                    if ($cityName === '') {
                        continue;
                    }

                    if (!isset($postalIndex[$rowPostalCode])) {
                        $postalIndex[$rowPostalCode] = [];
                    }
                    $postalIndex[$rowPostalCode][$cityName] = true;
                }
                fclose($handle);
            }
        }
    }

    if (!isset($postalIndex[$postalCode])) {
        return [];
    }

    $cities = array_keys($postalIndex[$postalCode]);
    sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
    return array_slice($cities, 0, max(1, min(50, (int) $limit)));
}

function catalog_lookup_cities_by_postal_code($postalCode, $limit = 20)
{
    $postalCode = preg_replace('/\D+/', '', (string) $postalCode);
    if ($postalCode === '') {
        return [];
    }

    $limit = max(1, min(50, (int) $limit));

    if (!catalog_using_database()) {
        return catalog_lookup_cities_by_postal_code_csv($postalCode, $limit);
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return catalog_lookup_cities_by_postal_code_csv($postalCode, $limit);
    }

    // Ensure postal reference table is populated from CSV before querying.
    catalog_postal_reference_ensure_loaded($connection);

    $statement = $connection->prepare(
        'SELECT city_name FROM postal_code_reference WHERE postal_code = ? GROUP BY city_name ORDER BY city_name ASC LIMIT ' . $limit
    );
    if (!$statement) {
        return catalog_lookup_cities_by_postal_code_csv($postalCode, $limit);
    }

    $statement->bind_param('s', $postalCode);
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    $cities = array_values(array_filter(array_map(function ($row) {
        return trim((string) ($row['city_name'] ?? ''));
    }, $rows)));

    if (!empty($cities)) {
        return $cities;
    }

    return catalog_lookup_cities_by_postal_code_csv($postalCode, $limit);
}

function catalog_db_apply_migrations($connection)
{
    $connection->query("CREATE TABLE IF NOT EXISTS catalog_annonces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('vehicle', 'part') NOT NULL,
        titre VARCHAR(190) NOT NULL,
        sous_titre VARCHAR(255) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        resume_court TEXT NOT NULL,
        description_longue MEDIUMTEXT NOT NULL,
        renseignements MEDIUMTEXT NOT NULL,
        statut ENUM('available', 'reserved') DEFAULT 'available',
        acompte_confirme BOOLEAN DEFAULT FALSE,
        current_vehicle_request_id VARCHAR(80) NULL,
        current_part_request_id VARCHAR(80) NULL,
        transaction_in_progress BOOLEAN DEFAULT FALSE,
        transaction_started_at DATETIME NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    if (!catalog_db_has_column($connection, 'catalog_annonces', 'current_vehicle_request_id')) {
        $connection->query('ALTER TABLE catalog_annonces ADD COLUMN current_vehicle_request_id VARCHAR(80) NULL AFTER acompte_confirme');
    }
    if (!catalog_db_has_column($connection, 'catalog_annonces', 'current_part_request_id')) {
        $connection->query('ALTER TABLE catalog_annonces ADD COLUMN current_part_request_id VARCHAR(80) NULL AFTER current_vehicle_request_id');
    }
    if (!catalog_db_has_column($connection, 'catalog_annonces', 'transaction_in_progress')) {
        $connection->query('ALTER TABLE catalog_annonces ADD COLUMN transaction_in_progress BOOLEAN DEFAULT FALSE AFTER current_part_request_id');
    }
    if (!catalog_db_has_column($connection, 'catalog_annonces', 'transaction_started_at')) {
        $connection->query('ALTER TABLE catalog_annonces ADD COLUMN transaction_started_at DATETIME NULL AFTER transaction_in_progress');
    }

    $connection->query("CREATE TABLE IF NOT EXISTS catalog_annonce_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annonce_id INT NOT NULL,
        nom_fichier VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        image_blob LONGBLOB NOT NULL,
        ordre_affichage INT DEFAULT 0,
        date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_catalog_images_annonce
            FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
            ON DELETE CASCADE
    )");

    $connection->query("CREATE TABLE IF NOT EXISTS catalog_vehicle_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annonce_id INT NOT NULL,
        firstname VARCHAR(120) NOT NULL,
        lastname VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        desired_date DATE NULL,
        message TEXT,
        request_status ENUM('queued', 'active', 'failed', 'closed') DEFAULT 'queued',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_catalog_vehicle_requests_annonce
            FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
            ON DELETE CASCADE
    )");
    if (!catalog_db_has_column($connection, 'catalog_vehicle_requests', 'request_status')) {
        $connection->query("ALTER TABLE catalog_vehicle_requests ADD COLUMN request_status ENUM('queued', 'active', 'failed', 'closed') DEFAULT 'queued' AFTER message");
    }

    $connection->query("CREATE TABLE IF NOT EXISTS catalog_part_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annonce_id INT NOT NULL,
        firstname VARCHAR(120) NOT NULL,
        lastname VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        message TEXT,
        request_status ENUM('queued', 'active', 'failed', 'closed') DEFAULT 'queued',
        transfer_verification_status VARCHAR(20) NOT NULL DEFAULT 'none',
        transfer_declared_at DATETIME NULL,
        transfer_deadline_at DATETIME NULL,
        transfer_verified_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_catalog_part_requests_annonce
            FOREIGN KEY (annonce_id) REFERENCES catalog_annonces(id)
            ON DELETE CASCADE
    )");
    if (!catalog_db_has_column($connection, 'catalog_part_requests', 'transfer_verification_status')) {
        $connection->query("ALTER TABLE catalog_part_requests ADD COLUMN transfer_verification_status VARCHAR(20) NOT NULL DEFAULT 'none' AFTER request_status");
    }
    if (!catalog_db_has_column($connection, 'catalog_part_requests', 'transfer_declared_at')) {
        $connection->query('ALTER TABLE catalog_part_requests ADD COLUMN transfer_declared_at DATETIME NULL AFTER transfer_verification_status');
    }
    if (!catalog_db_has_column($connection, 'catalog_part_requests', 'transfer_deadline_at')) {
        $connection->query('ALTER TABLE catalog_part_requests ADD COLUMN transfer_deadline_at DATETIME NULL AFTER transfer_declared_at');
    }
    if (!catalog_db_has_column($connection, 'catalog_part_requests', 'transfer_verified_at')) {
        $connection->query('ALTER TABLE catalog_part_requests ADD COLUMN transfer_verified_at DATETIME NULL AFTER transfer_deadline_at');
    }

    $connection->query("CREATE TABLE IF NOT EXISTS customer_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_type VARCHAR(20) NOT NULL DEFAULT 'individual',
        firstname VARCHAR(120) NOT NULL,
        lastname VARCHAR(120) NOT NULL,
        address_line VARCHAR(255) NOT NULL,
        postal_code VARCHAR(10) NOT NULL DEFAULT '',
        city VARCHAR(160) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        registration VARCHAR(40) NOT NULL,
        last_source VARCHAR(40) DEFAULT 'contact',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_customer_profiles_email (email),
        KEY idx_customer_profiles_phone (phone),
        KEY idx_customer_profiles_registration (registration)
    )");
    if (!catalog_db_has_column($connection, 'customer_profiles', 'customer_type')) {
        $connection->query("ALTER TABLE customer_profiles ADD COLUMN customer_type VARCHAR(20) NOT NULL DEFAULT 'individual' AFTER id");
        $connection->query("UPDATE customer_profiles SET customer_type = 'individual' WHERE customer_type IS NULL OR customer_type = ''");
    }
    if (!catalog_db_has_column($connection, 'customer_profiles', 'company_name')) {
        $connection->query("ALTER TABLE customer_profiles ADD COLUMN company_name VARCHAR(191) NOT NULL DEFAULT '' AFTER customer_type");
    }
    if (!catalog_db_has_column($connection, 'customer_profiles', 'postal_code')) {
        $connection->query("ALTER TABLE customer_profiles ADD COLUMN postal_code VARCHAR(10) NOT NULL DEFAULT '' AFTER address_line");
    }
    if (!catalog_db_has_column($connection, 'customer_profiles', 'city')) {
        $connection->query("ALTER TABLE customer_profiles ADD COLUMN city VARCHAR(160) NOT NULL DEFAULT '' AFTER postal_code");
    }

    $connection->query("CREATE TABLE IF NOT EXISTS catalog_transaction_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_type ENUM('vehicle', 'part') NOT NULL,
        item_id INT NOT NULL,
        event_name VARCHAR(80) NOT NULL,
        outcome ENUM('concluded', 'failed', 'pending') DEFAULT 'pending',
        metadata TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_catalog_transaction_outcome (outcome),
        KEY idx_catalog_transaction_created_at (created_at)
    )");

    $connection->query("CREATE TABLE IF NOT EXISTS site_visit_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        path_key VARCHAR(120) NOT NULL,
        visit_date DATE NOT NULL,
        hits INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_site_visit_path_date (path_key, visit_date)
    )");

    $connection->query('CREATE TABLE IF NOT EXISTS rendez_vous (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        service VARCHAR(120) NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT "En attente"
    )');

    if (!catalog_db_has_column($connection, 'rendez_vous', 'telephone')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN telephone VARCHAR(30) NULL AFTER email');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'address_line')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN address_line VARCHAR(255) NOT NULL DEFAULT '' AFTER telephone");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'postal_code')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN postal_code VARCHAR(10) NOT NULL DEFAULT '' AFTER address_line");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'city')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN city VARCHAR(160) NOT NULL DEFAULT '' AFTER postal_code");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'heure')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN heure VARCHAR(10) NULL AFTER date');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'reminder_sent_at')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN reminder_sent_at DATETIME NULL AFTER status');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'reminder_status')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN reminder_status VARCHAR(40) DEFAULT "pending" AFTER reminder_sent_at');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'request_context_type')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN request_context_type VARCHAR(30) NOT NULL DEFAULT '' AFTER reminder_status");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'linked_annonce_id')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN linked_annonce_id INT NULL AFTER request_context_type');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'linked_request_id')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN linked_request_id VARCHAR(80) NOT NULL DEFAULT '' AFTER linked_annonce_id");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'cancellation_reason')) {
        $connection->query("ALTER TABLE rendez_vous ADD COLUMN cancellation_reason VARCHAR(191) NOT NULL DEFAULT '' AFTER linked_request_id");
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'updated_at')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER date_creation');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'google_event_id')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN google_event_id VARCHAR(255) NULL AFTER reminder_status');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'google_etag')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN google_etag VARCHAR(255) NULL AFTER google_event_id');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'google_synced_at')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN google_synced_at DATETIME NULL AFTER google_etag');
    }
    if (!catalog_db_has_column($connection, 'rendez_vous', 'sync_source')) {
        $connection->query('ALTER TABLE rendez_vous ADD COLUMN sync_source VARCHAR(20) DEFAULT "local" AFTER google_synced_at');
    }

    $connection->query('CREATE TABLE IF NOT EXISTS google_calendar_sync_state (
        id TINYINT PRIMARY KEY,
        sync_token TEXT NULL,
        last_sync_at DATETIME NULL,
        last_error TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');

    $connection->query('CREATE TABLE IF NOT EXISTS demandes_devis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_type VARCHAR(20) NOT NULL DEFAULT "individual",
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        adresse VARCHAR(255) NOT NULL,
        postal_code VARCHAR(10) NOT NULL DEFAULT "",
        city VARCHAR(160) NOT NULL DEFAULT "",
        email VARCHAR(100) NOT NULL,
        telephone VARCHAR(30) NOT NULL,
        immatriculation VARCHAR(30) NOT NULL,
        sujet VARCHAR(200) NOT NULL,
        prestations TEXT NOT NULL,
        message TEXT NOT NULL,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut VARCHAR(20) DEFAULT "Nouveau"
    )');
    if (!catalog_db_has_column($connection, 'demandes_devis', 'customer_type')) {
        $connection->query("ALTER TABLE demandes_devis ADD COLUMN customer_type VARCHAR(20) NOT NULL DEFAULT 'individual' AFTER id");
        $connection->query("UPDATE demandes_devis SET customer_type = 'individual' WHERE customer_type IS NULL OR customer_type = ''");
    }
    if (!catalog_db_has_column($connection, 'demandes_devis', 'postal_code')) {
        $connection->query("ALTER TABLE demandes_devis ADD COLUMN postal_code VARCHAR(10) NOT NULL DEFAULT '' AFTER adresse");
    }
    if (!catalog_db_has_column($connection, 'demandes_devis', 'city')) {
        $connection->query("ALTER TABLE demandes_devis ADD COLUMN city VARCHAR(160) NOT NULL DEFAULT '' AFTER postal_code");
    }

    $connection->query('CREATE TABLE IF NOT EXISTS postal_code_reference (
        id INT AUTO_INCREMENT PRIMARY KEY,
        insee_code VARCHAR(10) NOT NULL DEFAULT "",
        commune_name VARCHAR(191) NOT NULL,
        postal_code VARCHAR(10) NOT NULL,
        routing_label VARCHAR(191) NOT NULL DEFAULT "",
        line5 VARCHAR(191) NOT NULL DEFAULT "",
        city_name VARCHAR(191) NOT NULL,
        UNIQUE KEY uq_postal_reference (postal_code, city_name),
        KEY idx_postal_reference_code (postal_code),
        KEY idx_postal_reference_city (city_name)
    )');

    $connection->query('CREATE TABLE IF NOT EXISTS postal_code_reference_meta (
        id TINYINT PRIMARY KEY,
        source_name VARCHAR(255) NOT NULL DEFAULT "",
        source_mtime BIGINT NOT NULL DEFAULT 0,
        imported_at DATETIME NULL,
        row_count INT NOT NULL DEFAULT 0
    )');

    catalog_postal_reference_ensure_loaded($connection);
}

function catalog_db_connection()
{
    static $connection = false;

    if ($connection !== false) {
        return $connection;
    }

    foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $constant) {
        if (!defined($constant)) {
            $connection = null;
            return $connection;
        }
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $dbPort = defined('DB_PORT') ? (int) DB_PORT : 3306;
    $candidate = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $dbPort);
    if ($candidate->connect_error) {
        catalog_set_runtime_error('Connexion base de donnees impossible: ' . $candidate->connect_error);
        $connection = null;
        return $connection;
    }

    $candidate->set_charset('utf8mb4');

    catalog_db_apply_migrations($candidate);

    foreach (['catalog_annonces', 'catalog_annonce_images', 'catalog_vehicle_requests', 'catalog_part_requests', 'customer_profiles', 'catalog_transaction_events', 'site_visit_stats', 'rendez_vous'] as $table) {
        $result = $candidate->query("SHOW TABLES LIKE '" . $candidate->real_escape_string($table) . "'");
        if (!$result || $result->num_rows === 0) {
            if ($result) {
                $result->free();
            }
            catalog_set_runtime_error('Table manquante en base: ' . $table);
            $candidate->close();
            $connection = null;
            return $connection;
        }
        $result->free();
    }

    // La table de synchro Google est technique : on tente de la recréer,
    // mais son absence ne doit pas bloquer tout le back-office.
    $candidate->query('CREATE TABLE IF NOT EXISTS google_calendar_sync_state (
        id TINYINT PRIMARY KEY,
        sync_token TEXT NULL,
        last_sync_at DATETIME NULL,
        last_error TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');

    $connection = $candidate;
    return $connection;
}

function catalog_using_database()
{
    return catalog_db_connection() instanceof mysqli;
}

function catalog_svg_placeholder($title, $accent)
{
    $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="#111a2f"/>'
        . '<stop offset="100%" stop-color="' . $accent . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="1200" height="800" fill="url(#g)"/>'
        . '<circle cx="935" cy="175" r="135" fill="rgba(255,255,255,0.1)"/>'
        . '<circle cx="195" cy="640" r="180" fill="rgba(255,255,255,0.08)"/>'
        . '<text x="80" y="350" fill="#ffffff" font-size="78" font-family="Segoe UI, Arial, sans-serif" font-weight="700">Clinik Auto</text>'
        . '<text x="80" y="450" fill="#dff7ff" font-size="48" font-family="Segoe UI, Arial, sans-serif">' . $safe_title . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function catalog_default_data()
{
    $now = date('c');

    return [
        'last_id' => 4,
        'items' => [
            [
                'id' => 1,
                'type' => 'vehicle',
                'title' => 'Peugeot 208 Allure',
                'subtitle' => '1.2 PureTech 100 - 2020 - 68 500 km',
                'price' => 11990,
                'short_description' => 'Citadine soignee, carnet a jour, ideale premier achat.',
                'description' => "Peugeot 208 Allure en excellent etat general. Controle realise, entretien suivi et essai possible sur rendez-vous.\n\nEquipements principaux : ecran tactile, climatisation automatique, radar de recul, regulateur et Bluetooth.",
                'specs' => "Marque : Peugeot\nModele : 208 Allure\nAnnee : 2020\nKilometrage : 68 500 km\nCarburant : Essence\nBoite : Manuelle\nCouleur : Gris Artense",
                'status' => 'available',
                'payment_confirmed' => false,
                'images' => [
                    [
                        'id' => 'img_1_a',
                        'name' => 'peugeot-208-allure.svg',
                        'mime' => 'image/svg+xml',
                        'data' => catalog_svg_placeholder('Peugeot 208 Allure', '#e52337')
                    ]
                ],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'id' => 2,
                'type' => 'vehicle',
                'title' => 'Renault Clio V Business',
                'subtitle' => '1.5 Blue dCi 85 - 2019 - 94 300 km',
                'price' => 10490,
                'short_description' => 'Vehicule propre, economique et pret a partir.',
                'description' => "Renault Clio V Business avec historique d'entretien connu. Le vehicule est visible a l'atelier et peut etre essaye apres prise de contact.\n\nPneumatiques recents, double des cles et dossier d'entretien disponible.",
                'specs' => "Marque : Renault\nModele : Clio V Business\nAnnee : 2019\nKilometrage : 94 300 km\nCarburant : Diesel\nBoite : Manuelle\nCouleur : Blanc Glacier",
                'status' => 'available',
                'payment_confirmed' => false,
                'images' => [
                    [
                        'id' => 'img_2_a',
                        'name' => 'renault-clio-v.svg',
                        'mime' => 'image/svg+xml',
                        'data' => catalog_svg_placeholder('Renault Clio V', '#23b9e6')
                    ]
                ],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'id' => 3,
                'type' => 'part',
                'title' => 'Optique avant gauche Peugeot 208',
                'subtitle' => 'Référence 9812345677 - pièce d\'occasion contrôlée',
                'price' => 180,
                'short_description' => 'Optique complète, fixations contrôlées, prête à monter.',
                'description' => "Optique avant gauche d'occasion contrôlée en atelier. Compatible avec Peugeot 208 phase 2. État propre, vitrage sain, connectique vérifiée.\n\nRéservation possible avec acompte de 30 % par virement instantané.",
                'specs' => "Famille : Eclairage\nCompatibilité : Peugeot 208 phase 2\nÉtat : Très bon état\nRéférence : 9812345677\nGarantie : 3 mois",
                'status' => 'available',
                'payment_confirmed' => false,
                'images' => [
                    [
                        'id' => 'img_3_a',
                        'name' => 'optique-208.svg',
                        'mime' => 'image/svg+xml',
                        'data' => catalog_svg_placeholder('Optique 208', '#ffbe2e')
                    ]
                ],
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'id' => 4,
                'type' => 'part',
                'title' => 'Jante aluminium Renault Clio',
                'subtitle' => '16 pouces - finition argent - vendue à l\'unité',
                'price' => 95,
                'short_description' => 'Jante d\'occasion contrôlée, sans fissure ni voile détecté.',
                'description' => "Jante aluminium d'occasion contrôlée. Équilibrage possible à l'atelier avant retrait.\n\nPour réserver la pièce, un acompte de 30 % est demandé. La pièce passe en indisponible dès validation de cet acompte.",
                'specs' => "Famille : Roue\nCompatibilité : Renault Clio IV\nDiamètre : 16 pouces\nEntraxe : 4x100\nÉtat : Bon état",
                'status' => 'available',
                'payment_confirmed' => false,
                'images' => [
                    [
                        'id' => 'img_4_a',
                        'name' => 'jante-clio.svg',
                        'mime' => 'image/svg+xml',
                        'data' => catalog_svg_placeholder('Jante Clio', '#24a86a')
                    ]
                ],
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]
    ];
}

function catalog_bootstrap_store()
{
    $path = catalog_store_path();
    $directory = dirname($path);

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    if (!file_exists($path)) {
        file_put_contents(
            $path,
            json_encode(catalog_default_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

function catalog_load_store()
{
    catalog_bootstrap_store();

    $raw = file_get_contents(catalog_store_path());
    $data = json_decode($raw ?: '', true);

    if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
        $data = catalog_default_data();
        catalog_save_store($data);
    }

    if (!isset($data['last_id'])) {
        $data['last_id'] = 0;
        foreach ($data['items'] as $item) {
            $data['last_id'] = max($data['last_id'], (int) ($item['id'] ?? 0));
        }
    }

    return $data;
}

function catalog_save_store($data)
{
    catalog_bootstrap_store();

    file_put_contents(
        catalog_store_path(),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function catalog_empty_item($type = 'vehicle')
{
    return [
        'id' => 0,
        'type' => $type === 'part' ? 'part' : 'vehicle',
        'title' => '',
        'subtitle' => '',
        'price' => '',
        'short_description' => '',
        'description' => '',
        'specs' => '',
        'status' => 'available',
        'payment_confirmed' => false,
        'transaction_in_progress' => false,
        'transaction_started_at' => '',
        'current_vehicle_request_id' => '',
        'vehicle_requests' => [],
        'part_requests' => [],
        'current_part_request_id' => '',
        'images' => [],
        'created_at' => '',
        'updated_at' => ''
    ];
}

function catalog_part_transfer_deadline_hours()
{
    return 12;
}

function catalog_normalize_part_request($request)
{
    $request = is_array($request) ? $request : [];
    return [
        'id' => (string) ($request['id'] ?? ''),
        'firstname' => (string) ($request['firstname'] ?? ''),
        'lastname' => (string) ($request['lastname'] ?? ''),
        'email' => (string) ($request['email'] ?? ''),
        'phone' => (string) ($request['phone'] ?? ''),
        'message' => (string) ($request['message'] ?? ''),
        'request_status' => (string) ($request['request_status'] ?? 'queued'),
        'transfer_verification_status' => (string) ($request['transfer_verification_status'] ?? 'none'),
        'transfer_declared_at' => (string) ($request['transfer_declared_at'] ?? ''),
        'transfer_deadline_at' => (string) ($request['transfer_deadline_at'] ?? ''),
        'transfer_verified_at' => (string) ($request['transfer_verified_at'] ?? ''),
        'created_at' => (string) ($request['created_at'] ?? '')
    ];
}

function catalog_part_current_request($part)
{
    $currentId = (string) ($part['current_part_request_id'] ?? '');
    if ($currentId === '') {
        return null;
    }

    foreach ((array) ($part['part_requests'] ?? []) as $request) {
        $request = catalog_normalize_part_request($request);
        if ((string) ($request['id'] ?? '') === $currentId) {
            return $request;
        }
    }

    return null;
}

function catalog_part_request_is_pending_transfer($request)
{
    $request = catalog_normalize_part_request($request);
    return ($request['request_status'] ?? 'queued') === 'active'
        && ($request['transfer_verification_status'] ?? 'none') === 'pending';
}

function catalog_part_request_remaining_seconds($request)
{
    $request = catalog_normalize_part_request($request);
    if (!catalog_part_request_is_pending_transfer($request)) {
        return null;
    }

    $deadline = trim((string) ($request['transfer_deadline_at'] ?? ''));
    if ($deadline === '') {
        return null;
    }

    $deadlineTs = strtotime($deadline);
    if ($deadlineTs === false) {
        return null;
    }

    return max(0, $deadlineTs - time());
}

function catalog_normalize_item($item)
{
    $base = catalog_empty_item(($item['type'] ?? 'vehicle'));
    $normalized = array_merge($base, is_array($item) ? $item : []);

    $normalized['id'] = (int) ($normalized['id'] ?? 0);
    $normalized['price'] = is_numeric($normalized['price'] ?? null) ? (float) $normalized['price'] : 0;
    $normalized['payment_confirmed'] = !empty($normalized['payment_confirmed']);
    $normalized['transaction_in_progress'] = !empty($normalized['transaction_in_progress']);
    $normalized['current_vehicle_request_id'] = (string) ($normalized['current_vehicle_request_id'] ?? '');
    $normalized['vehicle_requests'] = is_array($normalized['vehicle_requests']) ? $normalized['vehicle_requests'] : [];
    $normalized['part_requests'] = is_array($normalized['part_requests'])
        ? array_values(array_map('catalog_normalize_part_request', $normalized['part_requests']))
        : [];
    $normalized['current_part_request_id'] = (string) ($normalized['current_part_request_id'] ?? '');

    return $normalized;
}

function catalog_resize_and_compress_image($imageBinary, $mimeType)
{
    // Si GD n'est pas disponible, retourner l'image originale
    if (!extension_loaded('gd')) {
        return $imageBinary;
    }

    // Charger l'image depuis le blob
    $image = @imagecreatefromstring($imageBinary);
    if ($image === false) {
        return $imageBinary; // Si problème de lecture, retourner l'original
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $maxWidth = 1200;
    $maxHeight = 800;

    // Calculer les nouvelles dimensions (garde aspect ratio)
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio < 1) {
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            imagedestroy($image);
            return $imageBinary;
        }
        
        // Préserver transparence pour PNG/GIF
        if (in_array($mimeType, ['image/png', 'image/gif'], true)) {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    // Compresser selon le mime type
    ob_start();
    if ($mimeType === 'image/jpeg') {
        imagejpeg($image, null, 80); // Qualité 80%
    } elseif ($mimeType === 'image/png') {
        imagepng($image, null, 8); // Compression 8 (max)
    } elseif ($mimeType === 'image/webp') {
        imagewebp($image, null, 80);
    } else {
        imagegif($image);
    }
    $compressed = ob_get_clean();
    imagedestroy($image);
    
    // Utiliser la version compressée si plus petite
    if (is_string($compressed) && strlen($compressed) < strlen($imageBinary)) {
        return $compressed;
    }
    return $imageBinary;
}

function catalog_send_email($to, $subject, $body, $replyTo = '')
{
    $to = trim((string) $to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // En mode développement local, capturer l'email dans un fichier JSON au lieu de l'envoyer
    if (defined('CATALOG_IS_LOCAL_RUNTIME') && CATALOG_IS_LOCAL_RUNTIME) {
        $emailLogDir = dirname(__DIR__) . '/email-logs';
        if (!is_dir($emailLogDir)) {
            @mkdir($emailLogDir, 0777, true);
        }

        $emailRecord = [
            'id' => uniqid('email-', true),
            'from' => defined('EMAIL_EXPEDITEUR') ? EMAIL_EXPEDITEUR : 'no-reply@localhost',
            'to' => [$to],
            'reply_to' => $replyTo ?: null,
            'subject' => $subject,
            'body' => $body,
            'timestamp' => time(),
            'type' => 'dev_capture'
        ];

        $logFile = $emailLogDir . '/' . $emailRecord['id'] . '.json';
        @file_put_contents($logFile, json_encode($emailRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return true; // Succès (capturé localement)
    }

    if (defined('COMPOSER_AUTOLOAD_PATH') && file_exists(COMPOSER_AUTOLOAD_PATH)) {
        require_once COMPOSER_AUTOLOAD_PATH;
    }

    $smtp_ready =
        defined('SMTP_ENABLED') && SMTP_ENABLED &&
        class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') &&
        defined('SMTP_HOST') && SMTP_HOST !== '' &&
        defined('SMTP_USERNAME') && SMTP_USERNAME !== '' &&
        defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '';

    if ($smtp_ready) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            if (defined('SMTP_SECURE') && SMTP_SECURE !== '') {
                $mail->SMTPSecure = SMTP_SECURE;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(defined('EMAIL_EXPEDITEUR') ? EMAIL_EXPEDITEUR : $to, defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');
            $mail->addAddress($to);
            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }
            $mail->Subject = $subject;
            $mail->Body = $body;
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    if (!function_exists('mail')) {
        return false;
    }

    $headers =
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "From: " . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto') . " <" . (defined('EMAIL_EXPEDITEUR') ? EMAIL_EXPEDITEUR : $to) . ">\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: " . $replyTo . "\r\n";
    }

    return @mail($to, $subject, $body, $headers);
}

function catalog_track_visit($pathKey)
{
    if (!catalog_using_database()) {
        return false;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return false;
    }

    $pathKey = trim((string) $pathKey);
    if ($pathKey === '') {
        $pathKey = 'unknown';
    }

    $visitDate = date('Y-m-d');
    $statement = $connection->prepare(
        'INSERT INTO site_visit_stats (path_key, visit_date, hits) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE hits = hits + 1'
    );
    if (!$statement) {
        return false;
    }
    $statement->bind_param('ss', $pathKey, $visitDate);
    $ok = $statement->execute();
    $statement->close();

    return $ok;
}

function catalog_sum_visit_hits_since($date)
{
    if (!catalog_using_database()) {
        return 0;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return 0;
    }

    $statement = $connection->prepare('SELECT COALESCE(SUM(hits), 0) AS total FROM site_visit_stats WHERE visit_date >= ?');
    if (!$statement) {
        return 0;
    }
    $statement->bind_param('s', $date);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return (int) ($row['total'] ?? 0);
}

function catalog_log_transaction_event($itemType, $itemId, $eventName, $outcome, $metadata = '')
{
    if (!catalog_using_database()) {
        return false;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return false;
    }

    $itemType = ($itemType === 'part') ? 'part' : 'vehicle';
    $outcome = in_array($outcome, ['concluded', 'failed', 'pending'], true) ? $outcome : 'pending';
    $statement = $connection->prepare(
        'INSERT INTO catalog_transaction_events (item_type, item_id, event_name, outcome, metadata) VALUES (?, ?, ?, ?, ?)'
    );
    if (!$statement) {
        return false;
    }

    $itemId = (int) $itemId;
    $eventName = trim((string) $eventName);
    $metadata = trim((string) $metadata);
    $statement->bind_param('sisss', $itemType, $itemId, $eventName, $outcome, $metadata);
    $ok = $statement->execute();
    $statement->close();

    return $ok;
}

function catalog_customer_profiles($search = '', $customerType = 'all', $sortBy = 'updated_desc')
{
    if (!catalog_using_database()) {
        return [];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $search = trim((string) $search);
    $customerType = trim((string) $customerType);
    if (!in_array($customerType, ['all', 'individual', 'professional'], true)) {
        $customerType = 'all';
    }

    $orderBy = 'updated_at DESC';
    if ($sortBy === 'name_asc') {
        $orderBy = 'lastname ASC, firstname ASC, updated_at DESC';
    } elseif ($sortBy === 'name_desc') {
        $orderBy = 'lastname DESC, firstname DESC, updated_at DESC';
    } elseif ($sortBy === 'recent_first') {
        $orderBy = 'created_at DESC, updated_at DESC';
    } elseif ($sortBy === 'oldest_first') {
        $orderBy = 'created_at ASC, updated_at ASC';
    } elseif ($sortBy === 'type_then_name') {
        $orderBy = "customer_type DESC, lastname ASC, firstname ASC, updated_at DESC";
    } elseif ($sortBy === 'incomplete_only') {
        $orderBy = 'updated_at DESC';
    }

    $conditions = [];
    $types = '';
    $params = [];

    if ($search !== '') {
        $like = '%' . $search . '%';
        $conditions[] = '(firstname LIKE ? OR lastname LIKE ? OR company_name LIKE ? OR email LIKE ? OR phone LIKE ? OR registration LIKE ? OR postal_code LIKE ? OR city LIKE ?)';
        $types .= 'ssssssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($customerType !== 'all') {
        $conditions[] = 'customer_type = ?';
        $types .= 's';
        $params[] = $customerType;
    }
    if ($sortBy === 'incomplete_only') {
        $conditions[] = "(TRIM(COALESCE(firstname, '')) = '' OR TRIM(COALESCE(lastname, '')) = '' OR TRIM(COALESCE(email, '')) = '' OR TRIM(COALESCE(phone, '')) = '')";
    }

    $sql = 'SELECT * FROM customer_profiles';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY ' . $orderBy . ' LIMIT 500';

    $statement = $connection->prepare($sql);
    if (!$statement) {
        return [];
    }
    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    return $rows;
}

function catalog_get_customer_profile_by_id($id)
{
    if (!catalog_using_database()) {
        return null;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return null;
    }

    $statement = $connection->prepare('SELECT * FROM customer_profiles WHERE id = ? LIMIT 1');
    if (!$statement) {
        return null;
    }
    $id = (int) $id;
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $row ?: null;
}

function catalog_get_customer_profile_by_phone($phone)
{
    if (!catalog_using_database()) {
        return null;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return null;
    }

    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return null;
    }

    $variants = [$digits];
    if (strpos($digits, '33') === 0 && strlen($digits) > 2) {
        $variants[] = '0' . substr($digits, 2);
    }
    if (strpos($digits, '0') === 0 && strlen($digits) > 1) {
        $variants[] = '33' . substr($digits, 1);
    }
    $variants = array_values(array_unique(array_filter($variants, function ($item) {
        return is_string($item) && $item !== '';
    })));

    if (empty($variants)) {
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $normalizedExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '+', ''), '-', ''), '.', ''), '(', ''), ')', ''), '/', ''), CHAR(9), '')";
    $sql = 'SELECT * FROM customer_profiles WHERE ' . $normalizedExpr . ' IN (' . $placeholders . ') ORDER BY updated_at DESC LIMIT 1';
    $statement = $connection->prepare($sql);
    if (!$statement) {
        return null;
    }

    $types = str_repeat('s', count($variants));
    $statement->bind_param($types, ...$variants);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $row ?: null;
}

function catalog_update_customer_profile($id, $payload)
{
    if (!catalog_using_database()) {
        return [false, 'Base indisponible'];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, 'Base indisponible'];
    }

    $id = (int) $id;
    if ($id <= 0) {
        return [false, 'Client invalide'];
    }

    $customerType = trim((string) ($payload['customer_type'] ?? 'individual'));
    if (!in_array($customerType, ['individual', 'professional'], true)) {
        $customerType = 'individual';
    }
    $companyName = trim((string) ($payload['company_name'] ?? ''));
    $firstname = trim((string) ($payload['firstname'] ?? ''));
    $lastname = trim((string) ($payload['lastname'] ?? ''));
    $address = trim((string) ($payload['address_line'] ?? ''));
    $postalCode = preg_replace('/\s+/', '', strtoupper(trim((string) ($payload['postal_code'] ?? ''))));
    $city = trim((string) ($payload['city'] ?? ''));
    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $registration = strtoupper(trim((string) ($payload['registration'] ?? '')));

    if ($firstname === '' || $lastname === '' || $email === '') {
        return [false, 'Raison sociale / nom, contact / prénom et email sont obligatoires'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Email client invalide'];
    }

    $check = $connection->prepare('SELECT id FROM customer_profiles WHERE LOWER(email) = ? AND id <> ? LIMIT 1');
    if ($check) {
        $check->bind_param('si', $email, $id);
        $check->execute();
        $result = $check->get_result();
        if ($result && $result->fetch_assoc()) {
            $check->close();
            return [false, 'Cet email est déjà utilisé par un autre client'];
        }
        $check->close();
    }

    $statement = $connection->prepare('UPDATE customer_profiles SET customer_type = ?, company_name = ?, firstname = ?, lastname = ?, address_line = ?, postal_code = ?, city = ?, email = ?, phone = ?, registration = ?, last_source = ? WHERE id = ?');
    if (!$statement) {
        return [false, 'Mise à jour client impossible'];
    }

    $source = 'admin';
    $statement->bind_param('sssssssssssi', $customerType, $companyName, $firstname, $lastname, $address, $postalCode, $city, $email, $phone, $registration, $source, $id);
    $statement->execute();
    $statement->close();

    return [true, 'Fiche client mise à jour'];
}

function catalog_create_customer_profile($payload, $source = 'admin_manual')
{
    if (!catalog_using_database()) {
        return [false, 'Base indisponible'];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, 'Base indisponible'];
    }

    $customerType = trim((string) ($payload['customer_type'] ?? 'individual'));
    if (!in_array($customerType, ['individual', 'professional'], true)) {
        $customerType = 'individual';
    }
    $companyName = trim((string) ($payload['company_name'] ?? ''));
    $firstname = trim((string) ($payload['firstname'] ?? ''));
    $lastname = trim((string) ($payload['lastname'] ?? ''));
    $address = trim((string) ($payload['address_line'] ?? ''));
    $postalCode = preg_replace('/\s+/', '', strtoupper(trim((string) ($payload['postal_code'] ?? ''))));
    $city = trim((string) ($payload['city'] ?? ''));
    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $registration = strtoupper(trim((string) ($payload['registration'] ?? '')));
    $source = trim((string) $source);
    if ($source === '') {
        $source = 'admin_manual';
    }

    if ($firstname === '' || $lastname === '' || $email === '') {
        return [false, 'Raison sociale / nom, contact / prénom et email sont obligatoires'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Email client invalide'];
    }

    $existing = catalog_get_customer_profile(['email' => $email]);
    if ($existing) {
        return [false, 'Une fiche existe déjà avec cet email'];
    }

    $insert = $connection->prepare('INSERT INTO customer_profiles (customer_type, company_name, firstname, lastname, address_line, postal_code, city, email, phone, registration, last_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$insert) {
        return [false, 'Création fiche client impossible'];
    }
    $insert->bind_param('sssssssssss', $customerType, $companyName, $firstname, $lastname, $address, $postalCode, $city, $email, $phone, $registration, $source);
    $ok = $insert->execute();
    $newId = (int) $insert->insert_id;
    $insert->close();

    if (!$ok) {
        return [false, 'Création fiche client impossible'];
    }

    return [true, 'Fiche client créée', $newId];
}

function catalog_delete_customer_profile($id)
{
    if (!catalog_using_database()) {
        return [false, 'Base indisponible'];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, 'Base indisponible'];
    }

    $id = (int) $id;
    if ($id <= 0) {
        return [false, 'Client invalide'];
    }

    $existing = catalog_get_customer_profile_by_id($id);
    if (!$existing) {
        return [false, 'Fiche client introuvable'];
    }

    $statement = $connection->prepare('DELETE FROM customer_profiles WHERE id = ? LIMIT 1');
    if (!$statement) {
        return [false, 'Suppression de la fiche client impossible'];
    }

    $statement->bind_param('i', $id);
    $ok = $statement->execute();
    $affected = $statement->affected_rows;
    $statement->close();

    if (!$ok || $affected <= 0) {
        return [false, 'Aucune fiche client supprimee'];
    }

    return [true, 'Fiche client supprimee'];
}

function catalog_customer_rdv_timeline($customerId, $limit = 50)
{
    if (!catalog_using_database()) {
        return [];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $customer = catalog_get_customer_profile_by_id($customerId);
    if (!$customer) {
        return [];
    }

    $email = strtolower(trim((string) ($customer['email'] ?? '')));
    $phone = trim((string) ($customer['phone'] ?? ''));
    $limit = max(1, min(200, (int) $limit));

    $conditions = [];
    $types = '';
    $params = [];

    if ($email !== '') {
        $conditions[] = 'LOWER(email) = ?';
        $types .= 's';
        $params[] = $email;
    }
    if ($phone !== '') {
        $conditions[] = 'telephone = ?';
        $types .= 's';
        $params[] = $phone;
    }

    if (empty($conditions)) {
        return [];
    }

    $sql = 'SELECT id, nom, email, telephone, address_line, postal_code, city, date, heure, service, status, reminder_sent_at, reminder_status '
        . 'FROM rendez_vous WHERE ' . implode(' OR ', $conditions)
        . ' ORDER BY date DESC, heure DESC, id DESC LIMIT ' . $limit;
    $statement = $connection->prepare($sql);
    if (!$statement) {
        return [];
    }

    $statement->bind_param($types, ...$params);
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    return $rows;
}

function catalog_send_customer_campaign($customerIds, $subject, $body, $replyTo = '')
{
    if (!catalog_using_database()) {
        return [0, 0, 0, []];
    }

    $subject = trim((string) $subject);
    $body = trim((string) $body);
    if ($subject === '' || $body === '') {
        return [0, 0, 0, []];
    }

    $ids = [];
    foreach ((array) $customerIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    if (empty($ids)) {
        return [0, 0, 0, []];
    }

    $sent = 0;
    $failed = 0;
    $skipped = 0;
    $details = [];

    foreach ($ids as $id) {
        $customer = catalog_get_customer_profile_by_id($id);
        if (!$customer) {
            $skipped++;
            continue;
        }

        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $skipped++;
            continue;
        }

        $ok = catalog_send_email($email, $subject, $body, $replyTo);
        if ($ok) {
            $sent++;
        } else {
            $failed++;
            $details[] = [
                'id' => $id,
                'email' => $email,
                'name' => trim((string) (($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? '')))
            ];
        }
    }

    return [$sent, $failed, $skipped, $details];
}

function catalog_send_rdv_reminders($appointmentDate)
{
    if (!catalog_using_database()) {
        return [0, 0];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [0, 0];
    }

    $statement = $connection->prepare('SELECT id, nom, email, telephone, address_line, postal_code, city, date, heure, service, status FROM rendez_vous WHERE date = ?');
    if (!$statement) {
        return [0, 0];
    }

    $appointmentDate = trim((string) $appointmentDate);
    $statement->bind_param('s', $appointmentDate);
    $statement->execute();
    $result = $statement->get_result();

    $sent = 0;
    $failed = 0;
    while ($row = $result->fetch_assoc()) {
        $subject = '[Clinik Auto] Rappel rendez-vous du ' . date('d/m/Y', strtotime((string) $row['date']));
        $body =
            "Bonjour " . ($row['nom'] ?? '') . ",\n\n" .
            "Nous vous rappelons votre rendez-vous prévu chez Clinik Auto.\n\n" .
            "Date : " . ($row['date'] ?? '') . "\n" .
            "Heure : " . (!empty($row['heure']) ? (string) $row['heure'] : 'A confirmer') . "\n" .
            "Objet : " . ($row['service'] ?? '') . "\n" .
            "Statut : " . ($row['status'] ?? 'En attente') . "\n\n" .
            "Si vous souhaitez modifier ce rendez-vous, merci de nous répondre.\n\n" .
            "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

        $ok = catalog_send_email((string) ($row['email'] ?? ''), $subject, $body);
        $update = $connection->prepare('UPDATE rendez_vous SET reminder_sent_at = NOW(), reminder_status = ? WHERE id = ?');
        if ($update) {
            $status = $ok ? 'sent' : 'failed';
            $id = (int) ($row['id'] ?? 0);
            $update->bind_param('si', $status, $id);
            $update->execute();
            $update->close();
        }

        if ($ok) {
            $sent++;
        } else {
            $failed++;
        }
    }

    $statement->close();
    return [$sent, $failed];
}

function catalog_rdv_get_by_id($id)
{
    if (!catalog_using_database()) {
        return null;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return null;
    }

    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    $statement = $connection->prepare('SELECT id, nom, email, telephone, address_line, postal_code, city, date, heure, service, status, reminder_sent_at, reminder_status, request_context_type, linked_annonce_id, linked_request_id, cancellation_reason, google_event_id, google_etag, google_synced_at, sync_source FROM rendez_vous WHERE id = ? LIMIT 1');
    if (!$statement) {
        return null;
    }

    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return is_array($row) ? $row : null;
}

function catalog_send_rdv_reminder_by_id($id)
{
    $appointment = catalog_rdv_get_by_id($id);
    if (!$appointment) {
        return [false, 'Rendez-vous introuvable.'];
    }

    $subject = '[Clinik Auto] Rappel rendez-vous du ' . date('d/m/Y', strtotime((string) ($appointment['date'] ?? '')));
    $body =
        "Bonjour " . ($appointment['nom'] ?? '') . ",\n\n" .
        "Nous vous rappelons votre rendez-vous prévu chez Clinik Auto.\n\n" .
        "Date : " . ($appointment['date'] ?? '') . "\n" .
        "Heure : " . (!empty($appointment['heure']) ? (string) $appointment['heure'] : 'A confirmer') . "\n" .
        "Objet : " . ($appointment['service'] ?? '') . "\n" .
        "Statut : " . ($appointment['status'] ?? 'En attente') . "\n\n" .
        "Si vous souhaitez modifier ce rendez-vous, merci de nous répondre.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    $ok = catalog_send_email((string) ($appointment['email'] ?? ''), $subject, $body);

    $connection = catalog_db_connection();
    if ($connection) {
        $update = $connection->prepare('UPDATE rendez_vous SET reminder_sent_at = NOW(), reminder_status = ? WHERE id = ?');
        if ($update) {
            $status = $ok ? 'sent' : 'failed';
            $rdvId = (int) ($appointment['id'] ?? 0);
            $update->bind_param('si', $status, $rdvId);
            $update->execute();
            $update->close();
        }
    }

    if ($ok) {
        return [true, 'Relance envoyee au client.'];
    }

    return [false, 'Echec d\'envoi de la relance client.'];
}

function catalog_update_rdv($id, $payload)
{
    if (!catalog_using_database()) {
        return [false, 'Base de donnees indisponible.'];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, 'Base de donnees indisponible.'];
    }

    $id = (int) $id;
    if ($id <= 0) {
        return [false, 'Rendez-vous invalide.'];
    }

    $nom = trim((string) ($payload['nom'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $telephone = trim((string) ($payload['telephone'] ?? ''));
    $addressLine = trim((string) ($payload['address_line'] ?? ''));
    $postalCode = preg_replace('/\s+/', '', strtoupper(trim((string) ($payload['postal_code'] ?? ''))));
    $city = trim((string) ($payload['city'] ?? ''));
    $date = trim((string) ($payload['date'] ?? ''));
    $heure = trim((string) ($payload['heure'] ?? ''));
    $service = trim((string) ($payload['service'] ?? ''));
    $status = trim((string) ($payload['status'] ?? 'En attente'));

    if ($nom === '' || $date === '' || $service === '') {
        return [false, 'Nom, date et service sont obligatoires.'];
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return [false, 'Date invalide (format attendu: AAAA-MM-JJ).'];
    }
    if ($heure !== '' && !preg_match('/^\d{2}:\d{2}$/', $heure)) {
        return [false, 'Heure invalide (format attendu: HH:MM).'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Adresse email invalide.'];
    }
    if (!in_array($status, ['En attente', 'Confirme', 'Annule', 'Termine'], true)) {
        $status = 'En attente';
    }

    $statement = $connection->prepare('UPDATE rendez_vous SET nom = ?, email = ?, telephone = ?, address_line = ?, postal_code = ?, city = ?, date = ?, heure = ?, service = ?, status = ?, sync_source = "local" WHERE id = ?');
    if (!$statement) {
        return [false, 'Mise a jour du rendez-vous impossible.'];
    }

    $statement->bind_param('ssssssssssi', $nom, $email, $telephone, $addressLine, $postalCode, $city, $date, $heure, $service, $status, $id);
    $ok = $statement->execute();
    $statement->close();

    if (!$ok) {
        return [false, 'Echec de mise a jour du rendez-vous.'];
    }

    return [true, 'Rendez-vous mis a jour.'];
}

function catalog_delete_rdv($id)
{
    if (!catalog_using_database()) {
        return [false, 'Base de donnees indisponible.'];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, 'Base de donnees indisponible.'];
    }

    $id = (int) $id;
    if ($id <= 0) {
        return [false, 'Rendez-vous invalide.'];
    }

    $appointment = catalog_rdv_get_by_id($id);
    if (!$appointment) {
        return [false, 'Rendez-vous introuvable.'];
    }

    $googleEventId = trim((string) ($appointment['google_event_id'] ?? ''));
    $googleDeleteNote = '';

    if ($googleEventId !== '' && catalog_google_calendar_enabled()) {
        list($tokenOk, $tokenOrError) = catalog_google_calendar_access_token();
        if ($tokenOk) {
            $accessToken = (string) $tokenOrError;
            list($ok, $httpCode, $payload, $error) = catalog_google_calendar_request($accessToken, 'DELETE', '/events/' . rawurlencode($googleEventId), [], null);
            if (!$ok && $httpCode !== 404) {
                return [false, 'Suppression Google impossible: ' . $error];
            }
            if ($httpCode === 404) {
                $googleDeleteNote = ' (événement Google déjà absent)';
            }
        } else {
            return [false, 'Suppression Google impossible: ' . (string) $tokenOrError];
        }
    }

    $statement = $connection->prepare('DELETE FROM rendez_vous WHERE id = ? LIMIT 1');
    if (!$statement) {
        return [false, 'Suppression locale impossible.'];
    }

    $statement->bind_param('i', $id);
    $ok = $statement->execute();
    $affected = $statement->affected_rows;
    $statement->close();

    if (!$ok || $affected <= 0) {
        return [false, 'Aucun rendez-vous supprime.'];
    }

    return [true, 'Rendez-vous supprime avec succes' . $googleDeleteNote . '.'];
}

function catalog_cancel_linked_rdv($requestType, $requestId, $reason = '')
{
    if (!catalog_using_database()) {
        return 0;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return 0;
    }

    $requestType = trim((string) $requestType);
    $requestId = trim((string) $requestId);
    $reason = trim((string) $reason);
    if ($requestType === '' || $requestId === '') {
        return 0;
    }

    $select = $connection->prepare('SELECT id, nom, email, date, heure, service FROM rendez_vous WHERE request_context_type = ? AND linked_request_id = ? AND status <> "Annule"');
    if (!$select) {
        return 0;
    }

    $select->bind_param('ss', $requestType, $requestId);
    $select->execute();
    $result = $select->get_result();
    $appointments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $select->close();

    if (empty($appointments)) {
        return 0;
    }

    $update = $connection->prepare('UPDATE rendez_vous SET status = "Annule", cancellation_reason = ?, sync_source = "local" WHERE request_context_type = ? AND linked_request_id = ? AND status <> "Annule"');
    if ($update) {
        $update->bind_param('sss', $reason, $requestType, $requestId);
        $update->execute();
        $update->close();
    }

    foreach ($appointments as $appointment) {
        $email = trim((string) ($appointment['email'] ?? ''));
        if ($email === '') {
            continue;
        }
        $subject = '[Clinik Auto] Rendez-vous annule';
        $body =
            "Bonjour " . (string) ($appointment['nom'] ?? '') . ",\n\n" .
            "Votre rendez-vous prevu le " . (string) ($appointment['date'] ?? '') .
            (!empty($appointment['heure']) ? (' a ' . (string) $appointment['heure']) : '') .
            " pour \"" . (string) ($appointment['service'] ?? '') . "\" a ete annule.\n" .
            ($reason !== '' ? ("Motif : " . $reason . "\n\n") : "\n") .
            "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');
        catalog_send_email($email, $subject, $body);
    }

    return count($appointments);
}

function catalog_rdv_for_date($appointmentDate)
{
    if (!catalog_using_database()) {
        return [];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $appointmentDate = trim((string) $appointmentDate);
    if ($appointmentDate === '') {
        return [];
    }

    $statement = $connection->prepare(
        'SELECT id, nom, email, telephone, address_line, postal_code, city, date, heure, service, status, reminder_sent_at, reminder_status, request_context_type, linked_annonce_id, linked_request_id, cancellation_reason FROM rendez_vous WHERE date = ? ORDER BY heure ASC, id ASC'
    );
    if (!$statement) {
        return [];
    }

    $statement->bind_param('s', $appointmentDate);
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    foreach ($rows as &$row) {
        $profile = catalog_get_customer_profile([
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['telephone'] ?? ''),
            'registration' => ''
        ]);

        $row['customer_profile'] = is_array($profile) ? $profile : null;
        $row['customer_identified'] = is_array($profile);
    }
    unset($row);

    return $rows;
}

function catalog_rdv_for_period($startDate, $endDate)
{
    if (!catalog_using_database()) {
        return [];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $startDate = trim((string) $startDate);
    $endDate = trim((string) $endDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        return [];
    }
    if ($startDate > $endDate) {
        return [];
    }

    $statement = $connection->prepare(
        'SELECT id, nom, email, telephone, address_line, postal_code, city, date, heure, service, status, reminder_sent_at, reminder_status, request_context_type, linked_annonce_id, linked_request_id, cancellation_reason, google_event_id, sync_source
         FROM rendez_vous
         WHERE date BETWEEN ? AND ?
         ORDER BY date ASC, heure ASC, id ASC'
    );
    if (!$statement) {
        return [];
    }

    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    foreach ($rows as &$row) {
        $profile = catalog_get_customer_profile([
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['telephone'] ?? ''),
            'registration' => ''
        ]);

        $row['customer_profile'] = is_array($profile) ? $profile : null;
        $row['customer_identified'] = is_array($profile);
    }
    unset($row);

    return $rows;
}

function catalog_google_calendar_enabled()
{
    return
        defined('GOOGLE_CALENDAR_ENABLED') && GOOGLE_CALENDAR_ENABLED &&
        defined('GOOGLE_CALENDAR_ID') && trim((string) GOOGLE_CALENDAR_ID) !== '' &&
        defined('GOOGLE_CLIENT_ID') && trim((string) GOOGLE_CLIENT_ID) !== '' &&
        defined('GOOGLE_CLIENT_SECRET') && trim((string) GOOGLE_CLIENT_SECRET) !== '' &&
        defined('GOOGLE_REFRESH_TOKEN') && trim((string) GOOGLE_REFRESH_TOKEN) !== '' &&
        function_exists('curl_init');
}

function catalog_google_calendar_get_state($connection)
{
    $state = [
        'id' => 1,
        'sync_token' => null,
        'last_sync_at' => null,
        'last_error' => null
    ];

    $connection->query('CREATE TABLE IF NOT EXISTS google_calendar_sync_state (
        id TINYINT PRIMARY KEY,
        sync_token TEXT NULL,
        last_sync_at DATETIME NULL,
        last_error TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');

    $result = $connection->query('SELECT id, sync_token, last_sync_at, last_error FROM google_calendar_sync_state WHERE id = 1 LIMIT 1');
    $row = $result ? $result->fetch_assoc() : null;
    if ($result) {
        $result->free();
    }

    if ($row) {
        return $row;
    }

    $connection->query("INSERT INTO google_calendar_sync_state (id, sync_token, last_sync_at, last_error) VALUES (1, NULL, NULL, NULL)");
    return $state;
}

function catalog_google_calendar_update_state($connection, $payload)
{
    $connection->query('CREATE TABLE IF NOT EXISTS google_calendar_sync_state (
        id TINYINT PRIMARY KEY,
        sync_token TEXT NULL,
        last_sync_at DATETIME NULL,
        last_error TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');

    $syncToken = array_key_exists('sync_token', $payload) ? $payload['sync_token'] : null;
    $lastSyncAt = array_key_exists('last_sync_at', $payload) ? $payload['last_sync_at'] : null;
    $lastError = array_key_exists('last_error', $payload) ? $payload['last_error'] : null;

    $statement = $connection->prepare('UPDATE google_calendar_sync_state SET sync_token = ?, last_sync_at = ?, last_error = ? WHERE id = 1');
    if (!$statement) {
        return false;
    }

    $statement->bind_param('sss', $syncToken, $lastSyncAt, $lastError);
    $ok = $statement->execute();
    $statement->close();
    return $ok;
}

function catalog_google_calendar_access_token()
{
    $postFields = [
        'client_id' => (string) GOOGLE_CLIENT_ID,
        'client_secret' => (string) GOOGLE_CLIENT_SECRET,
        'refresh_token' => (string) GOOGLE_REFRESH_TOKEN,
        'grant_type' => 'refresh_token'
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    if (!$ch) {
        return [false, 'Impossible d\'initialiser la requête OAuth'];
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Fallback local Windows: certains environnements n'ont pas de CA bundle à jour.
    // En production, on garde la verification SSL stricte.
    if ($response === false && stripos($error, 'SSL certificate problem') !== false && catalog_google_is_local_runtime()) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        if (!$ch) {
            return [false, 'Impossible d\'initialiser la requête OAuth'];
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        catalog_google_curl_apply_local_ssl_fallback($ch);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if ($response === false) {
        return [false, $error !== '' ? $error : 'Réponse OAuth vide'];
    }

    $decoded = json_decode($response, true);
    if ($statusCode < 200 || $statusCode >= 300 || !is_array($decoded) || empty($decoded['access_token'])) {
        $message = is_array($decoded) && !empty($decoded['error_description'])
            ? (string) $decoded['error_description']
            : 'Erreur OAuth Google (HTTP ' . $statusCode . ')';
        return [false, $message];
    }

    return [true, (string) $decoded['access_token']];
}

function catalog_google_calendar_request($accessToken, $method, $path, $query = [], $body = null)
{
    $calendarId = rawurlencode((string) GOOGLE_CALENDAR_ID);
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . $path;
    if (!empty($query)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $ch = curl_init($url);
    if (!$ch) {
        return [false, 0, null, 'Impossible d\'initialiser cURL'];
    }

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper((string) $method));

    if ($body !== null) {
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false && stripos($error, 'SSL certificate problem') !== false && catalog_google_is_local_runtime()) {
        $ch = curl_init($url);
        if (!$ch) {
            return [false, 0, null, 'Impossible d\'initialiser cURL'];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper((string) $method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        catalog_google_curl_apply_local_ssl_fallback($ch);

        if ($body !== null) {
            $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if ($response === false) {
        return [false, $statusCode, null, $error !== '' ? $error : 'Réponse vide'];
    }

    $decoded = json_decode($response, true);
    if ($statusCode < 200 || $statusCode >= 300) {
        $message = is_array($decoded) && isset($decoded['error']['message'])
            ? (string) $decoded['error']['message']
            : 'Erreur Google Calendar (HTTP ' . $statusCode . ')';
        return [false, $statusCode, is_array($decoded) ? $decoded : null, $message];
    }

    return [true, $statusCode, is_array($decoded) ? $decoded : null, ''];
}

function catalog_google_calendar_rdv_to_event($rdv)
{
    $date = trim((string) ($rdv['date'] ?? ''));
    $time = trim((string) ($rdv['heure'] ?? ''));
    $name = trim((string) ($rdv['nom'] ?? 'Client'));
    $service = trim((string) ($rdv['service'] ?? 'Rendez-vous atelier'));
    $email = trim((string) ($rdv['email'] ?? ''));
    $phone = trim((string) ($rdv['telephone'] ?? ''));

    $payload = [
        'summary' => 'RDV Clinik Auto - ' . $name,
        'description' =>
            'Service: ' . $service . "\n" .
            'Client: ' . $name . "\n" .
            'Telephone: ' . ($phone !== '' ? $phone : 'N/A') . "\n" .
            'Email: ' . ($email !== '' ? $email : 'N/A') . "\n" .
            'Source: ClinikAuto',
        'location' => defined('GARAGE_ADRESSE') ? (string) GARAGE_ADRESSE : 'Clinik Auto'
    ];

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^\d{2}:\d{2}$/', $time)) {
        $start = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time, new DateTimeZone('Europe/Paris'));
        if ($start instanceof DateTime) {
            $end = clone $start;
            $end->modify('+1 hour');
            $payload['start'] = [
                'dateTime' => $start->format(DateTime::RFC3339),
                'timeZone' => 'Europe/Paris'
            ];
            $payload['end'] = [
                'dateTime' => $end->format(DateTime::RFC3339),
                'timeZone' => 'Europe/Paris'
            ];
        }
    }

    if (!isset($payload['start'])) {
        $startDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
        $endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
        $payload['start'] = ['date' => $startDate];
        $payload['end'] = ['date' => $endDate];
    }

    return $payload;
}

function catalog_google_calendar_parse_description($description, $label)
{
    foreach (preg_split('/\r\n|\r|\n/', (string) $description) as $line) {
        if (stripos($line, $label . ':') === 0) {
            return trim((string) substr($line, strlen($label) + 1));
        }
    }
    return '';
}

function catalog_google_calendar_event_to_rdv($event)
{
    $summary = trim((string) ($event['summary'] ?? 'Rendez-vous')); 
    $description = (string) ($event['description'] ?? '');

    $date = '';
    $time = '';
    if (!empty($event['start']['dateTime'])) {
        $start = new DateTime((string) $event['start']['dateTime']);
        $start->setTimezone(new DateTimeZone('Europe/Paris'));
        $date = $start->format('Y-m-d');
        $time = $start->format('H:i');
    } elseif (!empty($event['start']['date'])) {
        $date = (string) $event['start']['date'];
    }

    $name = preg_replace('/^RDV Clinik Auto\s*-\s*/i', '', $summary);
    if ($name === '') {
        $name = catalog_google_calendar_parse_description($description, 'Client');
    }

    $service = catalog_google_calendar_parse_description($description, 'Service');
    if ($service === '') {
        $service = $summary;
    }

    return [
        'nom' => $name !== '' ? $name : 'Client',
        'email' => catalog_google_calendar_parse_description($description, 'Email'),
        'telephone' => catalog_google_calendar_parse_description($description, 'Telephone'),
        'date' => $date,
        'heure' => $time,
        'service' => $service,
        'status' => ((string) ($event['status'] ?? 'confirmed') === 'cancelled') ? 'Annule' : 'En attente',
        'google_event_id' => (string) ($event['id'] ?? ''),
        'google_etag' => (string) ($event['etag'] ?? '')
    ];
}

function catalog_google_calendar_sync_bidirectional($force = false)
{
    $result = [
        'ok' => false,
        'message' => '',
        'pushed' => 0,
        'pulled' => 0,
        'errors' => []
    ];

    if (!catalog_google_calendar_enabled()) {
        $result['message'] = 'Synchronisation Google Agenda désactivée.';
        return $result;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        if (!$force) {
            $result['ok'] = true;
            $result['message'] = 'Synchronisation Google en pause: base de donnees indisponible.';
            return $result;
        }
        $result['message'] = 'Base de donnees indisponible pour la synchronisation.';
        return $result;
    }

    $state = catalog_google_calendar_get_state($connection);
    if (!$force && !empty($state['last_sync_at'])) {
        $lastSyncTs = strtotime((string) $state['last_sync_at']);
        if ($lastSyncTs !== false && (time() - $lastSyncTs) < 60) {
            $result['ok'] = true;
            $result['message'] = 'Synchronisation Google déjà récente.';
            return $result;
        }
    }

    list($tokenOk, $tokenOrError) = catalog_google_calendar_access_token();
    if (!$tokenOk) {
        catalog_google_calendar_update_state($connection, [
            'sync_token' => $state['sync_token'] ?? null,
            'last_sync_at' => date('Y-m-d H:i:s'),
            'last_error' => (string) $tokenOrError
        ]);
        $result['message'] = 'OAuth Google indisponible: ' . (string) $tokenOrError;
        return $result;
    }
    $accessToken = (string) $tokenOrError;

    $query = [
        'singleEvents' => 'true',
        'showDeleted' => 'true',
        'maxResults' => 250
    ];
    if (!empty($state['sync_token'])) {
        $query['syncToken'] = (string) $state['sync_token'];
    } else {
        $query['timeMin'] = gmdate('c', strtotime('-120 days'));
        $query['timeMax'] = gmdate('c', strtotime('+365 days'));
        $query['orderBy'] = 'updated';
    }

    $nextSyncToken = null;
    $pageToken = null;
    do {
        if ($pageToken) {
            $query['pageToken'] = $pageToken;
        } else {
            unset($query['pageToken']);
        }

        list($ok, $http, $payload, $error) = catalog_google_calendar_request($accessToken, 'GET', '/events', $query, null);
        if (!$ok) {
            if ($http === 410) {
                $query = [
                    'singleEvents' => 'true',
                    'showDeleted' => 'true',
                    'maxResults' => 250,
                    'timeMin' => gmdate('c', strtotime('-120 days')),
                    'timeMax' => gmdate('c', strtotime('+365 days')),
                    'orderBy' => 'updated'
                ];
                $state['sync_token'] = null;
                $pageToken = null;
                $nextSyncToken = null;
                continue;
            }
            catalog_google_calendar_update_state($connection, [
                'sync_token' => $state['sync_token'] ?? null,
                'last_sync_at' => date('Y-m-d H:i:s'),
                'last_error' => (string) $error
            ]);
            $result['message'] = 'Lecture Google Agenda impossible: ' . (string) $error;
            return $result;
        }

        $events = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        foreach ($events as $event) {
            $googleId = (string) ($event['id'] ?? '');
            if ($googleId === '') {
                continue;
            }

            $localId = 0;
            $findStmt = $connection->prepare('SELECT id FROM rendez_vous WHERE google_event_id = ? LIMIT 1');
            if ($findStmt) {
                $findStmt->bind_param('s', $googleId);
                $findStmt->execute();
                $findResult = $findStmt->get_result();
                $found = $findResult ? $findResult->fetch_assoc() : null;
                $localId = (int) ($found['id'] ?? 0);
                $findStmt->close();
            }

            if (((string) ($event['status'] ?? 'confirmed')) === 'cancelled') {
                if ($localId > 0) {
                    $cancelStmt = $connection->prepare('UPDATE rendez_vous SET status = "Annule", google_etag = ?, sync_source = "google", google_synced_at = NOW() WHERE id = ?');
                    if ($cancelStmt) {
                        $etag = (string) ($event['etag'] ?? '');
                        $cancelStmt->bind_param('si', $etag, $localId);
                        $cancelStmt->execute();
                        $cancelStmt->close();
                    }
                    $result['pulled']++;
                }
                continue;
            }

            $parsed = catalog_google_calendar_event_to_rdv($event);
            if ($parsed['date'] === '') {
                continue;
            }

            if ($localId > 0) {
                $updateStmt = $connection->prepare('UPDATE rendez_vous SET nom = ?, email = ?, telephone = ?, date = ?, heure = ?, service = ?, status = ?, google_etag = ?, sync_source = "google", google_synced_at = NOW() WHERE id = ?');
                if ($updateStmt) {
                    $updateStmt->bind_param(
                        'ssssssssi',
                        $parsed['nom'],
                        $parsed['email'],
                        $parsed['telephone'],
                        $parsed['date'],
                        $parsed['heure'],
                        $parsed['service'],
                        $parsed['status'],
                        $parsed['google_etag'],
                        $localId
                    );
                    $updateStmt->execute();
                    $updateStmt->close();
                    $result['pulled']++;
                }
            } else {
                $insertStmt = $connection->prepare('INSERT INTO rendez_vous (nom, email, telephone, address_line, postal_code, city, date, heure, service, status, google_event_id, google_etag, google_synced_at, sync_source) VALUES (?, ?, ?, "", "", "", ?, ?, ?, ?, ?, ?, NOW(), "google")');
                if ($insertStmt) {
                    $insertStmt->bind_param(
                        'sssssssss',
                        $parsed['nom'],
                        $parsed['email'],
                        $parsed['telephone'],
                        $parsed['date'],
                        $parsed['heure'],
                        $parsed['service'],
                        $parsed['status'],
                        $parsed['google_event_id'],
                        $parsed['google_etag']
                    );
                    $insertStmt->execute();
                    $insertStmt->close();
                    $result['pulled']++;
                }
            }
        }

        $nextSyncToken = (string) ($payload['nextSyncToken'] ?? $nextSyncToken);
        $pageToken = (string) ($payload['nextPageToken'] ?? '');
    } while ($pageToken !== '');

    $stateToken = $nextSyncToken !== null && $nextSyncToken !== '' ? $nextSyncToken : ($state['sync_token'] ?? null);

    $localChanges = $connection->query(
        "SELECT id, nom, email, telephone, date, heure, service, status, google_event_id, google_etag, updated_at, google_synced_at
         FROM rendez_vous
         WHERE (google_event_id IS NULL OR google_event_id = '' OR updated_at > COALESCE(google_synced_at, '1970-01-01 00:00:00'))"
    );

    if ($localChanges) {
        while ($row = $localChanges->fetch_assoc()) {
            $eventPayload = catalog_google_calendar_rdv_to_event($row);
            $googleEventId = trim((string) ($row['google_event_id'] ?? ''));

            if ($googleEventId === '') {
                list($ok, $http, $payload, $error) = catalog_google_calendar_request($accessToken, 'POST', '/events', [], $eventPayload);
                if ($ok) {
                    $newId = (string) ($payload['id'] ?? '');
                    $newEtag = (string) ($payload['etag'] ?? '');
                    if ($newId !== '') {
                        $syncStmt = $connection->prepare('UPDATE rendez_vous SET google_event_id = ?, google_etag = ?, google_synced_at = NOW(), sync_source = "local" WHERE id = ?');
                        if ($syncStmt) {
                            $id = (int) $row['id'];
                            $syncStmt->bind_param('ssi', $newId, $newEtag, $id);
                            $syncStmt->execute();
                            $syncStmt->close();
                        }
                    }
                    $result['pushed']++;
                } else {
                    $result['errors'][] = 'RDV #' . (int) $row['id'] . ': ' . $error;
                }
                continue;
            }

            list($ok, $http, $payload, $error) = catalog_google_calendar_request($accessToken, 'PATCH', '/events/' . rawurlencode($googleEventId), [], $eventPayload);
            if ($ok) {
                $newEtag = (string) ($payload['etag'] ?? '');
                $syncStmt = $connection->prepare('UPDATE rendez_vous SET google_etag = ?, google_synced_at = NOW(), sync_source = "local" WHERE id = ?');
                if ($syncStmt) {
                    $id = (int) $row['id'];
                    $syncStmt->bind_param('si', $newEtag, $id);
                    $syncStmt->execute();
                    $syncStmt->close();
                }
                $result['pushed']++;
            } else {
                $result['errors'][] = 'RDV #' . (int) $row['id'] . ': ' . $error;
            }
        }
        $localChanges->free();
    }

    $lastError = empty($result['errors']) ? null : implode(' | ', $result['errors']);
    catalog_google_calendar_update_state($connection, [
        'sync_token' => $stateToken,
        'last_sync_at' => date('Y-m-d H:i:s'),
        'last_error' => $lastError
    ]);

    $result['ok'] = true;
    $result['message'] = 'Synchro Google OK: ' . (int) $result['pulled'] . ' entrant(s), ' . (int) $result['pushed'] . ' sortant(s).';
    return $result;
}

function catalog_metrics_snapshot()
{
    $metrics = [
        'customers_total' => 0,
        'annonces_total' => 0,
        'vehicles_active' => 0,
        'vehicles_waiting' => 0,
        'parts_active' => 0,
        'parts_waiting' => 0,
        'in_transaction_people' => 0,
        'transactions_concluded' => 0,
        'transactions_failed' => 0,
        'traffic_daily' => 0,
        'traffic_weekly' => 0,
        'traffic_monthly' => 0
    ];

    if (!catalog_using_database()) {
        $items = catalog_all_items();
        $metrics['annonces_total'] = count($items);
        return $metrics;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return $metrics;
    }

    $countTables = [
        'customers_total' => 'SELECT COUNT(*) AS total FROM customer_profiles',
        'annonces_total' => 'SELECT COUNT(*) AS total FROM catalog_annonces',
        'transactions_concluded' => "SELECT COUNT(*) AS total FROM catalog_transaction_events WHERE outcome = 'concluded'",
        'transactions_failed' => "SELECT COUNT(*) AS total FROM catalog_transaction_events WHERE outcome = 'failed'"
    ];

    foreach ($countTables as $key => $sql) {
        $result = $connection->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        $metrics[$key] = (int) ($row['total'] ?? 0);
        if ($result) {
            $result->free();
        }
    }

    $vehicleRequests = $connection->query("SELECT request_status, COUNT(*) AS total FROM catalog_vehicle_requests GROUP BY request_status");
    if ($vehicleRequests) {
        while ($row = $vehicleRequests->fetch_assoc()) {
            if (($row['request_status'] ?? '') === 'active') {
                $metrics['vehicles_active'] = (int) ($row['total'] ?? 0);
            }
            if (($row['request_status'] ?? '') === 'queued') {
                $metrics['vehicles_waiting'] = (int) ($row['total'] ?? 0);
            }
        }
        $vehicleRequests->free();
    }

    $partRequests = $connection->query("SELECT request_status, COUNT(*) AS total FROM catalog_part_requests GROUP BY request_status");
    if ($partRequests) {
        while ($row = $partRequests->fetch_assoc()) {
            if (($row['request_status'] ?? '') === 'active') {
                $metrics['parts_active'] = (int) ($row['total'] ?? 0);
            }
            if (($row['request_status'] ?? '') === 'queued') {
                $metrics['parts_waiting'] = (int) ($row['total'] ?? 0);
            }
        }
        $partRequests->free();
    }

    $metrics['in_transaction_people'] = $metrics['vehicles_active'] + $metrics['parts_active'];

    $metrics['traffic_daily'] = catalog_sum_visit_hits_since(date('Y-m-d'));
    $metrics['traffic_weekly'] = catalog_sum_visit_hits_since(date('Y-m-d', strtotime('-6 days')));
    $metrics['traffic_monthly'] = catalog_sum_visit_hits_since(date('Y-m-01'));

    return $metrics;
}

function catalog_identity_cookie_name()
{
    return 'clinikauto_identity_email';
}

function catalog_normalize_profile($profile)
{
    $customerType = trim((string) ($profile['customer_type'] ?? 'individual'));
    if (!in_array($customerType, ['individual', 'professional'], true)) {
        $customerType = 'individual';
    }

    return [
        'customer_type' => $customerType,
        'firstname' => trim((string) ($profile['firstname'] ?? '')),
        'lastname' => trim((string) ($profile['lastname'] ?? '')),
        'address_line' => trim((string) ($profile['address_line'] ?? '')),
        'postal_code' => preg_replace('/\s+/', '', strtoupper(trim((string) ($profile['postal_code'] ?? '')))),
        'city' => trim((string) ($profile['city'] ?? '')),
        'email' => strtolower(trim((string) ($profile['email'] ?? ''))),
        'phone' => trim((string) ($profile['phone'] ?? '')),
        'registration' => strtoupper(trim((string) ($profile['registration'] ?? '')))
    ];
}

function catalog_get_customer_profile($identity)
{
    $identity = catalog_normalize_profile($identity);

    if (!catalog_using_database()) {
        return null;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return null;
    }

    $conditions = [];
    $types = '';
    $params = [];

    if ($identity['email'] !== '') {
        $conditions[] = 'LOWER(email) = ?';
        $types .= 's';
        $params[] = $identity['email'];
    }
    if ($identity['phone'] !== '') {
        $conditions[] = 'phone = ?';
        $types .= 's';
        $params[] = $identity['phone'];
    }
    if ($identity['registration'] !== '') {
        $conditions[] = 'registration = ?';
        $types .= 's';
        $params[] = $identity['registration'];
    }

    if (empty($conditions)) {
        return null;
    }

    $sql = 'SELECT customer_type, firstname, lastname, address_line, postal_code, city, email, phone, registration FROM customer_profiles WHERE ' . implode(' OR ', $conditions) . ' ORDER BY updated_at DESC LIMIT 1';
    $statement = $connection->prepare($sql);
    if (!$statement) {
        return null;
    }

    $statement->bind_param($types, ...$params);
    $statement->execute();
    $result = $statement->get_result();
    $profile = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $profile ?: null;
}

function catalog_save_customer_profile($profile, $source = 'contact')
{
    $profile = catalog_normalize_profile($profile);
    if ($profile['email'] === '' || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (!catalog_using_database()) {
        return false;
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return false;
    }

    $existing = catalog_get_customer_profile(['email' => $profile['email']]);
    if ($existing) {
        $customerType = $profile['customer_type'] !== '' ? $profile['customer_type'] : (string) ($existing['customer_type'] ?? 'individual');
        $firstname = $profile['firstname'] !== '' ? $profile['firstname'] : (string) ($existing['firstname'] ?? '');
        $lastname = $profile['lastname'] !== '' ? $profile['lastname'] : (string) ($existing['lastname'] ?? '');
        $address = $profile['address_line'] !== '' ? $profile['address_line'] : (string) ($existing['address_line'] ?? '');
        $postalCode = $profile['postal_code'] !== '' ? $profile['postal_code'] : (string) ($existing['postal_code'] ?? '');
        $city = $profile['city'] !== '' ? $profile['city'] : (string) ($existing['city'] ?? '');
        $phone = $profile['phone'] !== '' ? $profile['phone'] : (string) ($existing['phone'] ?? '');
        $registration = $profile['registration'] !== '' ? $profile['registration'] : (string) ($existing['registration'] ?? '');

        $update = $connection->prepare('UPDATE customer_profiles SET customer_type = ?, firstname = ?, lastname = ?, address_line = ?, postal_code = ?, city = ?, phone = ?, registration = ?, last_source = ? WHERE LOWER(email) = ?');
        if (!$update) {
            return false;
        }
        $update->bind_param('ssssssssss', $customerType, $firstname, $lastname, $address, $postalCode, $city, $phone, $registration, $source, $profile['email']);
        $ok = $update->execute();
        $update->close();
        return $ok;
    }

    $insert = $connection->prepare('INSERT INTO customer_profiles (customer_type, firstname, lastname, address_line, postal_code, city, email, phone, registration, last_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$insert) {
        return false;
    }
    $insert->bind_param('ssssssssss', $profile['customer_type'], $profile['firstname'], $profile['lastname'], $profile['address_line'], $profile['postal_code'], $profile['city'], $profile['email'], $profile['phone'], $profile['registration'], $source);
    $ok = $insert->execute();
    $insert->close();

    return $ok;
}

function catalog_notify_vehicle_request_received($vehicle, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Demande d\'essai bien reçue - ' . ($vehicle['title'] ?? 'Véhicule');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Votre demande d'essai pour le véhicule suivant a bien été enregistrée :\n" .
        ($vehicle['title'] ?? 'Véhicule') . "\n\n" .
        "Date souhaitée : " . (!empty($request['desired_date']) ? $request['desired_date'] : 'A confirmer') . "\n\n" .
        "La date de rendez-vous sera confirmée si le véhicule reste disponible.\n" .
        "Nous vous recontactons rapidement.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_vehicle_sold($vehicle, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Véhicule désormais indisponible - ' . ($vehicle['title'] ?? 'Véhicule');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Nous vous informons que le véhicule suivant n'est plus disponible à la vente :\n" .
        ($vehicle['title'] ?? 'Véhicule') . "\n\n" .
        "Si vous êtes intéressé(e), nous pouvons vous proposer une autre offre équivalente.\n" .
        "Sinon votre demande s'arrête ici pour cette annonce.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_vehicle_available_again($vehicle, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Véhicule de nouveau disponible - ' . ($vehicle['title'] ?? 'Véhicule');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Le véhicule suivant est de nouveau disponible :\n" .
        ($vehicle['title'] ?? 'Véhicule') . "\n\n" .
        "Vous pouvez confirmer un essai à une date ultérieure si vous le souhaitez.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_vehicle_next_candidate($vehicle, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Véhicule disponible pour votre essai - confirmation requise';
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Une demande précédente n'a pas été conclue pour le véhicule :\n" .
        ($vehicle['title'] ?? 'Véhicule') . "\n\n" .
        "Souhaitez-vous toujours réserver un essai ?\n" .
        "Répondez à cet email pour confirmer votre disponibilité.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_vehicle_request_cancelled($vehicle, $request, $reason = 'manual', $appointmentCancelled = false)
{
    if (empty($request['email'])) {
        return false;
    }

    $reasonLabel = $reason === 'expired'
        ? 'Le delai de priorite de 12 heures est depasse.'
        : 'Le dossier n\'a pas pu etre maintenu en priorite.';

    $subject = '[Clinik Auto] Dossier vehicule annule - ' . ($vehicle['title'] ?? 'Véhicule');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Votre dossier prioritaire pour le vehicule suivant est annule :\n" .
        ($vehicle['title'] ?? 'Véhicule') . "\n\n" .
        $reasonLabel . "\n" .
        ($appointmentCancelled ? "Le rendez-vous lie a cette demande a egalement ete annule.\n\n" : "\n") .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_sold($part, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Pièce indisponible - ' . ($part['title'] ?? 'Pièce');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Cette pièce n'est plus disponible :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        "Toutes les actions liées à cette annonce sont désormais clôturées.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_next_candidate($part, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Pièce disponible pour vous - acompte requis';
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Une précédente vente n'a pas été conclue pour la pièce suivante :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        "Souhaitez-vous toujours acquérir ce bien ?\n" .
        "Pour confirmer la réservation, l'acompte doit être envoyé.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_transfer_pending($part, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $deadlineLabel = trim((string) ($request['transfer_deadline_at'] ?? ''));
    $subject = '[Clinik Auto] Virement en attente de verification - ' . ($part['title'] ?? 'Pièce');
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Votre declaration de virement a bien ete prise en compte pour la piece suivante :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        "Notre equipe verifie la reception du virement. Sans validation dans un delai de " . catalog_part_transfer_deadline_hours() . " heures, la priorite pourra etre liberee pour le client suivant.\n" .
        ($deadlineLabel !== '' ? ("Echeance actuelle : " . $deadlineLabel . "\n\n") : "\n") .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_transfer_confirmed($part, $request)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Virement reçu - réservation confirmée';
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Nous confirmons la reception de votre virement pour la piece suivante :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        "Votre réservation est maintenant confirmée chez Clinik Auto.\n" .
        "Nous vous recontacterons pour la suite de la remise.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_transfer_rejected($part, $request, $appointmentCancelled = false)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Virement non retrouve - dossier en attente';
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Nous n'avons pas retrouve le virement annonce pour la piece suivante :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        ($appointmentCancelled ? "Le rendez-vous associe a cette demande a ete annule.\n\n" : '') .
        "La réservation prioritaire est libérée. Vous pouvez nous recontacter si le virement arrive avec retard.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_notify_part_transfer_expired($part, $request, $appointmentCancelled = false)
{
    if (empty($request['email'])) {
        return false;
    }

    $subject = '[Clinik Auto] Réservation annulée - délai dépassé';
    $body =
        "Bonjour " . ($request['firstname'] ?? '') . " " . ($request['lastname'] ?? '') . ",\n\n" .
        "Le delai de verification de votre virement est arrive a expiration pour la piece suivante :\n" .
        ($part['title'] ?? 'Pièce') . "\n\n" .
        ($appointmentCancelled ? "Le rendez-vous associe a cette demande a egalement ete annule.\n\n" : '') .
        "La réservation est annulée et la priorité passe au client suivant s'il existe.\n\n" .
        "Cordialement,\n" . (defined('GARAGE_NOM') ? GARAGE_NOM : 'Clinik Auto');

    return catalog_send_email($request['email'], $subject, $body);
}

function catalog_part_transfer_deadline_value()
{
    return date('Y-m-d H:i:s', time() + (catalog_part_transfer_deadline_hours() * 3600));
}

function catalog_part_process_current_request_resolution($id, $resolution)
{
    $id = (int) $id;
    $resolution = trim((string) $resolution);
    if ($id <= 0 || !in_array($resolution, ['verified', 'rejected', 'expired'], true)) {
        return [false, 'Resolution de virement invalide.'];
    }

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        $part = $connection ? catalog_db_find_item($id, 'part') : null;
        if (!$connection || !$part) {
            return [false, 'Piece introuvable.'];
        }

        $current = catalog_part_current_request($part);
        if (!$current || ($current['request_status'] ?? 'queued') !== 'active') {
            return [false, 'Aucune demande active a traiter.'];
        }

        $requestId = (int) ($current['id'] ?? 0);
        if ($requestId <= 0) {
            return [false, 'Demande active invalide.'];
        }

        if ($resolution === 'verified') {
            $updateRequest = $connection->prepare("UPDATE catalog_part_requests SET transfer_verification_status = 'verified', transfer_verified_at = NOW() WHERE id = ? AND annonce_id = ?");
            $updateItem = $connection->prepare("UPDATE catalog_annonces SET statut = 'reserved', acompte_confirme = 1, transaction_in_progress = 1 WHERE id = ? AND type = 'part'");
            if (!$updateRequest || !$updateItem) {
                return [false, 'Validation impossible pour le moment.'];
            }
            $updateRequest->bind_param('ii', $requestId, $id);
            $updateRequest->execute();
            $updateRequest->close();
            $updateItem->bind_param('i', $id);
            $updateItem->execute();
            $updateItem->close();
            catalog_notify_part_transfer_confirmed($part, $current);
            catalog_log_transaction_event('part', $id, 'transfer_verified', 'pending', 'part_reserved_after_transfer');
            return [true, 'Virement confirme. La piece est maintenant reservee.'];
        }

        $updateCurrent = $connection->prepare("UPDATE catalog_part_requests SET request_status = 'failed', transfer_verification_status = ?, transfer_verified_at = NOW() WHERE id = ? AND annonce_id = ?");
        if (!$updateCurrent) {
            return [false, 'Mise a jour de la demande impossible.'];
        }
        $failureStatus = $resolution === 'expired' ? 'expired' : 'rejected';
        $updateCurrent->bind_param('sii', $failureStatus, $requestId, $id);
        $updateCurrent->execute();
        $updateCurrent->close();

        $cancelledAppointments = catalog_cancel_linked_rdv('part_reservation', (string) $requestId, $resolution === 'expired' ? 'Délai de vérification dépassé' : 'Virement non retrouvé');

        if ($resolution === 'expired') {
            catalog_notify_part_transfer_expired($part, $current, $cancelledAppointments > 0);
        } else {
            catalog_notify_part_transfer_rejected($part, $current, $cancelledAppointments > 0);
        }

        $requests = [];
        $select = $connection->prepare('SELECT id, firstname, lastname, email, phone, message, request_status, transfer_verification_status, transfer_declared_at, transfer_deadline_at, transfer_verified_at, created_at FROM catalog_part_requests WHERE annonce_id = ? ORDER BY created_at ASC, id ASC');
        if ($select) {
            $select->bind_param('i', $id);
            $select->execute();
            $result = $select->get_result();
            while ($row = $result->fetch_assoc()) {
                $requests[] = catalog_normalize_part_request($row);
            }
            $select->close();
        }

        $next = null;
        foreach ($requests as $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $next = $request;
                break;
            }
        }

        if ($next) {
            $nextId = (int) ($next['id'] ?? 0);
            $activate = $connection->prepare("UPDATE catalog_part_requests SET request_status = 'active', transfer_verification_status = 'none', transfer_declared_at = NULL, transfer_deadline_at = NULL, transfer_verified_at = NULL WHERE id = ? AND annonce_id = ?");
            $updateItem = $connection->prepare("UPDATE catalog_annonces SET statut = 'available', acompte_confirme = 0, current_part_request_id = ?, transaction_in_progress = 1, transaction_started_at = NOW() WHERE id = ? AND type = 'part'");
            if ($activate) {
                $activate->bind_param('ii', $nextId, $id);
                $activate->execute();
                $activate->close();
            }
            if ($updateItem) {
                $nextIdStr = (string) $nextId;
                $updateItem->bind_param('si', $nextIdStr, $id);
                $updateItem->execute();
                $updateItem->close();
            }
            catalog_notify_part_next_candidate($part, $next);
            catalog_log_transaction_event('part', $id, $resolution === 'expired' ? 'transfer_expired' : 'transfer_rejected', 'failed', 'next_candidate_notified');
            return [true, $resolution === 'expired' ? 'Delai expire. Le client suivant a ete notifie.' : 'Virement rejete. Le client suivant a ete notifie.'];
        }

        $resetItem = $connection->prepare("UPDATE catalog_annonces SET statut = 'available', acompte_confirme = 0, current_part_request_id = NULL, transaction_in_progress = 0, transaction_started_at = NULL WHERE id = ? AND type = 'part'");
        if ($resetItem) {
            $resetItem->bind_param('i', $id);
            $resetItem->execute();
            $resetItem->close();
        }
        catalog_log_transaction_event('part', $id, $resolution === 'expired' ? 'transfer_expired' : 'transfer_rejected', 'failed', 'no_candidate_available');
        return [true, $resolution === 'expired' ? 'Delai expire. La piece redevient disponible.' : 'Virement rejete. La piece redevient disponible.'];
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $index => $item) {
        $item = catalog_normalize_item($item);
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'part') {
            continue;
        }

        $currentId = (string) ($item['current_part_request_id'] ?? '');
        $currentIndex = null;
        $currentRequest = null;
        foreach ($item['part_requests'] as $requestIndex => $request) {
            if ((string) ($request['id'] ?? '') === $currentId) {
                $currentIndex = $requestIndex;
                $currentRequest = catalog_normalize_part_request($request);
                break;
            }
        }
        if ($currentIndex === null || !$currentRequest) {
            return [false, 'Aucune demande active a traiter.'];
        }

        if ($resolution === 'verified') {
            $item['part_requests'][$currentIndex]['transfer_verification_status'] = 'verified';
            $item['part_requests'][$currentIndex]['transfer_verified_at'] = date('c');
            $item['status'] = 'reserved';
            $item['payment_confirmed'] = true;
            $item['transaction_in_progress'] = true;
            $item['updated_at'] = date('c');
            $store['items'][$index] = $item;
            catalog_save_store($store);
            catalog_notify_part_transfer_confirmed($item, $currentRequest);
            return [true, 'Virement confirme. La piece est maintenant reservee.'];
        }

        $item['part_requests'][$currentIndex]['request_status'] = 'failed';
        $item['part_requests'][$currentIndex]['transfer_verification_status'] = $resolution === 'expired' ? 'expired' : 'rejected';
        $item['part_requests'][$currentIndex]['transfer_verified_at'] = date('c');

        $cancelledAppointments = catalog_cancel_linked_rdv('part_reservation', (string) ($currentRequest['id'] ?? ''), $resolution === 'expired' ? 'Délai de vérification dépassé' : 'Virement non retrouvé');

        if ($resolution === 'expired') {
            catalog_notify_part_transfer_expired($item, $currentRequest, $cancelledAppointments > 0);
        } else {
            catalog_notify_part_transfer_rejected($item, $currentRequest, $cancelledAppointments > 0);
        }

        $nextIndex = null;
        foreach ($item['part_requests'] as $requestIndex => $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $nextIndex = $requestIndex;
                break;
            }
        }

        if ($nextIndex !== null) {
            $item['part_requests'][$nextIndex]['request_status'] = 'active';
            $item['part_requests'][$nextIndex]['transfer_verification_status'] = 'none';
            $item['part_requests'][$nextIndex]['transfer_declared_at'] = '';
            $item['part_requests'][$nextIndex]['transfer_deadline_at'] = '';
            $item['part_requests'][$nextIndex]['transfer_verified_at'] = '';
            $item['status'] = 'available';
            $item['payment_confirmed'] = false;
            $item['transaction_in_progress'] = true;
            $item['transaction_started_at'] = date('c');
            $item['current_part_request_id'] = (string) ($item['part_requests'][$nextIndex]['id'] ?? '');
            catalog_notify_part_next_candidate($item, $item['part_requests'][$nextIndex]);
        } else {
            $item['status'] = 'available';
            $item['payment_confirmed'] = false;
            $item['transaction_in_progress'] = false;
            $item['transaction_started_at'] = '';
            $item['current_part_request_id'] = '';
        }

        $item['updated_at'] = date('c');
        $store['items'][$index] = $item;
        catalog_save_store($store);
        return [true, $resolution === 'expired' ? 'Delai expire. La transaction a ete relancee.' : 'Virement rejete. La transaction a ete relancee.'];
    }

    return [false, 'Piece introuvable.'];
}

function catalog_process_expired_part_requests()
{
    static $running = false;
    if ($running) {
        return;
    }
    $running = true;

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        if ($connection) {
            $query = "SELECT annonce_id FROM catalog_part_requests WHERE request_status = 'active' AND transfer_verification_status = 'pending' AND transfer_deadline_at IS NOT NULL AND transfer_deadline_at <= NOW() GROUP BY annonce_id";
            $result = $connection->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    catalog_part_process_current_request_resolution((int) ($row['annonce_id'] ?? 0), 'expired');
                }
                $result->free();
            }
        }
        $running = false;
        return;
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $item) {
        $item = catalog_normalize_item($item);
        if (($item['type'] ?? '') !== 'part') {
            continue;
        }
        $currentRequest = catalog_part_current_request($item);
        $remaining = catalog_part_request_remaining_seconds($currentRequest);
        if ($remaining !== null && $remaining <= 0) {
            catalog_part_process_current_request_resolution((int) ($item['id'] ?? 0), 'expired');
        }
    }

    $running = false;
}

function catalog_register_vehicle_request($id, $request)
{
    $id = (int) $id;
    if ($id <= 0) {
        return [false, false, 'Annonce invalide', ''];
    }

    if (catalog_using_database()) {
        $vehicle = catalog_db_find_item($id, 'vehicle');
        if (!$vehicle) {
            return [false, false, 'Véhicule introuvable', ''];
        }

        $connection = catalog_db_connection();
        if (!$connection) {
            return [false, false, 'Base indisponible', ''];
        }

        $email = trim((string) ($request['email'] ?? ''));
        $desiredDate = trim((string) ($request['desired_date'] ?? ''));

        $check = $connection->prepare("SELECT id FROM catalog_vehicle_requests WHERE annonce_id = ? AND email = ? AND request_status IN ('queued','active') LIMIT 1");
        if ($check) {
            $check->bind_param('is', $id, $email);
            $check->execute();
            $result = $check->get_result();
            $existing = $result ? $result->fetch_assoc() : null;
            if ($existing) {
                $check->close();
                return [true, false, 'Demande déjà enregistrée', (string) ($existing['id'] ?? '')];
            }
            $check->close();
        }

        $insert = $connection->prepare("INSERT INTO catalog_vehicle_requests (annonce_id, firstname, lastname, email, phone, desired_date, message, request_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'queued')");
        if (!$insert) {
            return [false, false, 'Insertion demande impossible', ''];
        }

        $firstname = trim((string) ($request['firstname'] ?? ''));
        $lastname = trim((string) ($request['lastname'] ?? ''));
        $phone = trim((string) ($request['phone'] ?? ''));
        $message = trim((string) ($request['message'] ?? ''));
        $insert->bind_param('issssss', $id, $firstname, $lastname, $email, $phone, $desiredDate, $message);
        $insert->execute();
        $requestId = (int) $connection->insert_id;
        $insert->close();

        $isActiveNow = false;
        if ((string) ($vehicle['current_vehicle_request_id'] ?? '') === '') {
            $activate = $connection->prepare("UPDATE catalog_vehicle_requests SET request_status = 'active' WHERE id = ? AND annonce_id = ?");
            if ($activate) {
                $activate->bind_param('ii', $requestId, $id);
                $activate->execute();
                $activate->close();
            }

            $requestIdStr = (string) $requestId;
            $mark = $connection->prepare('UPDATE catalog_annonces SET transaction_in_progress = 1, transaction_started_at = NOW(), current_vehicle_request_id = ? WHERE id = ? AND type = "vehicle"');
            if ($mark) {
                $mark->bind_param('si', $requestIdStr, $id);
                $mark->execute();
                $mark->close();
                $isActiveNow = true;
            }
        }

        catalog_notify_vehicle_request_received($vehicle, [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'desired_date' => $desiredDate
        ]);

        return [true, $isActiveNow, $isActiveNow ? 'Demande active enregistrée' : 'Demande enregistrée dans la file', (string) $requestId];
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $index => $item) {
        $item = catalog_normalize_item($item);
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'vehicle') {
            continue;
        }

        $requestRow = [
            'id' => uniqid('req_', true),
            'firstname' => trim((string) ($request['firstname'] ?? '')),
            'lastname' => trim((string) ($request['lastname'] ?? '')),
            'email' => trim((string) ($request['email'] ?? '')),
            'phone' => trim((string) ($request['phone'] ?? '')),
            'desired_date' => trim((string) ($request['desired_date'] ?? '')),
            'message' => trim((string) ($request['message'] ?? '')),
            'created_at' => date('c')
        ];

        foreach ($item['vehicle_requests'] as $existingRequest) {
            if (
                strtolower(trim((string) ($existingRequest['email'] ?? ''))) === strtolower($requestRow['email']) &&
                in_array((string) ($existingRequest['request_status'] ?? 'queued'), ['queued', 'active'], true)
            ) {
                return [true, false, 'Demande déjà enregistrée', (string) ($existingRequest['id'] ?? '')];
            }
        }

        $requestRow['request_status'] = 'queued';
        $item['vehicle_requests'][] = $requestRow;

        $isActiveNow = false;
        if ((string) ($item['current_vehicle_request_id'] ?? '') === '') {
            $item['vehicle_requests'][count($item['vehicle_requests']) - 1]['request_status'] = 'active';
            $item['current_vehicle_request_id'] = (string) $requestRow['id'];
            $item['transaction_in_progress'] = true;
            $item['transaction_started_at'] = date('c');
            $isActiveNow = true;
        }

        $item['updated_at'] = date('c');
        $store['items'][$index] = $item;
        catalog_save_store($store);

        catalog_notify_vehicle_request_received($item, $requestRow);

        return [true, $isActiveNow, $isActiveNow ? 'Demande active enregistrée' : 'Demande enregistrée dans la file', (string) ($requestRow['id'] ?? '')];
    }

    return [false, false, 'Véhicule introuvable', ''];
}

function catalog_vehicle_mark_sold_and_delete($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }

    $vehicle = catalog_find_item($id, 'vehicle');
    if (!$vehicle) {
        return false;
    }

    $requests = [];
    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        $statement = $connection ? $connection->prepare("SELECT firstname, lastname, email, desired_date FROM catalog_vehicle_requests WHERE annonce_id = ? AND request_status IN ('queued','active')") : null;
        if ($statement) {
            $statement->bind_param('i', $id);
            $statement->execute();
            $result = $statement->get_result();
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            $statement->close();
        }
    } else {
        $requests = is_array($vehicle['vehicle_requests'] ?? null) ? $vehicle['vehicle_requests'] : [];
    }

    foreach ($requests as $request) {
        catalog_notify_vehicle_sold($vehicle, $request);
    }

    catalog_log_transaction_event('vehicle', $id, 'sale_confirmed', 'concluded', $vehicle['title'] ?? '');

    return catalog_delete_item($id);
}

function catalog_vehicle_release_transaction($id, $reason = 'manual')
{
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        if (!$connection) {
            return false;
        }

        $vehicle = catalog_db_find_item($id, 'vehicle');
        if (!$vehicle) {
            return false;
        }

        $requests = [];
        $select = $connection->prepare('SELECT id, firstname, lastname, email, desired_date, request_status, created_at FROM catalog_vehicle_requests WHERE annonce_id = ? ORDER BY created_at ASC, id ASC');
        if ($select) {
            $select->bind_param('i', $id);
            $select->execute();
            $result = $select->get_result();
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            $select->close();
        }

        $currentId = (string) ($vehicle['current_vehicle_request_id'] ?? '');
        $currentRequest = null;
        foreach ($requests as $request) {
            if ((string) ($request['id'] ?? '') === $currentId) {
                $currentRequest = $request;
                break;
            }
        }
        if ($currentId !== '') {
            $close = $connection->prepare("UPDATE catalog_vehicle_requests SET request_status = 'failed' WHERE id = ? AND annonce_id = ?");
            if ($close) {
                $currentInt = (int) $currentId;
                $close->bind_param('ii', $currentInt, $id);
                $close->execute();
                $close->close();
            }
            if (is_array($currentRequest)) {
                $cancelledAppointments = catalog_cancel_linked_rdv('vehicle_visit', $currentId, $reason === 'expired' ? 'Delai prioritaire depasse' : 'Dossier vehicule non conclu');
                catalog_notify_vehicle_request_cancelled($vehicle, $currentRequest, $reason, $cancelledAppointments > 0);
            }
        }

        $next = null;
        foreach ($requests as $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $next = $request;
                break;
            }
        }

        if ($next) {
            $nextId = (int) ($next['id'] ?? 0);
            $activate = $connection->prepare("UPDATE catalog_vehicle_requests SET request_status = 'active' WHERE id = ? AND annonce_id = ?");
            if ($activate) {
                $activate->bind_param('ii', $nextId, $id);
                $activate->execute();
                $activate->close();
            }

            $nextIdStr = (string) ($next['id'] ?? '');
            $update = $connection->prepare('UPDATE catalog_annonces SET transaction_in_progress = 1, transaction_started_at = NOW(), current_vehicle_request_id = ? WHERE id = ? AND type = "vehicle"');
            if (!$update) {
                return false;
            }
            $update->bind_param('si', $nextIdStr, $id);
            $update->execute();
            $update->close();

            catalog_notify_vehicle_next_candidate($vehicle, $next);
            catalog_log_transaction_event('vehicle', $id, $reason === 'expired' ? 'vehicle_expired' : 'sale_cancelled', 'failed', 'next_candidate_notified');
            return true;
        }

        $update = $connection->prepare('UPDATE catalog_annonces SET transaction_in_progress = 0, transaction_started_at = NULL, current_vehicle_request_id = NULL WHERE id = ? AND type = "vehicle"');
        if (!$update) {
            return false;
        }
        $update->bind_param('i', $id);
        $update->execute();
        $update->close();

        catalog_log_transaction_event('vehicle', $id, $reason === 'expired' ? 'vehicle_expired' : 'sale_cancelled', 'failed', 'no_candidate_available');

        return true;
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $index => $item) {
        $item = catalog_normalize_item($item);
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'vehicle') {
            continue;
        }

        $currentId = (string) ($item['current_vehicle_request_id'] ?? '');
        $currentRequest = null;
        if ($currentId !== '') {
            foreach ($item['vehicle_requests'] as $requestIndex => $request) {
                if ((string) ($request['id'] ?? '') === $currentId) {
                    $currentRequest = $request;
                    $item['vehicle_requests'][$requestIndex]['request_status'] = 'failed';
                    break;
                }
            }
            if (is_array($currentRequest)) {
                $cancelledAppointments = catalog_cancel_linked_rdv('vehicle_visit', $currentId, $reason === 'expired' ? 'Delai prioritaire depasse' : 'Dossier vehicule non conclu');
                catalog_notify_vehicle_request_cancelled($item, $currentRequest, $reason, $cancelledAppointments > 0);
            }
        }

        $nextIndex = null;
        foreach ($item['vehicle_requests'] as $requestIndex => $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $nextIndex = $requestIndex;
                break;
            }
        }

        if ($nextIndex !== null) {
            $item['vehicle_requests'][$nextIndex]['request_status'] = 'active';
            $item['current_vehicle_request_id'] = (string) ($item['vehicle_requests'][$nextIndex]['id'] ?? '');
            $item['transaction_in_progress'] = true;
            $item['transaction_started_at'] = date('c');
            catalog_notify_vehicle_next_candidate($item, $item['vehicle_requests'][$nextIndex]);
        } else {
            $item['transaction_in_progress'] = false;
            $item['transaction_started_at'] = '';
            $item['current_vehicle_request_id'] = '';
        }

        $item['updated_at'] = date('c');
        $store['items'][$index] = $item;
        catalog_save_store($store);

        catalog_log_transaction_event('vehicle', $id, $reason === 'expired' ? 'vehicle_expired' : 'sale_cancelled', 'failed', $nextIndex !== null ? 'next_candidate_notified' : 'no_candidate_available');

        return true;
    }

    return false;
}

function catalog_process_expired_vehicle_requests()
{
    static $running = false;
    if ($running) {
        return;
    }
    $running = true;

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        if ($connection) {
            $query = "SELECT id FROM catalog_annonces WHERE type = 'vehicle' AND transaction_in_progress = 1 AND transaction_started_at IS NOT NULL AND transaction_started_at <= (NOW() - INTERVAL 12 HOUR)";
            $result = $connection->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    catalog_vehicle_release_transaction((int) ($row['id'] ?? 0), 'expired');
                }
                $result->free();
            }
        }
        $running = false;
        return;
    }

    foreach (catalog_load_store()['items'] as $item) {
        $item = catalog_normalize_item($item);
        if (($item['type'] ?? '') !== 'vehicle' || empty($item['transaction_in_progress'])) {
            continue;
        }
        $startedAt = strtotime((string) ($item['transaction_started_at'] ?? ''));
        if ($startedAt !== false && $startedAt <= (time() - (12 * 3600))) {
            catalog_vehicle_release_transaction((int) ($item['id'] ?? 0), 'expired');
        }
    }

    $running = false;
}

function catalog_part_confirm_sale($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }

    $part = catalog_find_item($id, 'part');
    if (!$part) {
        return false;
    }

    $requests = is_array($part['part_requests'] ?? null) ? $part['part_requests'] : [];
    foreach ($requests as $request) {
        if (!in_array((string) ($request['request_status'] ?? 'queued'), ['queued', 'active'], true)) {
            continue;
        }
        catalog_notify_part_sold($part, $request);
    }

    catalog_log_transaction_event('part', $id, 'sale_confirmed', 'concluded', $part['title'] ?? '');

    return catalog_delete_item($id);
}

function catalog_part_cancel_sale($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        if (!$connection) {
            return false;
        }

        $part = catalog_db_find_item($id, 'part');
        if (!$part) {
            return false;
        }

        $requests = [];
        $select = $connection->prepare('SELECT id, firstname, lastname, email, phone, message, created_at, request_status FROM catalog_part_requests WHERE annonce_id = ? ORDER BY created_at ASC, id ASC');
        if ($select) {
            $select->bind_param('i', $id);
            $select->execute();
            $result = $select->get_result();
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
            $select->close();
        }

        $currentId = (string) ($part['current_part_request_id'] ?? '');
        if ($currentId !== '') {
            $close = $connection->prepare("UPDATE catalog_part_requests SET request_status = 'failed' WHERE id = ? AND annonce_id = ?");
            if ($close) {
                $currentInt = (int) $currentId;
                $close->bind_param('ii', $currentInt, $id);
                $close->execute();
                $close->close();
            }
        }

        $next = null;
        foreach ($requests as $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $next = $request;
                break;
            }
        }

        if ($next) {
            $nextId = (int) ($next['id'] ?? 0);
            $activate = $connection->prepare("UPDATE catalog_part_requests SET request_status = 'active' WHERE id = ? AND annonce_id = ?");
            if ($activate) {
                $activate->bind_param('ii', $nextId, $id);
                $activate->execute();
                $activate->close();
            }

            $statement = $connection->prepare("UPDATE catalog_annonces SET statut = 'reserved', acompte_confirme = 0, current_part_request_id = ? WHERE id = ? AND type = 'part'");
            if (!$statement) {
                return false;
            }
            $statement->bind_param('si', $next['id'], $id);
            $statement->execute();
            $statement->close();

            catalog_notify_part_next_candidate($part, $next);
            catalog_log_transaction_event('part', $id, 'sale_cancelled', 'failed', 'next_candidate_notified');
            return true;
        }

        $statement = $connection->prepare("UPDATE catalog_annonces SET statut = 'available', acompte_confirme = 0, current_part_request_id = NULL WHERE id = ? AND type = 'part'");
        if (!$statement) {
            return false;
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        catalog_log_transaction_event('part', $id, 'sale_cancelled', 'failed', 'no_candidate_available');
        return $updated;
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $index => $item) {
        $item = catalog_normalize_item($item);
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'part') {
            continue;
        }

        $currentId = (string) ($item['current_part_request_id'] ?? '');
        if ($currentId !== '') {
            foreach ($item['part_requests'] as $requestIndex => $request) {
                if ((string) ($request['id'] ?? '') === $currentId) {
                    $item['part_requests'][$requestIndex]['request_status'] = 'failed';
                    break;
                }
            }
        }

        $nextIndex = null;
        foreach ($item['part_requests'] as $requestIndex => $request) {
            if (($request['request_status'] ?? 'queued') === 'queued') {
                $nextIndex = $requestIndex;
                break;
            }
        }

        if ($nextIndex !== null) {
            $item['part_requests'][$nextIndex]['request_status'] = 'active';
            $item['current_part_request_id'] = (string) ($item['part_requests'][$nextIndex]['id'] ?? '');
            $item['status'] = 'reserved';
            $item['payment_confirmed'] = false;
            catalog_notify_part_next_candidate($item, $item['part_requests'][$nextIndex]);
        } else {
            $item['status'] = 'available';
            $item['payment_confirmed'] = false;
            $item['current_part_request_id'] = '';
        }

        $item['updated_at'] = date('c');
        $store['items'][$index] = $item;
        catalog_save_store($store);
        catalog_log_transaction_event('part', $id, 'sale_cancelled', 'failed', $nextIndex !== null ? 'next_candidate_notified' : 'no_candidate_available');
        return true;
    }

    return false;
}

function catalog_all_items($type = null)
{
    catalog_process_expired_vehicle_requests();
    catalog_process_expired_part_requests();

    if (catalog_using_database()) {
        $items = catalog_db_all_items($type);
        catalog_sync_store_from_database();
        return $items;
    }

    $items = catalog_load_store()['items'];

    if ($type !== null) {
        $items = array_values(array_filter($items, function ($item) use ($type) {
            return ($item['type'] ?? '') === $type;
        }));
    }

    $items = array_map('catalog_normalize_item', $items);

    usort($items, function ($left, $right) {
        return ($right['id'] ?? 0) <=> ($left['id'] ?? 0);
    });

    return $items;
}

function catalog_find_item($id, $type = null)
{
    catalog_process_expired_vehicle_requests();
    catalog_process_expired_part_requests();

    if (catalog_using_database()) {
        $item = catalog_db_find_item($id, $type);
        catalog_sync_store_from_database();
        return $item;
    }

    foreach (catalog_load_store()['items'] as $item) {
        if ((int) ($item['id'] ?? 0) !== (int) $id) {
            continue;
        }

        if ($type !== null && ($item['type'] ?? '') !== $type) {
            return null;
        }

        return catalog_normalize_item($item);
    }

    return null;
}

function catalog_normalize_files($files)
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $normalized[] = [
            'name' => $name,
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
            'type' => $files['type'][$index] ?? ''
        ];
    }

    return $normalized;
}

function catalog_prepare_uploaded_images($files, &$errors)
{
    $prepared = [];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
    $normalized = catalog_normalize_files($files);

    if (empty($normalized)) {
        return $prepared;
    }

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'depasse la taille maximale autorisee par le serveur',
        UPLOAD_ERR_FORM_SIZE => 'depasse la taille maximale autorisee par le formulaire',
        UPLOAD_ERR_PARTIAL => 'a ete telechargee partiellement',
        UPLOAD_ERR_NO_TMP_DIR => 'n\'a pas de dossier temporaire disponible sur le serveur',
        UPLOAD_ERR_CANT_WRITE => 'n\'a pas pu etre ecrite sur le disque',
        UPLOAD_ERR_EXTENSION => 'a ete bloquee par une extension PHP'
    ];

    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    $imageCount = 0;
    $maxImages = 8;
    
    foreach ($normalized as $file) {
        $fileName = basename((string) ($file['name'] ?? 'image'));
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $reason = $uploadErrors[$errorCode] ?? 'n\'a pas pu etre chargee correctement';
            catalog_append_runtime_error('Image ignoree (' . $fileName . ') : ' . $reason . '.');
            continue;
        }

        if (($file['size'] ?? 0) > 6 * 1024 * 1024) {
            catalog_append_runtime_error('Image ignoree (' . $fileName . ') : taille superieure a 6 Mo.');
            continue;
        }

        $mime = $file['type'] ?? '';
        if ($finfo && is_string($file['tmp_name']) && $file['tmp_name'] !== '') {
            $detected = finfo_file($finfo, $file['tmp_name']);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        if (!in_array($mime, $allowed_mimes, true)) {
            catalog_append_runtime_error('Image ignoree (' . $fileName . ') : format non supporte. Utilisez JPG, PNG, WEBP, SVG ou GIF.');
            continue;
        }

        $content = @file_get_contents($file['tmp_name']);
        if ($content === false) {
            catalog_append_runtime_error('Image ignoree (' . $fileName . ') : lecture impossible apres telechargement.');
            continue;
        }

        // Limiter à 8 images max
        if ($imageCount >= $maxImages) {
            catalog_append_runtime_error('Image ignoree (' . $fileName . ') : maximum ' . $maxImages . ' images autorisees par annonce.');
            continue;
        }

        // Redimensionner et compresser (SVG exclu)
        if ($mime !== 'image/svg+xml') {
            $sizeBefore = strlen($content);
            $content = catalog_resize_and_compress_image($content, $mime);
            $sizeAfter = strlen($content);
            if ($sizeAfter < $sizeBefore) {
                catalog_append_runtime_error('Image optimisee (' . $fileName . ') : ' . round(100 - ($sizeAfter / $sizeBefore * 100)) . '% de reduction.');
            }
        }

        $prepared[] = [
            'id' => uniqid('img_', true),
            'name' => $fileName,
            'mime' => $mime,
            'blob' => $content,
            'data' => 'data:' . $mime . ';base64,' . base64_encode($content)
        ];
        $imageCount++;
    }

    if ($finfo) {
        finfo_close($finfo);
    }

    return $prepared;
}

function catalog_validate_payload($payload)
{
    $item = catalog_empty_item($payload['type'] ?? 'vehicle');
    $item['id'] = (int) ($payload['id'] ?? 0);
    $item['type'] = ($payload['type'] ?? '') === 'part' ? 'part' : 'vehicle';
    $item['title'] = trim((string) ($payload['title'] ?? ''));
    $item['subtitle'] = trim((string) ($payload['subtitle'] ?? ''));
    $item['price'] = str_replace(',', '.', trim((string) ($payload['price'] ?? '')));
    $item['short_description'] = trim((string) ($payload['short_description'] ?? ''));
    $item['description'] = trim((string) ($payload['description'] ?? ''));
    $item['specs'] = trim((string) ($payload['specs'] ?? ''));
    $item['status'] = in_array(($payload['status'] ?? ''), ['available', 'reserved'], true)
        ? $payload['status']
        : 'available';
    $item['payment_confirmed'] = isset($payload['payment_confirmed']) && (string) $payload['payment_confirmed'] === '1';

    $errors = [];
    if ($item['title'] === '') {
        $errors[] = 'Le titre de l\'annonce est obligatoire.';
    }
    if ($item['subtitle'] === '') {
        $errors[] = 'Le sous-titre de l\'annonce est obligatoire.';
    }
    if ($item['short_description'] === '') {
        $errors[] = 'Le resume court est obligatoire.';
    }
    if ($item['description'] === '') {
        $errors[] = 'La description detaillee est obligatoire.';
    }
    if ($item['specs'] === '') {
        $errors[] = 'Le bloc de renseignements est obligatoire.';
    }
    if ($item['price'] === '' || !is_numeric($item['price'])) {
        $errors[] = 'Le prix doit être renseigné en numérique.';
    }

    $item['price'] = is_numeric($item['price']) ? round((float) $item['price'], 2) : 0.0;

    if ($item['type'] === 'part' && $item['payment_confirmed']) {
        $item['status'] = 'reserved';
    }

    return [$item, $errors];
}

function catalog_upsert_item($payload, $files, $remove_image_ids = [])
{
    catalog_set_runtime_error('');
    list($item, $errors) = catalog_validate_payload($payload);

    if (catalog_using_database()) {
        return catalog_db_upsert_item($item, $files, $remove_image_ids, $errors);
    }

    $store = catalog_load_store();
    $existing_index = null;
    $existing_item = null;

    foreach ($store['items'] as $index => $candidate) {
        if ((int) ($candidate['id'] ?? 0) === $item['id'] && $item['id'] > 0) {
            $existing_index = $index;
            $existing_item = $candidate;
            break;
        }
    }

    $item['images'] = $existing_item['images'] ?? [];
    $item['transaction_in_progress'] = !empty($existing_item['transaction_in_progress']);
    $item['transaction_started_at'] = (string) ($existing_item['transaction_started_at'] ?? '');
    $item['current_vehicle_request_id'] = (string) ($existing_item['current_vehicle_request_id'] ?? '');
    $item['vehicle_requests'] = is_array($existing_item['vehicle_requests'] ?? null) ? $existing_item['vehicle_requests'] : [];
    $item['part_requests'] = is_array($existing_item['part_requests'] ?? null) ? $existing_item['part_requests'] : [];
    $item['current_part_request_id'] = (string) ($existing_item['current_part_request_id'] ?? '');
    if (!empty($remove_image_ids)) {
        $item['images'] = array_values(array_filter($item['images'], function ($image) use ($remove_image_ids) {
            return !in_array((string) ($image['id'] ?? ''), $remove_image_ids, true);
        }));
    }

    $new_images = catalog_prepare_uploaded_images($files, $errors);
    $item['images'] = array_merge($item['images'], $new_images);
    if (count($item['images']) > 8) {
        $errors[] = 'Chaque annonce accepte 8 photos maximum.';
    }

    if (!empty($errors)) {
        if ($existing_item) {
            $item['created_at'] = $existing_item['created_at'] ?? '';
        }
        return [false, $errors, $item];
    }

    $item['images'] = array_slice($item['images'], 0, 8);
    $item['updated_at'] = date('c');

    if ($existing_index !== null) {
        $item['created_at'] = $existing_item['created_at'] ?? $item['updated_at'];
        $store['items'][$existing_index] = $item;
    } else {
        $store['last_id'] = (int) ($store['last_id'] ?? 0) + 1;
        $item['id'] = $store['last_id'];
        $item['created_at'] = $item['updated_at'];
        $store['items'][] = $item;
    }

    catalog_save_store($store);

    return [true, [], $item];
}

function catalog_delete_item($id)
{
    if (catalog_using_database()) {
        $ok = catalog_db_delete_item($id);
        if ($ok) {
            // Synchronisation JSON : supprimer aussi l'entrée du fichier JSON pour éviter
            // qu'un fallback ultérieur affiche une annonce supprimée.
            $store = catalog_load_store();
            $before = count($store['items']);
            $store['items'] = array_values(array_filter($store['items'], function ($item) use ($id) {
                return (int) ($item['id'] ?? 0) !== (int) $id;
            }));
            if (count($store['items']) < $before) {
                catalog_save_store($store);
            }
        }
        return $ok;
    }

    $store = catalog_load_store();
    $before = count($store['items']);

    $store['items'] = array_values(array_filter($store['items'], function ($item) use ($id) {
        return (int) ($item['id'] ?? 0) !== (int) $id;
    }));

    if (count($store['items']) === $before) {
        return false;
    }

    catalog_save_store($store);
    return true;
}

function catalog_format_price($price)
{
    return number_format((float) $price, 0, ',', ' ');
}

function catalog_reservation_amount($price)
{
    return round(((float) $price) * 0.30, 2);
}

function catalog_primary_image($item)
{
    if (!empty($item['images'][0]['data'])) {
        return $item['images'][0]['data'];
    }

    return catalog_svg_placeholder($item['title'] ?? 'Clinik Auto', '#273244');
}

function catalog_status_label($item)
{
    if (($item['type'] ?? '') === 'vehicle' && !empty($item['transaction_in_progress'])) {
        return 'Transaction en cours';
    }

    if (($item['type'] ?? '') === 'part' && !empty($item['transaction_in_progress']) && empty($item['payment_confirmed'])) {
        $currentRequest = catalog_part_current_request($item);
        if (catalog_part_request_is_pending_transfer($currentRequest)) {
            return 'Virement en verification';
        }
        return 'Client prioritaire';
    }

    if (($item['status'] ?? '') === 'reserved') {
        return ($item['type'] ?? '') === 'part'
            ? (!empty($item['payment_confirmed']) ? 'Acompte confirme - indisponible' : 'Reserve - en attente d\'acompte')
            : 'Reserve';
    }

    return 'Disponible';
}

function catalog_build_contact_link($item)
{
    $title = trim((string) ($item['title'] ?? 'Annonce Clinik Auto'));
    $price = catalog_format_price($item['price'] ?? 0);
    $base_query = [
        'contact_action' => ($item['type'] ?? '') === 'part' ? 'part_reservation' : 'vehicle_visit',
        'annonce_id' => (string) ((int) ($item['id'] ?? 0)),
        'annonce_type' => ($item['type'] ?? ''),
        'annonce_title' => $title,
        'annonce_price' => (string) ($item['price'] ?? 0)
    ];

    if (($item['type'] ?? '') === 'part') {
        $deposit = number_format(catalog_reservation_amount($item['price'] ?? 0), 2, ',', ' ');
        $query = $base_query + [
            'sujet' => 'Réservation pièce d\'occasion - ' . $title,
            'message' => "Bonjour, je souhaite réserver la pièce suivante : " . $title . " (" . $price . " EUR). Je suis informé qu'un acompte de 30 % soit " . $deposit . " EUR est demandé par virement instantané. Merci de me recontacter pour finaliser la réservation.",
            'acompte_montant' => (string) catalog_reservation_amount($item['price'] ?? 0)
        ];

        return '../contact/contact.php?' . http_build_query($query);
    }

    $query = $base_query + [
        'sujet' => 'Réservation visite véhicule - ' . $title,
        'message' => "Bonjour, je souhaite réserver une visite pour le véhicule suivant : " . $title . " (" . $price . " EUR). Merci de me proposer un rendez-vous."
    ];

    return '../contact/contact.php?' . http_build_query($query);
}

function catalog_type_label($type)
{
    return $type === 'part' ? 'Pièce d\'occasion' : 'Véhicule d\'occasion';
}

function catalog_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function catalog_mark_part_reserved($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return false;
    }

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        if (!$connection) {
            return false;
        }

        $statement = $connection->prepare("UPDATE catalog_annonces SET statut = 'reserved', acompte_confirme = 1 WHERE id = ? AND type = 'part'");
        if (!$statement) {
            return false;
        }

        $statement->bind_param('i', $id);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    $store = catalog_load_store();
    $updated = false;
    foreach ($store['items'] as $index => $item) {
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'part') {
            continue;
        }

        $store['items'][$index]['status'] = 'reserved';
        $store['items'][$index]['payment_confirmed'] = true;
        $store['items'][$index]['updated_at'] = date('c');
        $updated = true;
        break;
    }

    if ($updated) {
        catalog_save_store($store);
    }

    return $updated;
}

function catalog_db_all_items($type = null)
{
    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $sql = 'SELECT * FROM catalog_annonces';
    $types = '';
    $params = [];

    if ($type !== null) {
        $sql .= ' WHERE type = ?';
        $types = 's';
        $params[] = $type;
    }

    $sql .= ' ORDER BY id DESC';
    $statement = $connection->prepare($sql);
    if (!$statement) {
        return [];
    }

    if (!empty($params)) {
        $statement->bind_param($types, ...$params);
    }

    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    return catalog_db_attach_images($rows);
}

function catalog_sync_store_from_database()
{
    static $syncInProgress = false;

    if ($syncInProgress || !catalog_using_database()) {
        return;
    }

    $syncInProgress = true;

    $items = catalog_db_all_items();
    $store = [
        'last_id' => 0,
        'items' => $items,
    ];

    foreach ($items as $item) {
        $store['last_id'] = max($store['last_id'], (int) ($item['id'] ?? 0));
    }

    catalog_save_store($store);

    $syncInProgress = false;
}

function catalog_db_find_item($id, $type = null)
{
    $connection = catalog_db_connection();
    if (!$connection) {
        return null;
    }

    $sql = 'SELECT * FROM catalog_annonces WHERE id = ?';
    $types = 'i';
    $params = [(int) $id];

    if ($type !== null) {
        $sql .= ' AND type = ?';
        $types .= 's';
        $params[] = $type;
    }

    $statement = $connection->prepare($sql);
    if (!$statement) {
        return null;
    }

    $statement->bind_param($types, ...$params);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    if (!$row) {
        return null;
    }

    $items = catalog_db_attach_images([$row]);
    return $items[0] ?? null;
}

function catalog_db_attach_images($rows)
{
    if (empty($rows)) {
        return [];
    }

    $connection = catalog_db_connection();
    if (!$connection) {
        return [];
    }

    $ids = array_map(function ($row) {
        return (int) $row['id'];
    }, $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $imagesByItem = [];

    $statement = $connection->prepare(
        'SELECT id, annonce_id, nom_fichier, mime_type, image_blob, ordre_affichage FROM catalog_annonce_images WHERE annonce_id IN (' . $placeholders . ') ORDER BY annonce_id ASC, ordre_affichage ASC, id ASC'
    );

    if ($statement) {
        $statement->bind_param($types, ...$ids);
        $statement->execute();
        $result = $statement->get_result();
        while ($image = $result->fetch_assoc()) {
            $annonceId = (int) $image['annonce_id'];
            if (!isset($imagesByItem[$annonceId])) {
                $imagesByItem[$annonceId] = [];
            }
            $imagesByItem[$annonceId][] = [
                'id' => (string) $image['id'],
                'name' => $image['nom_fichier'],
                'mime' => $image['mime_type'],
                'data' => 'data:' . $image['mime_type'] . ';base64,' . base64_encode($image['image_blob'])
            ];
        }
        $statement->close();
    }

    $vehicleRequestsByItem = [];
    $vehicleStatement = $connection->prepare(
        'SELECT id, annonce_id, firstname, lastname, email, phone, desired_date, message, request_status, created_at FROM catalog_vehicle_requests WHERE annonce_id IN (' . $placeholders . ') ORDER BY annonce_id ASC, created_at ASC, id ASC'
    );
    if ($vehicleStatement) {
        $vehicleStatement->bind_param($types, ...$ids);
        $vehicleStatement->execute();
        $result = $vehicleStatement->get_result();
        while ($request = $result->fetch_assoc()) {
            $annonceId = (int) $request['annonce_id'];
            if (!isset($vehicleRequestsByItem[$annonceId])) {
                $vehicleRequestsByItem[$annonceId] = [];
            }
            $vehicleRequestsByItem[$annonceId][] = [
                'id' => (string) $request['id'],
                'firstname' => (string) $request['firstname'],
                'lastname' => (string) $request['lastname'],
                'email' => (string) $request['email'],
                'phone' => (string) $request['phone'],
                'desired_date' => (string) ($request['desired_date'] ?? ''),
                'message' => (string) ($request['message'] ?? ''),
                'request_status' => (string) ($request['request_status'] ?? 'queued'),
                'created_at' => (string) ($request['created_at'] ?? '')
            ];
        }
        $vehicleStatement->close();
    }

    $partRequestsByItem = [];
    $partStatement = $connection->prepare(
        'SELECT id, annonce_id, firstname, lastname, email, phone, message, request_status, transfer_verification_status, transfer_declared_at, transfer_deadline_at, transfer_verified_at, created_at FROM catalog_part_requests WHERE annonce_id IN (' . $placeholders . ') ORDER BY annonce_id ASC, created_at ASC, id ASC'
    );
    if ($partStatement) {
        $partStatement->bind_param($types, ...$ids);
        $partStatement->execute();
        $result = $partStatement->get_result();
        while ($request = $result->fetch_assoc()) {
            $annonceId = (int) $request['annonce_id'];
            if (!isset($partRequestsByItem[$annonceId])) {
                $partRequestsByItem[$annonceId] = [];
            }
            $partRequestsByItem[$annonceId][] = [
                'id' => (string) $request['id'],
                'firstname' => (string) $request['firstname'],
                'lastname' => (string) $request['lastname'],
                'email' => (string) $request['email'],
                'phone' => (string) $request['phone'],
                'message' => (string) ($request['message'] ?? ''),
                'request_status' => (string) ($request['request_status'] ?? 'queued'),
                'transfer_verification_status' => (string) ($request['transfer_verification_status'] ?? 'none'),
                'transfer_declared_at' => (string) ($request['transfer_declared_at'] ?? ''),
                'transfer_deadline_at' => (string) ($request['transfer_deadline_at'] ?? ''),
                'transfer_verified_at' => (string) ($request['transfer_verified_at'] ?? ''),
                'created_at' => (string) ($request['created_at'] ?? '')
            ];
        }
        $partStatement->close();
    }

    return array_map('catalog_normalize_item', array_map(function ($row) use ($imagesByItem, $vehicleRequestsByItem, $partRequestsByItem) {
        return [
            'id' => (int) $row['id'],
            'type' => $row['type'],
            'title' => $row['titre'],
            'subtitle' => $row['sous_titre'],
            'price' => (float) $row['prix'],
            'short_description' => $row['resume_court'],
            'description' => $row['description_longue'],
            'specs' => $row['renseignements'],
            'status' => $row['statut'],
            'payment_confirmed' => (bool) $row['acompte_confirme'],
            'transaction_in_progress' => !empty($row['transaction_in_progress']),
            'transaction_started_at' => $row['transaction_started_at'] ?? '',
            'current_vehicle_request_id' => (string) ($row['current_vehicle_request_id'] ?? ''),
            'vehicle_requests' => $vehicleRequestsByItem[(int) $row['id']] ?? [],
            'part_requests' => $partRequestsByItem[(int) $row['id']] ?? [],
            'current_part_request_id' => (string) ($row['current_part_request_id'] ?? ''),
            'images' => $imagesByItem[(int) $row['id']] ?? [],
            'created_at' => $row['date_creation'],
            'updated_at' => $row['date_mise_a_jour']
        ];
    }, $rows));
}

function catalog_register_part_request($id, $request, $acompteConfirme)
{
    $id = (int) $id;
    if ($id <= 0) {
        return [false, false, 'Annonce invalide', ''];
    }

    if (catalog_using_database()) {
        $connection = catalog_db_connection();
        $part = $connection ? catalog_db_find_item($id, 'part') : null;
        if (!$connection || !$part) {
            return [false, false, 'Pièce introuvable', ''];
        }

        $email = trim((string) ($request['email'] ?? ''));
        $check = $connection->prepare("SELECT id, request_status, transfer_verification_status FROM catalog_part_requests WHERE annonce_id = ? AND email = ? AND request_status IN ('queued','active') ORDER BY created_at ASC, id ASC LIMIT 1");
        if ($check) {
            $check->bind_param('is', $id, $email);
            $check->execute();
            $result = $check->get_result();
            $existingRequest = $result ? $result->fetch_assoc() : null;
            if ($existingRequest) {
                $check->close();
                $existingId = (int) ($existingRequest['id'] ?? 0);
                $existingStatus = (string) ($existingRequest['request_status'] ?? 'queued');
                $existingTransferStatus = (string) ($existingRequest['transfer_verification_status'] ?? 'none');
                $partCurrentId = (string) ($part['current_part_request_id'] ?? '');
                if ($acompteConfirme && $existingId > 0 && (string) $existingId === $partCurrentId && $existingStatus === 'active' && in_array($existingTransferStatus, ['none', 'rejected', 'expired'], true)) {
                    $deadline = catalog_part_transfer_deadline_value();
                    $updateExisting = $connection->prepare("UPDATE catalog_part_requests SET transfer_verification_status = 'pending', transfer_declared_at = NOW(), transfer_deadline_at = ?, transfer_verified_at = NULL WHERE id = ? AND annonce_id = ?");
                    if ($updateExisting) {
                        $updateExisting->bind_param('sii', $deadline, $existingId, $id);
                        $updateExisting->execute();
                        $updateExisting->close();
                    }
                    $markItem = $connection->prepare("UPDATE catalog_annonces SET statut = 'available', acompte_confirme = 0, current_part_request_id = ?, transaction_in_progress = 1, transaction_started_at = NOW() WHERE id = ? AND type = 'part'");
                    if ($markItem) {
                        $existingIdStr = (string) $existingId;
                        $markItem->bind_param('si', $existingIdStr, $id);
                        $markItem->execute();
                        $markItem->close();
                    }
                    $request['transfer_deadline_at'] = $deadline;
                    catalog_notify_part_transfer_pending($part, array_merge($request, ['transfer_deadline_at' => $deadline]));
                    return [true, false, 'Virement déclare, en attente de verification'];
                }
                return [true, false, 'Demande déjà enregistrée', (string) $existingId];
            }
            $check->close();
        }

        $requestStatus = ($acompteConfirme && (string) ($part['current_part_request_id'] ?? '') === '') ? 'active' : 'queued';
        $transferStatus = ($acompteConfirme && $requestStatus === 'active') ? 'pending' : 'none';
        $transferDeclaredAt = ($acompteConfirme && $requestStatus === 'active') ? date('Y-m-d H:i:s') : null;
        $transferDeadlineAt = ($acompteConfirme && $requestStatus === 'active') ? catalog_part_transfer_deadline_value() : null;
        $insert = $connection->prepare("INSERT INTO catalog_part_requests (annonce_id, firstname, lastname, email, phone, message, request_status, transfer_verification_status, transfer_declared_at, transfer_deadline_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$insert) {
            return [false, false, 'Insertion demande impossible', ''];
        }
        $firstname = trim((string) ($request['firstname'] ?? ''));
        $lastname = trim((string) ($request['lastname'] ?? ''));
        $phone = trim((string) ($request['phone'] ?? ''));
        $message = trim((string) ($request['message'] ?? ''));
        $insert->bind_param('isssssssss', $id, $firstname, $lastname, $email, $phone, $message, $requestStatus, $transferStatus, $transferDeclaredAt, $transferDeadlineAt);
        $insert->execute();
        $requestId = (int) $connection->insert_id;
        $insert->close();

        $reservedNow = false;
        if ($requestStatus === 'active') {
            $update = $connection->prepare("UPDATE catalog_annonces SET statut = 'available', acompte_confirme = 0, current_part_request_id = ?, transaction_in_progress = 1, transaction_started_at = NOW() WHERE id = ? AND type = 'part'");
            if ($update) {
                $requestIdStr = (string) $requestId;
                $update->bind_param('si', $requestIdStr, $id);
                $update->execute();
                $update->close();
            }
            if ($acompteConfirme) {
                catalog_notify_part_transfer_pending($part, [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'transfer_deadline_at' => $transferDeadlineAt
                ]);
            }
        }

        return [true, $reservedNow, $acompteConfirme && $requestStatus === 'active' ? 'Virement déclare, en attente de verification' : 'Demande enregistrée', (string) $requestId];
    }

    $store = catalog_load_store();
    foreach ($store['items'] as $index => $item) {
        $item = catalog_normalize_item($item);
        if ((int) ($item['id'] ?? 0) !== $id || ($item['type'] ?? '') !== 'part') {
            continue;
        }

        $email = strtolower(trim((string) ($request['email'] ?? '')));
        foreach ($item['part_requests'] as $existingRequest) {
            if (
                strtolower(trim((string) ($existingRequest['email'] ?? ''))) === $email &&
                in_array(($existingRequest['request_status'] ?? 'queued'), ['queued', 'active'], true)
            ) {
                return [true, false, 'Demande déjà enregistrée', (string) ($existingRequest['id'] ?? '')];
            }
        }

        $requestRow = [
            'id' => uniqid('part_req_', true),
            'firstname' => trim((string) ($request['firstname'] ?? '')),
            'lastname' => trim((string) ($request['lastname'] ?? '')),
            'email' => trim((string) ($request['email'] ?? '')),
            'phone' => trim((string) ($request['phone'] ?? '')),
            'message' => trim((string) ($request['message'] ?? '')),
            'request_status' => ($acompteConfirme && ($item['current_part_request_id'] ?? '') === '') ? 'active' : 'queued',
            'transfer_verification_status' => ($acompteConfirme && ($item['current_part_request_id'] ?? '') === '') ? 'pending' : 'none',
            'transfer_declared_at' => ($acompteConfirme && ($item['current_part_request_id'] ?? '') === '') ? date('c') : '',
            'transfer_deadline_at' => ($acompteConfirme && ($item['current_part_request_id'] ?? '') === '') ? date('c', time() + (catalog_part_transfer_deadline_hours() * 3600)) : '',
            'transfer_verified_at' => '',
            'created_at' => date('c')
        ];

        $item['part_requests'][] = $requestRow;
        $reservedNow = false;

        if (($requestRow['request_status'] ?? 'queued') === 'active') {
            $item['status'] = 'available';
            $item['payment_confirmed'] = false;
            $item['current_part_request_id'] = (string) $requestRow['id'];
            $item['transaction_in_progress'] = true;
            $item['transaction_started_at'] = date('c');
        }

        if ($acompteConfirme && ($requestRow['request_status'] ?? 'queued') === 'active') {
            catalog_notify_part_transfer_pending($item, $requestRow);
        }

        $item['updated_at'] = date('c');
        $store['items'][$index] = $item;
        catalog_save_store($store);

        return [true, $reservedNow, 'Demande enregistrée', (string) ($requestRow['id'] ?? '')];
    }

    return [false, false, 'Pièce introuvable', ''];
}

function catalog_db_upsert_item($item, $files, $remove_image_ids, $errors)
{
    $connection = catalog_db_connection();
    if (!$connection) {
        return [false, ['Base de donnees indisponible pour enregistrer cette annonce.'], $item];
    }

    $existingItem = $item['id'] > 0 ? catalog_db_find_item($item['id']) : null;
    $remainingImages = $existingItem['images'] ?? [];
    if (!empty($remove_image_ids)) {
        $remainingImages = array_values(array_filter($remainingImages, function ($image) use ($remove_image_ids) {
            return !in_array((string) $image['id'], $remove_image_ids, true);
        }));
    }

    $newImages = catalog_prepare_uploaded_images($files, $errors);
    if (count($remainingImages) + count($newImages) > 8) {
        $errors[] = 'Chaque annonce accepte 8 photos maximum.';
    }

    if (!empty($errors)) {
        $item['images'] = $remainingImages;
        if ($existingItem) {
            $item['created_at'] = $existingItem['created_at'];
            $item['updated_at'] = $existingItem['updated_at'];
        }
        return [false, $errors, $item];
    }

    $confirmed = $item['payment_confirmed'] ? 1 : 0;
    if ($item['id'] > 0 && $existingItem) {
        $statement = $connection->prepare('UPDATE catalog_annonces SET type = ?, titre = ?, sous_titre = ?, prix = ?, resume_court = ?, description_longue = ?, renseignements = ?, statut = ?, acompte_confirme = ? WHERE id = ?');
        if (!$statement) {
            return [false, ['Impossible de mettre a jour l\'annonce.'], $item];
        }
        $statement->bind_param('sssdssssii', $item['type'], $item['title'], $item['subtitle'], $item['price'], $item['short_description'], $item['description'], $item['specs'], $item['status'], $confirmed, $item['id']);
        if (!$statement->execute()) {
            $dbError = trim((string) $statement->error);
            $statement->close();
            return [false, ['Echec mise a jour annonce: ' . ($dbError !== '' ? $dbError : 'erreur SQL.')], $item];
        }
        $statement->close();
        $annonceId = $item['id'];
    } else {
        $statement = $connection->prepare('INSERT INTO catalog_annonces (type, titre, sous_titre, prix, resume_court, description_longue, renseignements, statut, acompte_confirme) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$statement) {
            return [false, ['Impossible de creer l\'annonce.'], $item];
        }
        $statement->bind_param('sssdssssi', $item['type'], $item['title'], $item['subtitle'], $item['price'], $item['short_description'], $item['description'], $item['specs'], $item['status'], $confirmed);
        if (!$statement->execute()) {
            $dbError = trim((string) $statement->error);
            $statement->close();
            return [false, ['Echec creation annonce: ' . ($dbError !== '' ? $dbError : 'erreur SQL.')], $item];
        }
        $annonceId = (int) $connection->insert_id;
        $statement->close();
        $item['id'] = $annonceId;
    }

    if (!empty($remove_image_ids)) {
        $placeholders = implode(',', array_fill(0, count($remove_image_ids), '?'));
        $types = 'i' . str_repeat('i', count($remove_image_ids));
        $ids = array_map('intval', $remove_image_ids);
        $statement = $connection->prepare('DELETE FROM catalog_annonce_images WHERE annonce_id = ? AND id IN (' . $placeholders . ')');
        if ($statement) {
            $statement->bind_param($types, $annonceId, ...$ids);
            if (!$statement->execute()) {
                $statement->close();
                return [false, ['Impossible de supprimer les images sélectionnées.'], $item];
            }
            $statement->close();
        }
    }

    if (!empty($newImages)) {
        $order = count($remainingImages);
        $statement = $connection->prepare('INSERT INTO catalog_annonce_images (annonce_id, nom_fichier, mime_type, image_blob, ordre_affichage) VALUES (?, ?, ?, ?, ?)');
        if ($statement) {
            foreach ($newImages as $image) {
                $order++;
                $blob = $image['blob'];
                $statement->bind_param('isssi', $annonceId, $image['name'], $image['mime'], $blob, $order);
                if (!$statement->execute()) {
                    $statement->close();
                    return [false, ['Une image n\'a pas pu être enregistrée en base.'], $item];
                }
            }
            $statement->close();
        }
    }

    $savedItem = catalog_db_find_item($annonceId);
    return [true, [], $savedItem ?: $item];
}

function catalog_db_delete_item($id)
{
    $connection = catalog_db_connection();
    if (!$connection) {
        catalog_set_runtime_error('Connexion base de donnees indisponible.');
        return false;
    }

    $id = (int) $id;
    if ($id <= 0) {
        catalog_set_runtime_error('Identifiant annonce invalide.');
        return false;
    }

    if (!$connection->begin_transaction()) {
        catalog_set_runtime_error('Impossible de demarrer la transaction SQL: ' . $connection->error);
        return false;
    }

    $childTables = [
        'catalog_annonce_images',
        'catalog_vehicle_requests',
        'catalog_part_requests'
    ];

    foreach ($childTables as $table) {
        $statement = $connection->prepare('DELETE FROM ' . $table . ' WHERE annonce_id = ?');
        if (!$statement) {
            catalog_set_runtime_error('Preparation suppression table ' . $table . ' impossible: ' . $connection->error);
            $connection->rollback();
            return false;
        }

        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            catalog_set_runtime_error('Suppression dependances impossible (' . $table . '): ' . $statement->error);
            $statement->close();
            $connection->rollback();
            return false;
        }
        $statement->close();
    }

    $statement = $connection->prepare('DELETE FROM catalog_annonces WHERE id = ?');
    if (!$statement) {
        catalog_set_runtime_error('Preparation suppression annonce impossible: ' . $connection->error);
        $connection->rollback();
        return false;
    }

    $statement->bind_param('i', $id);
    if (!$statement->execute()) {
        catalog_set_runtime_error('Suppression annonce impossible: ' . $statement->error);
        $statement->close();
        $connection->rollback();
        return false;
    }
    $deleted = $statement->affected_rows > 0;
    $statement->close();

    if (!$deleted) {
        catalog_set_runtime_error('Aucune annonce supprimee (id introuvable).');
        $connection->rollback();
        return false;
    }

    if (!$connection->commit()) {
        catalog_set_runtime_error('Commit transaction suppression impossible: ' . $connection->error);
        $connection->rollback();
        return false;
    }

    catalog_set_runtime_error('');
    return true;
}
