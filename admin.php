<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_store.php';

function admin_session_timeout_seconds()
{
    $configured = defined('ADMIN_SESSION_TIMEOUT_SECONDS') ? (int) ADMIN_SESSION_TIMEOUT_SECONDS : 1800;
    return $configured > 120 ? $configured : 120;
}

function admin_session_fingerprint()
{
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return hash('sha256', $ua . '|' . $ip);
}

function admin_clear_auth_session()
{
    unset($_SESSION['catalog_admin']);
    unset($_SESSION['catalog_admin_auth_at']);
    unset($_SESSION['catalog_admin_last_activity']);
    unset($_SESSION['catalog_admin_fingerprint']);
}

function admin_mark_authenticated_session()
{
    session_regenerate_id(true);
    $_SESSION['catalog_admin'] = true;
    $_SESSION['catalog_admin_auth_at'] = time();
    $_SESSION['catalog_admin_last_activity'] = time();
    $_SESSION['catalog_admin_fingerprint'] = admin_session_fingerprint();
}

function admin_is_authenticated_session_valid()
{
    if (empty($_SESSION['catalog_admin']) || $_SESSION['catalog_admin'] !== true) {
        return false;
    }

    $fingerprint = (string) ($_SESSION['catalog_admin_fingerprint'] ?? '');
    if ($fingerprint === '' || !hash_equals($fingerprint, admin_session_fingerprint())) {
        return false;
    }

    $lastActivity = (int) ($_SESSION['catalog_admin_last_activity'] ?? 0);
    if ($lastActivity <= 0) {
        return false;
    }

    if ((time() - $lastActivity) > admin_session_timeout_seconds()) {
        return false;
    }

    $_SESSION['catalog_admin_last_activity'] = time();
    return true;
}

function admin_normalize_spec_label($label)
{
    $label = trim((string) $label);
    $label = strtr($label, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i',
        'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ÿ' => 'y',
        'À' => 'a', 'Â' => 'a', 'Ä' => 'a',
        'Ç' => 'c',
        'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'Î' => 'i', 'Ï' => 'i',
        'Ô' => 'o', 'Ö' => 'o',
        'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
        'Ÿ' => 'y'
    ]);
    $label = strtolower($label);
    return preg_replace('/\s+/', ' ', $label);
}

function admin_known_spec_labels($type)
{
    if ($type === 'part') {
        return [
            'famille',
            'compatibilite',
            'diametre',
            'entraxe',
            'etat',
            'reference',
            'garantie'
        ];
    }

    return [
        'marque',
        'modele',
        'annee',
        'kilometrage',
        'carburant',
        'boite',
        'couleur'
    ];
}

function admin_specs_to_map($specs)
{
    $map = [];
    $lines = preg_split('/\r\n|\r|\n/', (string) $specs);
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || strpos($line, ':') === false) {
            continue;
        }
        list($label, $value) = explode(':', $line, 2);
        $normalizedLabel = admin_normalize_spec_label($label);
        $normalizedValue = trim((string) $value);
        if ($normalizedLabel !== '' && $normalizedValue !== '') {
            $map[$normalizedLabel] = $normalizedValue;
        }
    }
    return $map;
}

function admin_specs_map_value($map, $aliases)
{
    foreach ($aliases as $alias) {
        $key = admin_normalize_spec_label($alias);
        if (isset($map[$key])) {
            return (string) $map[$key];
        }
    }
    return '';
}

function admin_specs_extra($specs, $type)
{
    $extras = [];
    $known = admin_known_spec_labels($type);
    $knownMap = array_fill_keys($known, true);
    $lines = preg_split('/\r\n|\r|\n/', (string) $specs);

    foreach ($lines as $line) {
        $trimmed = trim((string) $line);
        if ($trimmed === '') {
            continue;
        }

        if (strpos($trimmed, ':') !== false) {
            list($label) = explode(':', $trimmed, 2);
            $normalizedLabel = admin_normalize_spec_label($label);
            if (isset($knownMap[$normalizedLabel])) {
                continue;
            }
        }

        $extras[] = $trimmed;
    }

    return implode("\n", $extras);
}

function admin_build_specs_from_payload($payload, $type)
{
    $lines = [];

    if ($type === 'part') {
        $partFields = [
            'part_family' => ['label' => 'Famille'],
            'part_compatibility' => ['label' => 'Compatibilite'],
            'part_diameter' => ['label' => 'Diametre'],
            'part_spacing' => ['label' => 'Entraxe'],
            'part_condition' => ['label' => 'Etat'],
            'part_reference' => ['label' => 'Reference'],
            'part_warranty' => ['label' => 'Garantie']
        ];

        foreach ($partFields as $field => $meta) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value !== '') {
                $lines[] = $meta['label'] . ' : ' . $value;
            }
        }
    } else {
        $vehicleFields = [
            'vehicle_brand' => ['label' => 'Marque'],
            'vehicle_model' => ['label' => 'Modele'],
            'vehicle_year' => ['label' => 'Annee'],
            'vehicle_km' => ['label' => 'Kilometrage', 'suffix' => ' km'],
            'vehicle_fuel' => ['label' => 'Carburant'],
            'vehicle_gearbox' => ['label' => 'Boite'],
            'vehicle_color' => ['label' => 'Couleur']
        ];

        foreach ($vehicleFields as $field => $meta) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value !== '') {
                $lines[] = $meta['label'] . ' : ' . $value . ($meta['suffix'] ?? '');
            }
        }
    }

    $extra = trim((string) ($payload['specs_extra'] ?? ''));
    if ($extra !== '') {
        foreach (preg_split('/\r\n|\r|\n/', $extra) as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
    }

    return implode("\n", $lines);
}

function admin_log_operation($action, $status, $context = [])
{
    $entry = [
        'timestamp' => date('c'),
        'action' => (string) $action,
        'status' => (string) $status,
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'context' => is_array($context) ? $context : ['value' => (string) $context]
    ];

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return;
    }

    $logPath = __DIR__ . '/data/admin_ops.log';
    @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function admin_collect_specs_values($items, $aliases, $stripKm = false)
{
    $values = [];
    foreach ($items as $item) {
        $map = admin_specs_to_map((string) ($item['specs'] ?? ''));
        $value = admin_specs_map_value($map, $aliases);
        if ($stripKm) {
            $value = preg_replace('/\s*km$/i', '', (string) $value);
        }
        $value = trim((string) $value);
        if ($value !== '') {
            $values[] = $value;
        }
    }

    $values = array_values(array_unique($values));
    natcasesort($values);
    return array_values($values);
}

function admin_merge_suggestions($fixed, $dynamic)
{
    $merged = array_values(array_unique(array_filter(array_map('trim', array_merge($fixed, $dynamic)))));
    natcasesort($merged);
    return array_values($merged);
}

function admin_sanitize_date($value, $fallback = '')
{
    $value = trim((string) $value);
    if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    if ($value !== '' && preg_match('/^(\d{2})-(\d{2})-(\d{2})$/', $value, $matches)) {
        $year = (int) $matches[3];
        $year += ($year >= 70 ? 1900 : 2000);
        $normalized = sprintf('%04d-%02d-%02d', $year, (int) $matches[2], (int) $matches[1]);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) && strtotime($normalized) !== false) {
            return $normalized;
        }
    }
    if ($value !== '' && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $matches)) {
        $normalized = sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) && strtotime($normalized) !== false) {
            return $normalized;
        }
    }
    return $fallback;
}

function admin_sanitize_month($value, $fallback = '')
{
    $value = trim((string) $value);
    if ($value !== '' && preg_match('/^\d{4}-\d{2}$/', $value)) {
        return $value;
    }
    return $fallback;
}

function admin_item_in_transaction($item, $type)
{
    $requests = [];
    if ($type === 'part') {
        $requests = is_array($item['part_requests'] ?? null) ? $item['part_requests'] : [];
    } else {
        $requests = is_array($item['vehicle_requests'] ?? null) ? $item['vehicle_requests'] : [];
    }

    foreach ($requests as $request) {
        if (($request['request_status'] ?? 'queued') === 'active') {
            return true;
        }
    }

    return false;
}

function admin_part_current_request($item)
{
    if (($item['type'] ?? '') !== 'part') {
        return null;
    }
    return function_exists('catalog_part_current_request') ? catalog_part_current_request($item) : null;
}

function admin_part_transfer_countdown_label($request)
{
    if (!function_exists('catalog_part_request_remaining_seconds')) {
        return '';
    }

    $seconds = catalog_part_request_remaining_seconds($request);
    if ($seconds === null) {
        return '';
    }

    $hours = (int) floor($seconds / 3600);
    $minutes = (int) floor(($seconds % 3600) / 60);
    return sprintf('%02dh %02dmin', $hours, $minutes);
}

function admin_part_transfer_status_label($request)
{
    $request = is_array($request) ? $request : [];
    $status = (string) ($request['transfer_verification_status'] ?? 'none');
    if ($status === 'pending') {
        return 'Virement a verifier';
    }
    if ($status === 'verified') {
        return 'Virement valide';
    }
    if ($status === 'rejected') {
        return 'Virement rejete';
    }
    if ($status === 'expired') {
        return 'Delai expire';
    }
    return 'En attente client';
}

function admin_vehicle_current_request($item)
{
    if (($item['type'] ?? '') !== 'vehicle') {
        return null;
    }

    $currentId = (string) ($item['current_vehicle_request_id'] ?? '');
    foreach ((array) ($item['vehicle_requests'] ?? []) as $request) {
        if ((string) ($request['id'] ?? '') === $currentId && ($request['request_status'] ?? 'queued') === 'active') {
            return $request;
        }
    }

    return null;
}

function admin_vehicle_transaction_countdown_label($item)
{
    $startedAt = strtotime((string) ($item['transaction_started_at'] ?? ''));
    if ($startedAt === false || empty($item['transaction_in_progress'])) {
        return '';
    }

    $remaining = ($startedAt + (12 * 3600)) - time();
    if ($remaining <= 0) {
        return '00h 00min';
    }

    $hours = (int) floor($remaining / 3600);
    $minutes = (int) floor(($remaining % 3600) / 60);
    return sprintf('%02dh %02dmin', $hours, $minutes);
}

function admin_item_matches_filters($item, $type, $search, $statusFilter)
{
    $search = trim((string) $search);
    if ($search !== '') {
        $haystack = strtolower(
            (string) ($item['title'] ?? '') . ' ' .
            (string) ($item['subtitle'] ?? '') . ' ' .
            (string) ($item['short_description'] ?? '') . ' ' .
            (string) ($item['specs'] ?? '')
        );
        if (strpos($haystack, strtolower($search)) === false) {
            return false;
        }
    }

    $status = (string) ($item['status'] ?? 'available');
    if ($statusFilter === 'available' && $status !== 'available') {
        return false;
    }
    if ($statusFilter === 'reserved' && $status !== 'reserved') {
        return false;
    }
    if ($statusFilter === 'transaction' && !admin_item_in_transaction($item, $type)) {
        return false;
    }

    return true;
}

function admin_google_calendar_event_url($appointment)
{
    $date = trim((string) ($appointment['date'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return 'https://calendar.google.com/calendar/u/0/r';
    }

    $name = trim((string) ($appointment['nom'] ?? 'Client'));
    $service = trim((string) ($appointment['service'] ?? 'Rendez-vous atelier'));
    $phone = trim((string) ($appointment['telephone'] ?? ''));
    $email = trim((string) ($appointment['email'] ?? ''));
    $time = trim((string) ($appointment['heure'] ?? ''));

    $summary = 'RDV Clinik Auto - ' . $name;
    $details = "Service: " . $service . "\nClient: " . $name;
    if ($phone !== '') {
        $details .= "\nTelephone: " . $phone;
    }
    if ($email !== '') {
        $details .= "\nEmail: " . $email;
    }

    if ($time !== '' && preg_match('/^\d{2}:\d{2}$/', $time)) {
        $start = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if ($start instanceof DateTime) {
            $end = clone $start;
            $end->modify('+1 hour');
            $dates = $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis');
        } else {
            $dates = str_replace('-', '', $date) . '/' . str_replace('-', '', date('Y-m-d', strtotime($date . ' +1 day')));
        }
    } else {
        $dates = str_replace('-', '', $date) . '/' . str_replace('-', '', date('Y-m-d', strtotime($date . ' +1 day')));
    }

    $params = [
        'action' => 'TEMPLATE',
        'text' => $summary,
        'dates' => $dates,
        'details' => $details,
        'location' => 'Clinik Auto, 118 Clos des Teppes, 74950 Scionzier'
    ];

    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

function admin_ics_escape($value)
{
    $value = str_replace("\\", "\\\\", (string) $value);
    $value = str_replace(';', '\\;', $value);
    $value = str_replace(',', '\\,', $value);
    return str_replace(["\r\n", "\r", "\n"], '\\n', $value);
}

function admin_build_rdv_ics($appointments)
{
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Clinik Auto//Agenda RDV//FR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH'
    ];

    foreach ($appointments as $appointment) {
        $date = trim((string) ($appointment['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }

        $name = trim((string) ($appointment['nom'] ?? 'Client'));
        $service = trim((string) ($appointment['service'] ?? 'Rendez-vous atelier'));
        $time = trim((string) ($appointment['heure'] ?? ''));
        $email = trim((string) ($appointment['email'] ?? ''));
        $phone = trim((string) ($appointment['telephone'] ?? ''));
        $uid = 'rdv-' . (int) ($appointment['id'] ?? 0) . '-' . md5($date . '|' . $name) . '@clinikauto.local';
        $createdAt = gmdate('Ymd\THis\Z');
        $summary = admin_ics_escape('RDV Clinik Auto - ' . $name);
        $description = 'Service: ' . $service;
        if ($phone !== '') {
            $description .= "\nTelephone: " . $phone;
        }
        if ($email !== '') {
            $description .= "\nEmail: " . $email;
        }
        $description = admin_ics_escape($description);

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . $createdAt;

        if ($time !== '' && preg_match('/^\d{2}:\d{2}$/', $time)) {
            $startLocal = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time, new DateTimeZone('Europe/Paris'));
            if ($startLocal instanceof DateTime) {
                $endLocal = clone $startLocal;
                $endLocal->modify('+1 hour');
                $startUtc = clone $startLocal;
                $startUtc->setTimezone(new DateTimeZone('UTC'));
                $endUtc = clone $endLocal;
                $endUtc->setTimezone(new DateTimeZone('UTC'));
                $lines[] = 'DTSTART:' . $startUtc->format('Ymd\THis\Z');
                $lines[] = 'DTEND:' . $endUtc->format('Ymd\THis\Z');
            }
        } else {
            $startDate = str_replace('-', '', $date);
            $endDate = str_replace('-', '', date('Y-m-d', strtotime($date . ' +1 day')));
            $lines[] = 'DTSTART;VALUE=DATE:' . $startDate;
            $lines[] = 'DTEND;VALUE=DATE:' . $endDate;
        }

        $lines[] = 'SUMMARY:' . $summary;
        $lines[] = 'DESCRIPTION:' . $description;
        $lines[] = 'LOCATION:' . admin_ics_escape('Clinik Auto, 118 Clos des Teppes, 74950 Scionzier');
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines) . "\r\n";
}

function admin_rdv_reminder_label($status)
{
    $status = trim((string) $status);
    if ($status === 'sent') {
        return 'Envoye';
    }
    if ($status === 'failed') {
        return 'Echec';
    }
    return 'En attente';
}

function admin_format_short_date($date)
{
    $date = trim((string) $date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d-m-y', $timestamp);
}

function admin_rdv_is_completed($appointment)
{
    return trim((string) ($appointment['status'] ?? '')) === 'Termine';
}

function admin_update_password_hash_in_config($newHash)
{
    $configPath = __DIR__ . '/config.php';
    $content = @file_get_contents($configPath);
    if ($content === false) {
        return [false, 'Impossible de lire config.php'];
    }

    $escapedHash = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $newHash);
    $replacement = "define('ADMIN_PASSWORD_HASH', '" . $escapedHash . "');";
    $updated = preg_replace("/define\\(\\s*['\"]ADMIN_PASSWORD_HASH['\"]\\s*,\\s*['\"][^'\"]*['\"]\\s*\\);/", $replacement, $content, 1, $count);

    if ($updated === null || (int) $count !== 1) {
        return [false, 'Impossible de mettre à jour le hash admin dans config.php'];
    }

    $written = @file_put_contents($configPath, $updated);
    if ($written === false) {
        return [false, 'Écriture refusée sur config.php (droits insuffisants)'];
    }

    return [true, 'Mot de passe administrateur mis à jour dans config.php'];
}

function admin_verify_password($plainPassword)
{
    $stored = defined('ADMIN_PASSWORD_HASH') ? (string) ADMIN_PASSWORD_HASH : '';
    if ($stored === '') {
        return false;
    }

    $algoInfo = password_get_info($stored);
    if (($algoInfo['algo'] ?? 0) !== 0) {
        return password_verify((string) $plainPassword, $stored);
    }

    // Compatibilite: si une valeur en clair est stockee dans config.php.
    return hash_equals($stored, (string) $plainPassword);
}

function admin_reset_file_path()
{
    return __DIR__ . '/data/admin_password_reset.json';
}

function admin_reset_load_state()
{
    $path = admin_reset_file_path();
    if (!file_exists($path)) {
        return ['token_hash' => '', 'expires_at' => 0];
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return ['token_hash' => '', 'expires_at' => 0];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['token_hash' => '', 'expires_at' => 0];
    }

    return [
        'token_hash' => (string) ($decoded['token_hash'] ?? ''),
        'expires_at' => (int) ($decoded['expires_at'] ?? 0)
    ];
}

function admin_reset_save_state($state)
{
    $path = admin_reset_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $payload = json_encode([
        'token_hash' => (string) ($state['token_hash'] ?? ''),
        'expires_at' => (int) ($state['expires_at'] ?? 0)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return false;
    }

    return @file_put_contents($path, $payload) !== false;
}

function admin_reset_clear_token()
{
    return admin_reset_save_state(['token_hash' => '', 'expires_at' => 0]);
}

function admin_reset_issue_token(&$plainToken)
{
    $plainToken = bin2hex(random_bytes(24));
    $state = [
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => time() + (30 * 60)
    ];

    return admin_reset_save_state($state);
}

function admin_reset_token_is_valid($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }

    $state = admin_reset_load_state();
    if ((int) ($state['expires_at'] ?? 0) < time()) {
        admin_reset_clear_token();
        return false;
    }

    $savedHash = (string) ($state['token_hash'] ?? '');
    if ($savedHash === '') {
        return false;
    }

    return hash_equals($savedHash, hash('sha256', $token));
}

function admin_password_reset_target_email()
{
    if (defined('ADMIN_PASSWORD_RESET_EMAIL') && trim((string) ADMIN_PASSWORD_RESET_EMAIL) !== '') {
        return trim((string) ADMIN_PASSWORD_RESET_EMAIL);
    }

    return defined('ADMIN_LOGIN') ? trim((string) ADMIN_LOGIN) : '';
}

function admin_build_reset_url($token)
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin.php'));
    $directory = trim((string) dirname($script));
    $directory = ($directory === '.' || $directory === '/') ? '' : $directory;
    $basePath = ($directory !== '' ? $directory : '') . '/admin.php';

    $params = [
        'reset_token' => $token
    ];

    return $scheme . '://' . $host . $basePath . '?' . http_build_query($params);
}

function admin_send_password_reset_email($token)
{
    $target = admin_password_reset_target_email();
    if ($target === '' || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $resetUrl = admin_build_reset_url($token);
    $subject = 'Clinik Auto - Reinitialisation du mot de passe admin';
    $body = "Demande de reinitialisation recue.\n\n" .
        "Lien valable 30 minutes :\n" . $resetUrl . "\n\n" .
        "Si vous n'etes pas a l'origine de cette demande, ignorez ce message.";

    return catalog_send_email($target, $subject, $body, defined('ADMIN_LOGIN') ? (string) ADMIN_LOGIN : '');
}

function admin_sms_templates_file_path()
{
    return __DIR__ . '/data/admin_sms_templates.json';
}

function admin_sms_default_templates()
{
    return [
        [
            'id' => 'work_done',
            'label' => 'Travaux termines',
            'body' => 'Bonjour {NOM}, vos travaux sont termines. Votre vehicule est pret. Clinik Auto.'
        ],
        [
            'id' => 'part_ready',
            'label' => 'Piece disponible',
            'body' => 'Bonjour {NOM}, votre piece est disponible a l atelier. Clinik Auto.'
        ],
        [
            'id' => 'quote_ready',
            'label' => 'Devis pret',
            'body' => 'Bonjour {NOM}, votre devis est pret. Vous pouvez nous contacter pour validation. Clinik Auto.'
        ]
    ];
}

function admin_sms_load_templates()
{
    $templates = admin_sms_default_templates();
    $path = admin_sms_templates_file_path();
    if (!file_exists($path)) {
        return $templates;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $templates;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $templates;
    }

    $known = [];
    foreach ($templates as $item) {
        $known[(string) ($item['id'] ?? '')] = true;
    }

    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = trim((string) ($item['id'] ?? ''));
        $label = trim((string) ($item['label'] ?? ''));
        $body = trim((string) ($item['body'] ?? ''));
        if ($id === '' || $label === '' || $body === '' || isset($known[$id])) {
            continue;
        }
        $templates[] = [
            'id' => $id,
            'label' => $label,
            'body' => $body
        ];
        $known[$id] = true;
    }

    return $templates;
}

function admin_sms_save_templates($templates)
{
    $path = admin_sms_templates_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $payload = json_encode(array_values($templates), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return false;
    }

    return @file_put_contents($path, $payload) !== false;
}

function admin_sms_phone_digits($phone)
{
    return preg_replace('/\D+/', '', (string) $phone);
}

function admin_sms_phone_link_target($phone)
{
    $digits = admin_sms_phone_digits($phone);
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '0') === 0 && strlen($digits) > 1) {
        return '+33' . substr($digits, 1);
    }
    if (strpos($digits, '33') === 0) {
        return '+' . $digits;
    }
    return '+' . $digits;
}

function admin_customer_display_name($customer)
{
    if (!is_array($customer)) {
        return '';
    }
    $type = (string) ($customer['customer_type'] ?? 'individual');
    $firstname = trim((string) ($customer['firstname'] ?? ''));
    $lastname = trim((string) ($customer['lastname'] ?? ''));
    if ($type === 'professional') {
        return $lastname !== '' ? $lastname : $firstname;
    }
    return trim($firstname . ' ' . $lastname);
}

function admin_sms_render_message($templateBody, $customerName)
{
    $name = trim((string) $customerName);
    if ($name === '') {
        $name = 'client';
    }
    return str_replace('{NOM}', $name, (string) $templateBody);
}

function admin_sms_find_template_by_id($templates, $templateId)
{
    foreach ((array) $templates as $template) {
        if ((string) ($template['id'] ?? '') === (string) $templateId) {
            return $template;
        }
    }
    return null;
}

catalog_track_visit('admin');

$remoteIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$allowedIps = defined('ADMIN_ALLOWED_IPS') && is_array(ADMIN_ALLOWED_IPS) ? ADMIN_ALLOWED_IPS : [];
$isIpAllowed = empty($allowedIps) || in_array($remoteIp, $allowedIps, true);

if (!$isIpAllowed) {
    http_response_code(403);
    echo '<h1>Accès refusé</h1><p>' . htmlspecialchars(defined('ADMIN_SECURITY_NOTICE') ? ADMIN_SECURITY_NOTICE : 'Accès non autorisé.', ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

$hiddenEntryEnabled = defined('ADMIN_HIDDEN_ENTRY_ENABLED') ? (bool) ADMIN_HIDDEN_ENTRY_ENABLED : false;
$hiddenEntryKey = defined('ADMIN_HIDDEN_ENTRY_KEY') ? trim((string) ADMIN_HIDDEN_ENTRY_KEY) : '';

if ($hiddenEntryEnabled && !isset($_SESSION['catalog_admin_gate_ok'])) {
    header('Location: admin_gate.php');
    exit;
}

if (isset($_GET['logout'])) {
    // Conserver le gate dans la session pour ne pas re-bloquer l'acces au formulaire de connexion
    $gateOk = !empty($_SESSION['catalog_admin_gate_ok']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    if ($gateOk) {
        $_SESSION['catalog_admin_gate_ok'] = true;
    }
    $_SESSION['catalog_admin_customer_auto_refresh'] = '1';
    header('Location: index.html');
    exit;
}

$resetInfo = '';
$resetError = '';
$resetToken = trim((string) ($_GET['reset_token'] ?? $_POST['reset_token'] ?? ''));
$resetTokenValid = ($resetToken !== '' && admin_reset_token_is_valid($resetToken));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_admin_password_reset') {
    $emailInput = trim((string) ($_POST['reset_email_input'] ?? ''));
    $configuredTarget = admin_password_reset_target_email();

    if ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $resetError = 'Adresse email invalide pour la reinitialisation.';
    } elseif ($configuredTarget === '' || strcasecmp($emailInput, $configuredTarget) !== 0) {
        // Adresse erronée : envoyer une alerte à l'admin
        $subject = 'Clinik Auto - Tentative d\'accès administrateur';
        $body = "Une tentative d'accès à l'espace administrateur a été détectée.\n\n" .
                "Adresse email saisie : " . $emailInput . "\n" .
                "Adresse autorisée : " . $configuredTarget . "\n\n" .
                "Heure : " . date('d/m/Y à H:i:s') . "\n" .
                "IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue');
        catalog_send_email($configuredTarget, $subject, $body, '');
        $resetError = 'Adresse non autorisee pour la reinitialisation.';
    } else {
        $token = '';
        if (!admin_reset_issue_token($token)) {
            $resetError = 'Impossible de generer un token de reinitialisation.';
        } elseif (!admin_send_password_reset_email($token)) {
            $resetError = 'Echec d\'envoi du mail de reinitialisation. Verifiez SMTP.';
            admin_reset_clear_token();
        } else {
            $resetInfo = 'Email de reinitialisation envoye a ' . $configuredTarget . '.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_admin_password_reset') {
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

    if (!$resetTokenValid) {
        $resetError = 'Lien de reinitialisation invalide ou expire.';
    } elseif (strlen($newPassword) < 10) {
        $resetError = 'Le nouveau mot de passe doit contenir au moins 10 caracteres.';
    } elseif ($newPassword !== $newPasswordConfirm) {
        $resetError = 'La confirmation du nouveau mot de passe ne correspond pas.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        list($updated, $note) = admin_update_password_hash_in_config($newHash);
        if ($updated) {
            admin_reset_clear_token();
            $resetInfo = $note . ' Vous pouvez vous reconnecter.';
            $resetTokenValid = false;
            $resetToken = '';
        } else {
            $resetError = $note;
        }
    }
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($login === ADMIN_LOGIN && admin_verify_password($password)) {
        admin_mark_authenticated_session();
        header('Location: admin.php');
        exit;
    }

    $loginError = 'Identifiants administrateur invalides.';
}

$isAuthenticated = admin_is_authenticated_session_valid();
if (!$isAuthenticated && !empty($_SESSION['catalog_admin'])) {
    admin_clear_auth_session();
    if ($loginError === '') {
        $loginError = 'Session administrateur expiree ou invalide. Merci de vous reconnecter.';
    }
}

$flashMessage = '';
$flashError = '';
$dashboardInfo = '';
$calendarSyncInfo = '';
$securityInfo = '';
$targetDate = admin_sanitize_date($_REQUEST['target_date'] ?? ($_SESSION['catalog_admin_target_date'] ?? ''), date('Y-m-d'));
$targetMonth = admin_sanitize_month($_REQUEST['target_month'] ?? ($_SESSION['catalog_admin_target_month'] ?? ''), date('Y-m'));
$targetMonthStart = $targetMonth . '-01';
$targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
$rdvEditId = (int) ($_GET['rdv_edit'] ?? 0);
$rdvEditItem = $rdvEditId > 0 ? catalog_rdv_get_by_id($rdvEditId) : null;
$editType = (isset($_GET['type']) && $_GET['type'] === 'part') ? 'part' : 'vehicle';
$editItem = catalog_empty_item($editType);
$customerSearch = trim((string) ($_GET['customer_search'] ?? ''));
$customerRegistrationSearch = strtoupper(trim((string) ($_GET['customer_registration'] ?? '')));
$customerTypeFilter = trim((string) ($_GET['customer_type_filter'] ?? ($_SESSION['catalog_admin_customer_type_filter'] ?? 'all')));
$customerSort = trim((string) ($_GET['customer_sort'] ?? ($_SESSION['catalog_admin_customer_sort'] ?? 'updated_desc')));
$customerEditId = (int) ($_GET['customer_edit'] ?? 0);
$customerEditItem = $customerEditId > 0 ? catalog_get_customer_profile_by_id($customerEditId) : null;
$customerAutoRefresh = (string) ($_REQUEST['customer_auto_refresh'] ?? ($_SESSION['catalog_admin_customer_auto_refresh'] ?? '1')) === '1';
$customerCampaignSubject = trim((string) ($_POST['campaign_subject'] ?? ($_SESSION['catalog_admin_campaign_subject'] ?? '')));
$customerCampaignBody = trim((string) ($_POST['campaign_body'] ?? ($_SESSION['catalog_admin_campaign_body'] ?? '')));
$smsTemplates = admin_sms_load_templates();
$smsQuickPhone = trim((string) ($_POST['sms_quick_phone'] ?? ''));
$smsQuickName = trim((string) ($_POST['sms_quick_name'] ?? ''));
$smsQuickTemplateId = trim((string) ($_POST['sms_template_id'] ?? ((string) ($smsTemplates[0]['id'] ?? 'work_done'))));
$smsQuickPreparedPhone = '';
$smsQuickPreparedMessage = '';
$smsQuickPreparedHref = '';
$smsQuickDetectedName = '';
$smsQuickMatchedCustomerId = 0;
$smsTemplateNewLabel = trim((string) ($_POST['sms_template_new_label'] ?? ''));
$smsTemplateNewBody = trim((string) ($_POST['sms_template_new_body'] ?? ''));
$bankAccountEditId = (int) ($_GET['bank_edit'] ?? 0);
$bankAccounts = catalog_bank_accounts_load();
$selectedBankAccount = catalog_bank_account_selected();
$bankAccountEditItem = null;
$bankAccountForm = [
    'id' => '',
    'label' => '',
    'beneficiary' => '',
    'iban' => '',
    'bic' => '',
    'bank_name' => '',
    'note' => '',
    'is_active' => true,
    'set_default' => false
];
$inventorySearch = trim((string) ($_GET['inventory_search'] ?? ''));
$vehicleFilter = trim((string) ($_GET['vehicle_filter'] ?? ($_SESSION['catalog_admin_vehicle_filter'] ?? 'all')));
$partFilter = trim((string) ($_GET['part_filter'] ?? ($_SESSION['catalog_admin_part_filter'] ?? 'all')));
$adminDbAvailable = catalog_using_database();

if (isset($_SESSION['catalog_admin_flash_message'])) {
    $flashMessage = (string) $_SESSION['catalog_admin_flash_message'];
    unset($_SESSION['catalog_admin_flash_message']);
}
if (isset($_SESSION['catalog_admin_flash_error'])) {
    $flashError = (string) $_SESSION['catalog_admin_flash_error'];
    unset($_SESSION['catalog_admin_flash_error']);
}

foreach ($bankAccounts as $bankAccountItem) {
    if ((int) ($bankAccountItem['id'] ?? 0) === $bankAccountEditId || (string) ($bankAccountItem['id'] ?? '') === (string) ($_GET['bank_edit'] ?? '')) {
        $bankAccountEditItem = $bankAccountItem;
        break;
    }
}

if (is_array($bankAccountEditItem)) {
    $bankAccountForm = [
        'id' => (string) ($bankAccountEditItem['id'] ?? ''),
        'label' => (string) ($bankAccountEditItem['label'] ?? ''),
        'beneficiary' => (string) ($bankAccountEditItem['beneficiary'] ?? ''),
        'iban' => (string) ($bankAccountEditItem['iban'] ?? ''),
        'bic' => (string) ($bankAccountEditItem['bic'] ?? ''),
        'bank_name' => (string) ($bankAccountEditItem['bank_name'] ?? ''),
        'note' => (string) ($bankAccountEditItem['note'] ?? ''),
        'is_active' => !empty($bankAccountEditItem['is_active']),
        'set_default' => !empty($bankAccountEditItem['is_default'])
    ];
}

if (!in_array($vehicleFilter, ['all', 'available', 'reserved', 'transaction', 'only_vehicle'], true)) {
    $vehicleFilter = 'all';
}
if (!in_array($partFilter, ['all', 'available', 'reserved', 'transaction', 'only_part'], true)) {
    $partFilter = 'all';
}
if (!in_array($customerTypeFilter, ['all', 'individual', 'professional'], true)) {
    $customerTypeFilter = 'all';
}
if (!in_array($customerSort, ['updated_desc', 'name_asc', 'name_desc', 'recent_first', 'oldest_first', 'type_then_name', 'incomplete_only'], true)) {
    $customerSort = 'updated_desc';
}

if ($isAuthenticated) {
    $_SESSION['catalog_admin_vehicle_filter'] = $vehicleFilter;
    $_SESSION['catalog_admin_part_filter'] = $partFilter;
    $_SESSION['catalog_admin_target_date'] = $targetDate;
    $_SESSION['catalog_admin_target_month'] = $targetMonth;
    $_SESSION['catalog_admin_customer_auto_refresh'] = $customerAutoRefresh ? '1' : '0';
    $_SESSION['catalog_admin_customer_type_filter'] = $customerTypeFilter;
    $_SESSION['catalog_admin_customer_sort'] = $customerSort;
    $_SESSION['catalog_admin_campaign_subject'] = $customerCampaignSubject;
    $_SESSION['catalog_admin_campaign_body'] = $customerCampaignBody;
}

if ($isAuthenticated) {
    $requestedAdminSection = trim((string) ($_POST['admin_section'] ?? $_GET['admin_section'] ?? ($_SESSION['catalog_admin_last_section'] ?? 'section-inventory')));
    if (!in_array($requestedAdminSection, ['section-security', 'section-kpi', 'section-analytics', 'section-banks', 'section-reminders', 'section-customers', 'section-create-ad', 'section-vehicles', 'section-parts', 'section-inventory'], true)) {
        $requestedAdminSection = 'section-inventory';
    }
    $_SESSION['catalog_admin_last_section'] = $requestedAdminSection;

    $adminRedirectWithFlash = function ($anchor = '') use (&$flashMessage, &$flashError, $requestedAdminSection) {
        if ($flashMessage !== '') {
            $_SESSION['catalog_admin_flash_message'] = $flashMessage;
        }
        if ($flashError !== '') {
            $_SESSION['catalog_admin_flash_error'] = $flashError;
        }

        $targetAnchor = $anchor !== '' ? $anchor : $requestedAdminSection;
        $safeAnchor = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $targetAnchor);
        $_SESSION['catalog_admin_last_section'] = $safeAnchor !== '' ? $safeAnchor : 'section-inventory';
        header('Location: admin.php' . ($safeAnchor !== '' ? '#' . $safeAnchor : ''));
        exit;
    };

    $postAction = trim((string) ($_POST['action'] ?? ''));
    $dbRequiredActions = ['save', 'delete', 'vehicle_sold', 'vehicle_release', 'part_confirm_sale', 'part_cancel_sale', 'part_validate_transfer', 'part_reject_transfer', 'rdv_update', 'rdv_delete', 'rdv_remind_one', 'rdv_complete', 'customer_create', 'customer_update', 'customer_delete', 'customer_bulk_email'];
    $adminRequiresDb = defined('CATALOG_ADMIN_REQUIRE_DB') ? CATALOG_ADMIN_REQUIRE_DB : true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($postAction, $dbRequiredActions, true) && !$adminDbAvailable && $adminRequiresDb) {
        $actionAnchors = [
            'save' => 'section-create-ad',
            'delete' => 'section-inventory',
            'vehicle_sold' => 'section-vehicles',
            'vehicle_release' => 'section-vehicles',
            'part_confirm_sale' => 'section-parts',
            'part_cancel_sale' => 'section-parts',
            'part_validate_transfer' => 'section-parts',
            'part_reject_transfer' => 'section-parts',
            'rdv_update' => 'section-reminders',
            'rdv_delete' => 'section-reminders',
            'rdv_remind_one' => 'section-reminders',
            'rdv_complete' => 'section-reminders',
            'customer_create' => 'section-customers',
            'customer_update' => 'section-customers',
            'customer_delete' => 'section-customers',
            'customer_bulk_email' => 'section-customers'
        ];
        $flashError = 'Base de donnees indisponible: action admin "' . $postAction . '" refusee pour eviter une desynchronisation avec le JSON local.';
        admin_log_operation($postAction, 'blocked_db_unavailable', [
            'post_keys' => array_keys($_POST)
        ]);
        $adminRedirectWithFlash($actionAnchors[$postAction] ?? 'section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sms_template_save') {
        $label = trim((string) ($_POST['sms_template_new_label'] ?? ''));
        $body = trim((string) ($_POST['sms_template_new_body'] ?? ''));
        if ($label === '' || $body === '') {
            $flashError = 'Nom du modele et texte SMS obligatoires.';
        } else {
            $templateId = 'tpl_' . substr(sha1($label . '|' . $body . '|' . microtime(true)), 0, 12);
            $smsTemplates[] = [
                'id' => $templateId,
                'label' => $label,
                'body' => $body
            ];
            if (admin_sms_save_templates($smsTemplates)) {
                $flashMessage = 'Modele SMS enregistre.';
            } else {
                $flashError = 'Impossible d\'enregistrer le modele SMS.';
            }
        }
        $adminRedirectWithFlash('section-customers');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bank_account_save') {
        $bankPayload = [
            'id' => trim((string) ($_POST['bank_id'] ?? '')),
            'label' => trim((string) ($_POST['bank_label'] ?? '')),
            'beneficiary' => trim((string) ($_POST['bank_beneficiary'] ?? '')),
            'iban' => trim((string) ($_POST['bank_iban'] ?? '')),
            'bic' => trim((string) ($_POST['bank_bic'] ?? '')),
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')),
            'note' => trim((string) ($_POST['bank_note'] ?? '')),
            'is_active' => isset($_POST['bank_is_active']) ? 1 : 0,
            'set_default' => isset($_POST['bank_set_default']) ? 1 : 0
        ];
        list($ok, $note, $savedBankAccount) = catalog_bank_account_upsert($bankPayload);
        if ($ok) {
            $flashMessage = $note;
            if (is_array($savedBankAccount)) {
                $bankAccountForm = [
                    'id' => (string) ($savedBankAccount['id'] ?? ''),
                    'label' => (string) ($savedBankAccount['label'] ?? ''),
                    'beneficiary' => (string) ($savedBankAccount['beneficiary'] ?? ''),
                    'iban' => (string) ($savedBankAccount['iban'] ?? ''),
                    'bic' => (string) ($savedBankAccount['bic'] ?? ''),
                    'bank_name' => (string) ($savedBankAccount['bank_name'] ?? ''),
                    'note' => (string) ($savedBankAccount['note'] ?? ''),
                    'is_active' => !empty($savedBankAccount['is_active']),
                    'set_default' => !empty($savedBankAccount['is_default'])
                ];
            }
        } else {
            $flashError = $note;
            $bankAccountForm = [
                'id' => (string) $bankPayload['id'],
                'label' => (string) $bankPayload['label'],
                'beneficiary' => (string) $bankPayload['beneficiary'],
                'iban' => strtoupper(str_replace(' ', '', (string) $bankPayload['iban'])),
                'bic' => strtoupper(str_replace(' ', '', (string) $bankPayload['bic'])),
                'bank_name' => (string) $bankPayload['bank_name'],
                'note' => (string) $bankPayload['note'],
                'is_active' => !empty($bankPayload['is_active']),
                'set_default' => !empty($bankPayload['set_default'])
            ];
        }
        $bankAccounts = catalog_bank_accounts_load();
        $selectedBankAccount = catalog_bank_account_selected();
        $adminRedirectWithFlash('section-banks');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bank_account_delete') {
        $bankId = trim((string) ($_POST['bank_id'] ?? ''));
        list($ok, $note) = catalog_bank_account_delete($bankId);
        if ($ok) {
            $flashMessage = $note;
        } else {
            $flashError = $note;
        }
        $bankAccounts = catalog_bank_accounts_load();
        $selectedBankAccount = catalog_bank_account_selected();
        $adminRedirectWithFlash('section-banks');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bank_account_select') {
        $bankId = trim((string) ($_POST['bank_id'] ?? ''));
        list($ok, $note) = catalog_bank_account_set_default($bankId);
        if ($ok) {
            $flashMessage = $note;
        } else {
            $flashError = $note;
        }
        $bankAccounts = catalog_bank_accounts_load();
        $selectedBankAccount = catalog_bank_account_selected();
        $adminRedirectWithFlash('section-banks');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sms_quick_prepare') {
        $smsQuickPhone = trim((string) ($_POST['sms_quick_phone'] ?? ''));
        $smsQuickName = trim((string) ($_POST['sms_quick_name'] ?? ''));
        $smsQuickTemplateId = trim((string) ($_POST['sms_template_id'] ?? ''));

        $template = admin_sms_find_template_by_id($smsTemplates, $smsQuickTemplateId);
        if (!$template) {
            $template = $smsTemplates[0] ?? null;
            $smsQuickTemplateId = (string) ($template['id'] ?? '');
        }

        $phoneDigits = admin_sms_phone_digits($smsQuickPhone);
        if ($phoneDigits === '') {
            $flashError = 'Renseignez un numero de telephone pour preparer le SMS.';
        } elseif (!$template) {
            $flashError = 'Aucun modele SMS disponible.';
        } else {
            $matchedCustomer = null;
            if ($adminDbAvailable) {
                $matchedCustomer = catalog_get_customer_profile_by_phone($phoneDigits);
            }

            if (is_array($matchedCustomer)) {
                $smsQuickMatchedCustomerId = (int) ($matchedCustomer['id'] ?? 0);
                $smsQuickDetectedName = admin_customer_display_name($matchedCustomer);
            }

            $nameForMessage = $smsQuickDetectedName !== '' ? $smsQuickDetectedName : $smsQuickName;
            if ($nameForMessage === '') {
                $flashError = 'Aucun client detecte pour ce numero. Merci de renseigner un nom pour le message.';
            } else {
                $smsQuickPreparedPhone = admin_sms_phone_link_target($phoneDigits);
                $smsQuickPreparedMessage = admin_sms_render_message((string) ($template['body'] ?? ''), $nameForMessage);
                $smsQuickPreparedHref = 'sms:' . $smsQuickPreparedPhone . '?body=' . rawurlencode($smsQuickPreparedMessage);
                $flashMessage = 'SMS prepare. Vous pouvez copier le texte ou ouvrir l\'application SMS.';
            }
        }
    }

    if (($_GET['action'] ?? '') === 'export_rdv_ics') {
        $appointments = catalog_rdv_for_date($targetDate);
        $ics = admin_build_rdv_ics($appointments);
        $fileDate = preg_replace('/[^0-9\-]/', '', $targetDate);
        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: attachment; filename="clinikauto-rdv-' . $fileDate . '.ics"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $ics;
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_rdv_reminders') {
        $targetDate = admin_sanitize_date($_POST['target_date'] ?? '', date('Y-m-d', strtotime('+1 day')));
        if ($targetDate === '') {
            $flashError = 'Choisissez une date de rendez-vous pour les rappels.';
        } else {
            list($sentCount, $failedCount) = catalog_send_rdv_reminders($targetDate);
            $dashboardInfo = 'Rappels envoyés pour le ' . htmlspecialchars($targetDate, ENT_QUOTES, 'UTF-8') . ' : ' . (int) $sentCount . ' succès, ' . (int) $failedCount . ' échecs.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sync_google_calendar') {
        $sync = catalog_google_calendar_sync_bidirectional(true);
        if (!empty($sync['ok'])) {
            $calendarSyncInfo = $sync['message'];
        } else {
            $flashError = ($flashError !== '' ? $flashError . ' ' : '') . $sync['message'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rdv_remind_one') {
        $rdvId = (int) ($_POST['rdv_id'] ?? 0);
        list($ok, $note) = catalog_send_rdv_reminder_by_id($rdvId);
        if ($ok) {
            $flashMessage = $note;
            admin_log_operation('rdv_remind_one', 'success', ['rdv_id' => $rdvId]);
        } else {
            $flashError = $note;
            admin_log_operation('rdv_remind_one', 'failed', ['rdv_id' => $rdvId, 'error' => $note]);
        }
        $adminRedirectWithFlash('section-reminders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rdv_update') {
        $rdvId = (int) ($_POST['rdv_id'] ?? 0);
        list($ok, $note) = catalog_update_rdv($rdvId, [
            'nom' => $_POST['rdv_nom'] ?? '',
            'email' => $_POST['rdv_email'] ?? '',
            'telephone' => $_POST['rdv_telephone'] ?? '',
            'address_line' => $_POST['rdv_address_line'] ?? '',
            'postal_code' => $_POST['rdv_postal_code'] ?? '',
            'city' => $_POST['rdv_city'] ?? '',
            'date' => $_POST['rdv_date'] ?? '',
            'heure' => $_POST['rdv_heure'] ?? '',
            'service' => $_POST['rdv_service'] ?? '',
            'status' => $_POST['rdv_status'] ?? ''
        ]);

        if ($ok) {
            if (defined('GOOGLE_CALENDAR_ENABLED') && GOOGLE_CALENDAR_ENABLED) {
                $sync = catalog_google_calendar_sync_bidirectional(true);
                if (!empty($sync['ok'])) {
                    $note .= ' ' . $sync['message'];
                } elseif (!empty($sync['message'])) {
                    $note .= ' Synchronisation Google: ' . $sync['message'];
                }
            }
            $flashMessage = $note;
            admin_log_operation('rdv_update', 'success', ['rdv_id' => $rdvId]);
        } else {
            $flashError = $note;
            admin_log_operation('rdv_update', 'failed', ['rdv_id' => $rdvId, 'error' => $note]);
        }
        $adminRedirectWithFlash('section-reminders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rdv_complete') {
        $rdvId = (int) ($_POST['rdv_id'] ?? 0);
        $rdvItem = $rdvId > 0 ? catalog_rdv_get_by_id($rdvId) : null;
        list($ok, $note) = $rdvItem
            ? catalog_update_rdv($rdvId, [
                'nom' => $rdvItem['nom'] ?? '',
                'email' => $rdvItem['email'] ?? '',
                'telephone' => $rdvItem['telephone'] ?? '',
                'address_line' => $rdvItem['address_line'] ?? '',
                'postal_code' => $rdvItem['postal_code'] ?? '',
                'city' => $rdvItem['city'] ?? '',
                'date' => $rdvItem['date'] ?? '',
                'heure' => $rdvItem['heure'] ?? '',
                'service' => $rdvItem['service'] ?? '',
                'status' => 'Termine'
            ])
            : [false, 'Rendez-vous introuvable.'];

        if ($ok) {
            $flashMessage = 'Rendez-vous marque comme termine.';
            admin_log_operation('rdv_complete', 'success', ['rdv_id' => $rdvId]);
        } else {
            $flashError = $note;
            admin_log_operation('rdv_complete', 'failed', ['rdv_id' => $rdvId, 'error' => $note]);
        }
        $adminRedirectWithFlash('section-reminders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rdv_delete') {
        $rdvId = (int) ($_POST['rdv_id'] ?? 0);
        list($ok, $note) = catalog_delete_rdv($rdvId);
        if ($ok) {
            $flashMessage = $note;
            admin_log_operation('rdv_delete', 'success', ['rdv_id' => $rdvId]);
        } else {
            $flashError = $note;
            admin_log_operation('rdv_delete', 'failed', ['rdv_id' => $rdvId, 'error' => $note]);
        }
        $adminRedirectWithFlash('section-reminders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_admin_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

        if (!admin_verify_password($currentPassword)) {
            $flashError = 'Mot de passe actuel incorrect.';
        } elseif (strlen($newPassword) < 10) {
            $flashError = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $flashError = 'La confirmation du nouveau mot de passe ne correspond pas.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            list($updated, $note) = admin_update_password_hash_in_config($newHash);
            if ($updated) {
                $securityInfo = $note . ' Reconnectez-vous pour appliquer le nouveau hash.';
            } else {
                $flashError = $note;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'customer_update') {
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        list($ok, $note) = catalog_update_customer_profile($customerId, [
            'customer_type' => $_POST['customer_type'] ?? 'individual',
            'firstname' => $_POST['firstname'] ?? '',
            'lastname' => $_POST['lastname'] ?? '',
            'address_line' => $_POST['address_line'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'city' => $_POST['city'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'registration' => $_POST['registration'] ?? ''
        ]);

        if ($ok) {
            $flashMessage = $note;
            $customerEditId = $customerId;
            $customerEditItem = catalog_get_customer_profile_by_id($customerEditId);
        } else {
            $flashError = $note;
            $customerEditId = $customerId;
            $customerEditItem = catalog_get_customer_profile_by_id($customerEditId);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'customer_create') {
        list($ok, $note, $createdId) = catalog_create_customer_profile([
            'customer_type' => $_POST['customer_type'] ?? 'individual',
            'firstname' => $_POST['firstname'] ?? '',
            'lastname' => $_POST['lastname'] ?? '',
            'address_line' => $_POST['address_line'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'city' => $_POST['city'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'registration' => $_POST['registration'] ?? ''
        ], 'admin_manual');

        if ($ok) {
            $flashMessage = $note;
            $customerEditId = (int) $createdId;
            $customerEditItem = $customerEditId > 0 ? catalog_get_customer_profile_by_id($customerEditId) : null;
        } else {
            $flashError = $note;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'customer_delete') {
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        list($ok, $note) = catalog_delete_customer_profile($customerId);
        if ($ok) {
            $flashMessage = $note;
            $customerEditId = 0;
            $customerEditItem = null;
            admin_log_operation('customer_delete', 'success', ['customer_id' => $customerId]);
        } else {
            $flashError = $note;
            admin_log_operation('customer_delete', 'failed', ['customer_id' => $customerId, 'error' => $note]);
        }
        $adminRedirectWithFlash('section-customers');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'customer_bulk_email') {
        $campaignScope = trim((string) ($_POST['campaign_scope'] ?? 'selected'));
        $campaignSubject = trim((string) ($_POST['campaign_subject'] ?? ''));
        $campaignBody = trim((string) ($_POST['campaign_body'] ?? ''));
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['customer_ids'] ?? [])), function ($id) {
            return $id > 0;
        })));

        $effectiveIds = $selectedIds;
        if ($campaignScope === 'filtered' && empty($effectiveIds)) {
            $postedSearch = trim((string) ($_POST['customer_search'] ?? ''));
            $postedRegistration = strtoupper(trim((string) ($_POST['customer_registration'] ?? '')));
            $postedCustomerTypeFilter = trim((string) ($_POST['customer_type_filter'] ?? 'all'));
            if (!in_array($postedCustomerTypeFilter, ['all', 'individual', 'professional'], true)) {
                $postedCustomerTypeFilter = 'all';
            }
            $filterQuery = trim($postedSearch . ' ' . $postedRegistration);
            $filteredCustomers = catalog_customer_profiles($filterQuery, $postedCustomerTypeFilter, $customerSort);
            foreach ($filteredCustomers as $customerItem) {
                $candidateId = (int) ($customerItem['id'] ?? 0);
                if ($candidateId > 0) {
                    $effectiveIds[] = $candidateId;
                }
            }
            $effectiveIds = array_values(array_unique($effectiveIds));
        }

        if ($campaignSubject === '' || $campaignBody === '') {
            $flashError = 'Sujet et message sont obligatoires pour la campagne email.';
        } elseif (empty($effectiveIds)) {
            $flashError = 'Aucune fiche client selectionnee pour la campagne email.';
        } else {
            list($sent, $failed, $skipped, $details) = catalog_send_customer_campaign(
                $effectiveIds,
                $campaignSubject,
                $campaignBody,
                defined('ADMIN_LOGIN') ? (string) ADMIN_LOGIN : ''
            );

            $flashMessage = 'Campagne email terminee: ' . (int) $sent . ' envoye(s), ' . (int) $failed . ' echec(s), ' . (int) $skipped . ' ignore(s).';
            if ($failed > 0 && !empty($details)) {
                $first = $details[0];
                $flashError = 'Au moins un envoi a echoue (exemple: ' . (string) ($first['email'] ?? 'email inconnu') . ').';
            }
            admin_log_operation('customer_bulk_email', 'completed', [
                'scope' => $campaignScope,
                'requested' => count($effectiveIds),
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped
            ]);
        }
        $adminRedirectWithFlash('section-customers');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0 && catalog_delete_item($deleteId)) {
            // Vérification arrière-plan : l'annonce doit être absente de la DB
            $stillExists = catalog_find_item($deleteId);
            if ($stillExists !== null) {
                $flashError = 'Suppression signalée OK mais l\'annonce ID ' . $deleteId . ' est encore présente en base. Possible problème de contrainte FK ou de connexion DB.';
                admin_log_operation('delete', 'verify_failed', ['id' => $deleteId]);
            } else {
                $flashMessage = 'Annonce ID ' . $deleteId . ' supprimée — vérification DB : absente. ✓';
                admin_log_operation('delete', 'success', ['id' => $deleteId]);
            }
        } else {
            $deleteErrorDetail = trim((string) catalog_get_runtime_error());
            $flashError = 'Suppression impossible pour l\'annonce ID ' . $deleteId . '.' . ($deleteErrorDetail !== '' ? ' Détail : ' . $deleteErrorDetail : ' Aucune erreur retournée — vérifiez la connexion DB.');
            admin_log_operation('delete', 'failed', ['id' => $deleteId, 'runtime_error' => $deleteErrorDetail]);
        }
        $adminRedirectWithFlash('section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vehicle_sold') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && catalog_vehicle_mark_sold_and_delete($id)) {
            $flashMessage = 'Vente véhicule confirmée : annonce supprimée et notifications envoyées aux personnes concernées.';
        } else {
            $flashError = 'Impossible de confirmer cette vente véhicule.';
        }
        $adminRedirectWithFlash('section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vehicle_release') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && catalog_vehicle_release_transaction($id)) {
            $flashMessage = 'Transaction véhicule non conclue : passage automatique au candidat suivant si présent, sinon retour en disponible.';
        } else {
            $flashError = 'Impossible de remettre ce véhicule en disponibilité.';
        }
        $adminRedirectWithFlash('section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'part_confirm_sale') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && catalog_part_confirm_sale($id)) {
            $flashMessage = 'Vente pièce confirmée : annonce supprimée.';
        } else {
            $flashError = 'Impossible de confirmer la vente de cette pièce.';
        }
        $adminRedirectWithFlash('section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'part_cancel_sale') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && catalog_part_cancel_sale($id)) {
            $flashMessage = 'Vente pièce non confirmée : passage automatique au candidat suivant si présent, sinon retour en disponible.';
        } else {
            $flashError = 'Impossible de repasser cette pièce en disponible.';
        }
        $adminRedirectWithFlash('section-inventory');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'part_validate_transfer') {
        $id = (int) ($_POST['id'] ?? 0);
        list($ok, $note) = function_exists('catalog_part_process_current_request_resolution')
            ? catalog_part_process_current_request_resolution($id, 'verified')
            : [false, 'Traitement du virement indisponible.'];
        if ($ok) {
            $flashMessage = $note;
        } else {
            $flashError = $note;
        }
        $adminRedirectWithFlash('section-parts');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'part_reject_transfer') {
        $id = (int) ($_POST['id'] ?? 0);
        list($ok, $note) = function_exists('catalog_part_process_current_request_resolution')
            ? catalog_part_process_current_request_resolution($id, 'rejected')
            : [false, 'Traitement du virement indisponible.'];
        if ($ok) {
            $flashMessage = $note;
        } else {
            $flashError = $note;
        }
        $adminRedirectWithFlash('section-parts');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
        $payload = $_POST;
        $payloadType = (($payload['type'] ?? 'vehicle') === 'part') ? 'part' : 'vehicle';
        $payload['specs'] = admin_build_specs_from_payload($payload, $payloadType);
        $removeImageIds = array_values(array_filter(array_map('strval', $_POST['remove_images'] ?? [])));
        $saveMode = $adminDbAvailable ? '[Mode DB]' : '[Mode JSON ⚠ — DB non disponible]';
        list($success, $errors, $savedItem) = catalog_upsert_item($payload, $_FILES['images'] ?? [], $removeImageIds);

        if ($success) {
            $savedType = (($savedItem['type'] ?? 'vehicle') === 'part') ? 'piece' : 'vehicule';
            $savedId = (int) ($savedItem['id'] ?? 0);
            $saveRuntimeError = trim((string) catalog_get_runtime_error());
            // Vérification arrière-plan : l'annonce doit être présente en DB avec le bon ID
            $verifyItem = ($savedId > 0) ? catalog_find_item($savedId) : null;
            $activeFilter = ($savedType === 'piece') ? $partFilter : $vehicleFilter;
            $filterInfo = ($activeFilter !== 'all') ? ' [Filtre actif: ' . htmlspecialchars($activeFilter) . ' — pensez à Réinitialiser si l\'annonce ne s\'affiche pas]' : '';
            $warningInfo = ($saveRuntimeError !== '') ? ' [Note: ' . htmlspecialchars($saveRuntimeError, ENT_QUOTES, 'UTF-8') . ']' : '';
            if ($verifyItem === null) {
                $flashError = $saveMode . ' Enregistrement signalé OK (ID ' . $savedId . ') mais l\'annonce est introuvable en base après sauvegarde.';
                admin_log_operation('save', 'verify_failed', ['id' => $savedId, 'type' => $savedType]);
            } else {
                $flashMessage = $saveMode . ' Annonce ' . $savedType . ' enregistrée (ID ' . $savedId . ') — vérification DB : présente. ✓' . $filterInfo . $warningInfo;
                admin_log_operation('save', 'success', ['id' => $savedId, 'type' => $savedType, 'filter' => $activeFilter, 'runtime_error' => $saveRuntimeError]);
            }
            $editType = $savedItem['type'];
            $editItem = $savedItem;
            // Redirect vers ?edit=ID pour afficher immédiatement l'annonce créée avec ses images
            $_SESSION['catalog_admin_flash_message'] = $flashMessage;
            header('Location: admin.php?edit=' . (int) $savedId . '#section-create-ad');
            exit;
        } else {
            $saveRuntimeError = trim((string) catalog_get_runtime_error());
            $flashError = $saveMode . ' ' . implode(' ', $errors) . ($saveRuntimeError !== '' ? ' Détail: ' . $saveRuntimeError : '');
            admin_log_operation('save', 'failed', [
                'errors' => $errors,
                'runtime_error' => $saveRuntimeError,
                'payload_id' => (int) ($payload['id'] ?? 0),
                'payload_type' => (string) ($payload['type'] ?? '')
            ]);
            $editType = $savedItem['type'] ?? $editType;
            $editItem = $savedItem;
        }
    } elseif (isset($_GET['edit'])) {
        $candidate = catalog_find_item((int) $_GET['edit']);
        if ($candidate) {
            $editItem = $candidate;
            $editType = $candidate['type'];
        }
    }

    $isManualGoogleSyncPost = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'sync_google_calendar');
    if (defined('GOOGLE_CALENDAR_ENABLED') && GOOGLE_CALENDAR_ENABLED && !$isManualGoogleSyncPost) {
        $sync = catalog_google_calendar_sync_bidirectional(false);
        if (!empty($sync['ok'])) {
            $calendarSyncInfo = $sync['message'];
        } elseif ($sync['message'] !== '') {
            $flashError = ($flashError !== '' ? $flashError . ' ' : '') . $sync['message'];
        }
    }
}

$vehiclesRaw = [];
$partsRaw = [];
if ($isAuthenticated && !$adminDbAvailable) {
    if ((defined('CATALOG_ADMIN_REQUIRE_DB') ? CATALOG_ADMIN_REQUIRE_DB : true)) {
        $flashError = ($flashError !== '' ? $flashError . ' ' : '') . 'Connexion DB indisponible: mode strict actif, certaines actions restent bloquees pour eviter une desynchronisation.';
        admin_log_operation('admin_view', 'db_unavailable_strict', []);
    } else {
        $flashError = ($flashError !== '' ? $flashError . ' ' : '') . 'Connexion DB indisponible: bascule automatique en mode JSON local (lecture/edition locale).';
        admin_log_operation('admin_view', 'db_unavailable_json_fallback', []);
    }
}
$vehiclesRaw = catalog_all_items('vehicle');
$partsRaw = catalog_all_items('part');
$metrics = catalog_metrics_snapshot();
$customerQuery = trim($customerSearch . ' ' . $customerRegistrationSearch);
$customers = catalog_customer_profiles($customerQuery, $customerTypeFilter, $customerSort);
$customerAppointments = $customerEditId > 0 ? catalog_customer_rdv_timeline($customerEditId, 50) : [];
$rdvPreview = $isAuthenticated ? catalog_rdv_for_date($targetDate) : [];
$rdvMonthPreview = $isAuthenticated ? catalog_rdv_for_period($targetMonthStart, $targetMonthEnd) : [];

$vehicles = array_values(array_filter($vehiclesRaw, function ($item) use ($inventorySearch, $vehicleFilter) {
    return admin_item_matches_filters($item, 'vehicle', $inventorySearch, $vehicleFilter);
}));

$parts = array_values(array_filter($partsRaw, function ($item) use ($inventorySearch, $partFilter) {
    return admin_item_matches_filters($item, 'part', $inventorySearch, $partFilter);
}));

$showVehiclesBlock = ($partFilter !== 'only_part');
$showPartsBlock = ($vehicleFilter !== 'only_vehicle');
if (!$showVehiclesBlock && !$showPartsBlock) {
    $showVehiclesBlock = true;
    $showPartsBlock = true;
}

if (!$showVehiclesBlock) {
    $vehicles = [];
}
if (!$showPartsBlock) {
    $parts = [];
}
$progressMax = max(1, (int) max(
    $metrics['customers_total'] ?? 0,
    $metrics['annonces_total'] ?? 0,
    $metrics['transactions_concluded'] ?? 0,
    $metrics['transactions_failed'] ?? 0,
    $metrics['traffic_monthly'] ?? 0
));
$barCustomers = (int) round(((int) ($metrics['customers_total'] ?? 0) / $progressMax) * 100);
$barAnnonces = (int) round(((int) ($metrics['annonces_total'] ?? 0) / $progressMax) * 100);
$barSales = (int) round(((int) ($metrics['transactions_concluded'] ?? 0) / $progressMax) * 100);
$barFailed = (int) round(((int) ($metrics['transactions_failed'] ?? 0) / $progressMax) * 100);
$barTraffic = (int) round(((int) ($metrics['traffic_monthly'] ?? 0) / $progressMax) * 100);

$editSpecsMap = admin_specs_to_map((string) ($editItem['specs'] ?? ''));
$vehicleBrand = admin_specs_map_value($editSpecsMap, ['marque']);
$vehicleModel = admin_specs_map_value($editSpecsMap, ['modele']);
$vehicleYear = admin_specs_map_value($editSpecsMap, ['annee']);
$vehicleKm = preg_replace('/\s*km$/i', '', admin_specs_map_value($editSpecsMap, ['kilometrage', 'kilometrage km']));
$vehicleFuel = admin_specs_map_value($editSpecsMap, ['carburant']);
$vehicleGearbox = admin_specs_map_value($editSpecsMap, ['boite']);
$vehicleColor = admin_specs_map_value($editSpecsMap, ['couleur']);

$partFamily = admin_specs_map_value($editSpecsMap, ['famille']);
$partCompatibility = admin_specs_map_value($editSpecsMap, ['compatibilite']);
$partDiameter = admin_specs_map_value($editSpecsMap, ['diametre']);
$partSpacing = admin_specs_map_value($editSpecsMap, ['entraxe']);
$partCondition = admin_specs_map_value($editSpecsMap, ['etat']);
$partReference = admin_specs_map_value($editSpecsMap, ['reference']);
$partWarranty = admin_specs_map_value($editSpecsMap, ['garantie']);
$specsExtra = admin_specs_extra((string) ($editItem['specs'] ?? ''), $editType);

$vehicleBrandSuggestions = admin_merge_suggestions(
    ['Audi', 'BMW', 'Citroen', 'Dacia', 'Fiat', 'Ford', 'Hyundai', 'Kia', 'Mercedes', 'Nissan', 'Opel', 'Peugeot', 'Renault', 'Seat', 'Skoda', 'Toyota', 'Volkswagen', 'Volvo'],
    admin_collect_specs_values($vehiclesRaw, ['marque'])
);
$partFamilySuggestions = admin_merge_suggestions(
    ['Carrosserie', 'Echappement', 'Eclairage', 'Electronique', 'Freinage', 'Habitacle', 'Moteur', 'Refroidissement', 'Roue', 'Suspension', 'Transmission'],
    admin_collect_specs_values($partsRaw, ['famille'])
);
$vehicleFuelSuggestions = admin_merge_suggestions(
    ['Diesel', 'Essence', 'Electrique', 'GPL', 'Hybride'],
    admin_collect_specs_values($vehiclesRaw, ['carburant'])
);
$vehicleGearboxSuggestions = admin_merge_suggestions(
    ['Automatique', 'Manuelle'],
    admin_collect_specs_values($vehiclesRaw, ['boite'])
);
$partConditionSuggestions = admin_merge_suggestions(
    ['Comme neuf', 'Tres bon etat', 'Bon etat', 'Usage normal'],
    admin_collect_specs_values($partsRaw, ['etat'])
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration annonces - Clinik Auto</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/postal-city.js" defer></script>
    <script src="assets/customer-type.js" defer></script>
    <?php echo catalog_get_google_analytics_script(); ?>
</head>
<body>
    <header>
        <div class="site-brand">
            <a class="site-brand-link" href="index.html" aria-label="Clinik Auto accueil">
                <img class="site-logo" src="assets/logo.png" alt="Logo Clinik Auto">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="catalogue/catalogue.php">Catalogue</a></li>
                <li><a href="contact/contact.php">Contact</a></li>
                <?php if ($isAuthenticated): ?>
                    <li><a href="admin.php?logout=1" onclick="return confirm('Confirmer la déconnexion ?');">Déconnexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="admin-page" id="admin-top">
        <span class="hero-badge">Espace privé</span>
        <h2>Gestion des annonces</h2>
        <p>Créez, modifiez, réservez ou supprimez vos annonces véhicules et pièces. Les images restent liées à l'annonce et disparaissent à sa suppression.</p>

        <?php if (!$isAuthenticated): ?>
            <section class="admin-login-card">
                <h3>Connexion administrateur</h3>
                <?php if ($loginError !== ''): ?>
                    <p class="error-message"><?php echo catalog_escape($loginError); ?></p>
                <?php endif; ?>
                <?php if ($resetError !== ''): ?>
                    <p class="error-message"><?php echo catalog_escape($resetError); ?></p>
                <?php endif; ?>
                <?php if ($resetInfo !== ''): ?>
                    <p class="success-message"><?php echo catalog_escape($resetInfo); ?></p>
                <?php endif; ?>
                <form method="post" class="admin-login-form">
                    <input type="hidden" name="action" value="login">
                    <label>Identifiant
                        <input type="text" name="login" value="" placeholder="Votre identifiant admin" required>
                    </label>
                    <label>Mot de passe
                        <input type="password" name="password" value="" placeholder="Votre mot de passe" required>
                    </label>
                    <button type="submit">Se connecter</button>
                </form>

                <hr>
                <form method="post" class="admin-login-form">
                    <input type="hidden" name="action" value="request_admin_password_reset">
                    <input type="hidden" name="reset_email" value="<?php echo defined('ADMIN_PASSWORD_RESET_EMAIL') ? htmlspecialchars(ADMIN_PASSWORD_RESET_EMAIL, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <label>Email de recuperation
                        <input type="email" name="reset_email_input" placeholder="Saisissez l'adresse de recuperation" required>
                    </label>
                    <button type="submit">Envoyer le mail de reinitialisation</button>
                </form>

                <?php if ($resetTokenValid): ?>
                    <form method="post" class="admin-login-form spaced-section-small">
                        <input type="hidden" name="action" value="apply_admin_password_reset">
                        <input type="hidden" name="reset_token" value="<?php echo catalog_escape($resetToken); ?>">
                        <label>Nouveau mot de passe
                            <input type="password" name="new_password" minlength="10" autocomplete="new-password" required>
                        </label>
                        <label>Confirmer le nouveau mot de passe
                            <input type="password" name="new_password_confirm" minlength="10" autocomplete="new-password" required>
                        </label>
                        <button type="submit">Valider le nouveau mot de passe</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <?php if ($flashMessage !== ''): ?>
                <p class="success-message"><?php echo catalog_escape($flashMessage); ?></p>
            <?php endif; ?>
            <?php if ($flashError !== ''): ?>
                <p class="error-message"><?php echo catalog_escape($flashError); ?></p>
            <?php endif; ?>
            <?php if ($dashboardInfo !== ''): ?>
                <p class="success-message"><?php echo $dashboardInfo; ?></p>
            <?php endif; ?>
            <?php if ($calendarSyncInfo !== ''): ?>
                <p class="success-message"><?php echo catalog_escape($calendarSyncInfo); ?></p>
            <?php endif; ?>
            <?php if ($securityInfo !== ''): ?>
                <p class="success-message"><?php echo catalog_escape($securityInfo); ?></p>
            <?php endif; ?>

            <div class="admin-shell">
                <aside class="admin-quick-nav" aria-label="Navigation rapide administration">
                    <h3>Accès rapide</h3>
                    <div class="admin-quick-item" style="margin-bottom:0.4rem;">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-security">Sécurité admin</button>
                        <a class="admin-section-jump" data-target-section="section-security" href="#section-security" aria-label="Aller à Sécurité admin" title="Aller à Sécurité admin"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-kpi">Vue d'ensemble</button>
                        <a class="admin-section-jump" data-target-section="section-kpi" href="#section-kpi" aria-label="Aller à Vue d'ensemble" title="Aller à Vue d'ensemble"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-analytics">Graphique</button>
                        <a class="admin-section-jump" data-target-section="section-analytics" href="#section-analytics" aria-label="Aller à Graphique" title="Aller à Graphique"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-banks">Comptes virement</button>
                        <a class="admin-section-jump" data-target-section="section-banks" href="#section-banks" aria-label="Aller à Comptes virement" title="Aller à Comptes virement"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-reminders">Rappels RDV</button>
                        <a class="admin-section-jump" data-target-section="section-reminders" href="#section-reminders" aria-label="Aller à Rappels RDV" title="Aller à Rappels RDV"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-customers">Fiches clients</button>
                        <a class="admin-section-jump" data-target-section="section-customers" href="#section-customers" aria-label="Aller à Fiches clients" title="Aller à Fiches clients"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-sms-quick">SMS rapide</button>
                        <a class="admin-section-jump" data-target-section="section-sms-quick" href="#section-sms-quick" aria-label="Aller à SMS rapide" title="Aller à SMS rapide"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-create-ad">Créer une annonce</button>
                        <a class="admin-section-jump" data-target-section="section-create-ad" href="#section-create-ad" aria-label="Aller à Créer une annonce" title="Aller à Créer une annonce"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-vehicles">Gestion véhicules</button>
                        <a class="admin-section-jump" data-target-section="section-vehicles" href="#section-vehicles" aria-label="Aller à Gestion véhicules" title="Aller à Gestion véhicules"></a>
                    </div>
                    <div class="admin-quick-item">
                        <button type="button" class="btn-secondary admin-section-toggle" data-target-section="section-parts">Gestion pièces</button>
                        <a class="admin-section-jump" data-target-section="section-parts" href="#section-parts" aria-label="Aller à Gestion pièces" title="Aller à Gestion pièces"></a>
                    </div>
                    <button type="button" class="btn-secondary admin-quick-top">Retour haut de page</button>
                </aside>

                <div class="admin-shell-content">

            <section id="section-security" class="admin-login-card" style="display:none;">
                <div class="admin-card-head">
                    <div>
                        <h3>Sécurité administrateur</h3>
                        <p>Compte codé en dur: identifiant fixe <strong><?php echo catalog_escape(ADMIN_LOGIN); ?></strong>.</p>
                    </div>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="action" value="change_admin_password">
                    <div class="admin-form-grid">
                        <label>Mot de passe actuel
                            <input type="password" name="current_password" autocomplete="current-password" required>
                        </label>
                        <label>Nouveau mot de passe
                            <input type="password" name="new_password" autocomplete="new-password" minlength="10" required>
                        </label>
                        <label>Confirmer le nouveau mot de passe
                            <input type="password" name="new_password_confirm" autocomplete="new-password" minlength="10" required>
                        </label>
                    </div>
                    <div class="admin-form-actions">
                        <button type="submit">Mettre à jour le mot de passe admin</button>
                    </div>
                </form>
            </section>

            <section id="section-kpi" class="admin-dashboard-grid" style="display:none;">
                <article class="admin-kpi-card">
                    <strong><?php echo (int) ($metrics['customers_total'] ?? 0); ?></strong>
                    <span>Fiches clients créées</span>
                </article>
                <article class="admin-kpi-card">
                    <strong><?php echo (int) ($metrics['in_transaction_people'] ?? 0); ?></strong>
                    <span>Personnes en transaction</span>
                </article>
                <article class="admin-kpi-card">
                    <strong><?php echo (int) (($metrics['vehicles_waiting'] ?? 0) + ($metrics['parts_waiting'] ?? 0)); ?></strong>
                    <span>Personnes en attente</span>
                </article>
                <article class="admin-kpi-card">
                    <strong><?php echo (int) ($metrics['transactions_concluded'] ?? 0); ?></strong>
                    <span>Transactions conclues</span>
                </article>
                <article class="admin-kpi-card">
                    <strong><?php echo (int) ($metrics['transactions_failed'] ?? 0); ?></strong>
                    <span>Transactions échouées</span>
                </article>
                <article class="admin-kpi-card">
                    <strong><?php echo (int) ($metrics['traffic_daily'] ?? 0); ?> / <?php echo (int) ($metrics['traffic_weekly'] ?? 0); ?> / <?php echo (int) ($metrics['traffic_monthly'] ?? 0); ?></strong>
                    <span>Trafic jour / semaine / mois</span>
                </article>
            </section>

            <section id="section-analytics" class="admin-analytics-card" style="display:none;">
                <div class="admin-card-head">
                    <div>
                        <h3>Graphique de progression</h3>
                        <p>Vision synthétique clients, annonces, ventes, échecs et trafic mensuel.</p>
                    </div>
                </div>
                <div class="progress-bars">
                    <div class="progress-row"><span>Clients</span><em style="width: <?php echo $barCustomers; ?>%"></em><strong><?php echo (int) ($metrics['customers_total'] ?? 0); ?></strong></div>
                    <div class="progress-row"><span>Annonces</span><em style="width: <?php echo $barAnnonces; ?>%"></em><strong><?php echo (int) ($metrics['annonces_total'] ?? 0); ?></strong></div>
                    <div class="progress-row"><span>Ventes conclues</span><em style="width: <?php echo $barSales; ?>%"></em><strong><?php echo (int) ($metrics['transactions_concluded'] ?? 0); ?></strong></div>
                    <div class="progress-row"><span>Échecs</span><em style="width: <?php echo $barFailed; ?>%"></em><strong><?php echo (int) ($metrics['transactions_failed'] ?? 0); ?></strong></div>
                    <div class="progress-row"><span>Trafic mensuel</span><em style="width: <?php echo $barTraffic; ?>%"></em><strong><?php echo (int) ($metrics['traffic_monthly'] ?? 0); ?></strong></div>
                </div>
            </section>

            <section id="section-banks" class="admin-reminder-card" style="display:none;">
                <div class="admin-card-head">
                    <div>
                        <h3>Comptes bancaires pour popup virement</h3>
                        <p>Créez plusieurs comptes, modifiez-les, puis selectionnez celui affiche au client lors du clic sur "Valider ma commande".</p>
                    </div>
                </div>

                <?php if ($selectedBankAccount): ?>
                    <p class="form-note">
                        <strong>Compte actuellement diffuse au client :</strong>
                        <?php echo catalog_escape((string) ($selectedBankAccount['label'] ?? 'Compte principal')); ?>
                        <?php if (!empty($selectedBankAccount['iban'])): ?>
                            - IBAN <?php echo catalog_escape((string) ($selectedBankAccount['iban'] ?? '')); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <form method="post" class="admin-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                    <input type="hidden" name="action" value="bank_account_save">
                    <input type="hidden" name="bank_id" value="<?php echo catalog_escape((string) ($bankAccountForm['id'] ?? '')); ?>">
                    <div class="admin-form-grid">
                        <label>Libelle du compte
                            <input type="text" name="bank_label" value="<?php echo catalog_escape((string) ($bankAccountForm['label'] ?? '')); ?>" placeholder="Ex : Compte principal" required>
                        </label>
                        <label>Beneficiaire
                            <input type="text" name="bank_beneficiary" value="<?php echo catalog_escape((string) ($bankAccountForm['beneficiary'] ?? '')); ?>" placeholder="Nom du beneficiaire" required>
                        </label>
                        <label>IBAN
                            <input type="text" name="bank_iban" value="<?php echo catalog_escape((string) ($bankAccountForm['iban'] ?? '')); ?>" placeholder="FR76..." required>
                        </label>
                        <label>BIC (optionnel)
                            <input type="text" name="bank_bic" value="<?php echo catalog_escape((string) ($bankAccountForm['bic'] ?? '')); ?>" placeholder="AGRIFRPP...">
                        </label>
                        <label>Banque (optionnel)
                            <input type="text" name="bank_name" value="<?php echo catalog_escape((string) ($bankAccountForm['bank_name'] ?? '')); ?>" placeholder="Nom de la banque">
                        </label>
                    </div>
                    <label>Message pour le client (optionnel)
                        <textarea name="bank_note" rows="3" placeholder="Informations affichees dans le popup client"><?php echo catalog_escape((string) ($bankAccountForm['note'] ?? '')); ?></textarea>
                    </label>
                    <label class="checkbox-toggle">
                        <input type="checkbox" name="bank_set_default" value="1" <?php echo !empty($bankAccountForm['set_default']) ? 'checked' : ''; ?>>
                        Utiliser ce compte dans la popup client
                    </label>
                    <label class="checkbox-toggle">
                        <input type="checkbox" name="bank_is_active" value="1" <?php echo !empty($bankAccountForm['is_active']) ? 'checked' : ''; ?>>
                        Compte actif
                    </label>
                    <div class="admin-form-actions" style="gap:0.6rem; flex-wrap:wrap;">
                        <button type="submit"><?php echo (string) ($bankAccountForm['id'] ?? '') !== '' ? 'Mettre a jour le compte' : 'Ajouter un compte'; ?></button>
                        <a class="btn-secondary admin-inline-action" href="admin.php#section-banks">Nouveau compte</a>
                    </div>
                </form>

                <div class="table-wrapper admin-rdv-table-wrapper">
                    <table class="admin-rdv-table">
                        <thead>
                            <tr>
                                <th>Compte</th>
                                <th>Coordonnees</th>
                                <th>Etat</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bankAccounts as $bankItem): ?>
                                <?php $bankId = (string) ($bankItem['id'] ?? ''); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo catalog_escape((string) ($bankItem['label'] ?? 'Compte')); ?></strong><br>
                                        <small><?php echo catalog_escape((string) ($bankItem['beneficiary'] ?? '')); ?></small>
                                    </td>
                                    <td>
                                        <strong>IBAN :</strong> <?php echo catalog_escape((string) ($bankItem['iban'] ?? '')); ?><br>
                                        <?php if (!empty($bankItem['bic'])): ?><small><strong>BIC :</strong> <?php echo catalog_escape((string) ($bankItem['bic'] ?? '')); ?></small><br><?php endif; ?>
                                        <?php if (!empty($bankItem['bank_name'])): ?><small><?php echo catalog_escape((string) ($bankItem['bank_name'] ?? '')); ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($bankItem['is_default'])): ?><span class="status-pill">Selectionne</span><?php endif; ?>
                                        <?php if (!empty($bankItem['is_active'])): ?>
                                            <div class="admin-muted-line">Actif</div>
                                        <?php else: ?>
                                            <div class="admin-muted-line" style="color:#b91c1c;">Inactif</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="line-action-link" href="admin.php?bank_edit=<?php echo urlencode($bankId); ?>#section-banks">Modifier</a>
                                        <form method="post" style="display:inline; margin-left:0.45rem;">
                                            <input type="hidden" name="action" value="bank_account_select">
                                            <input type="hidden" name="bank_id" value="<?php echo catalog_escape($bankId); ?>">
                                            <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;">Selectionner</button>
                                        </form>
                                        <form method="post" style="display:inline; margin-left:0.45rem;" onsubmit="return confirm('Supprimer ce compte bancaire ?');">
                                            <input type="hidden" name="action" value="bank_account_delete">
                                            <input type="hidden" name="bank_id" value="<?php echo catalog_escape($bankId); ?>">
                                            <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;color:#b91c1c;">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-reminders" class="admin-reminder-card" style="display:none;">
                <div class="admin-card-head">
                    <div>
                        <h3>Rappels rendez-vous</h3>
                        <p>Pilotage manuel des rappels, identification client et synchronisation calendrier.</p>
                    </div>
                </div>
                <div class="admin-reminder-actions">
                    <form method="post" class="admin-reminder-form">
                        <input type="hidden" name="action" value="send_rdv_reminders">
                        <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                        <label>Date des rendez-vous à rappeler
                            <input type="text" name="target_date" value="<?php echo catalog_escape(admin_format_short_date($targetDate)); ?>" placeholder="01-12-26" inputmode="numeric" pattern="\d{2}-\d{2}-(\d{2}|\d{4})" required>
                        </label>
                        <button type="submit">Envoyer les rappels clients →</button>
                    </form>
                    <form method="post" class="admin-reminder-form">
                        <input type="hidden" name="action" value="sync_google_calendar">
                        <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                        <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                        <button type="submit">Synchroniser Google Agenda maintenant</button>
                    </form>
                    <a class="btn-secondary admin-inline-action" href="admin.php?action=export_rdv_ics&amp;target_date=<?php echo urlencode($targetDate); ?>#section-reminders">Exporter .ics (Google Agenda)</a>
                </div>

                <form method="get" class="admin-reminder-form" style="margin-bottom:1rem;">
                    <label>Afficher tout le mois
                        <input type="month" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                    </label>
                    <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                    <input type="hidden" name="inventory_search" value="<?php echo catalog_escape($inventorySearch); ?>">
                    <input type="hidden" name="vehicle_filter" value="<?php echo catalog_escape($vehicleFilter); ?>">
                    <input type="hidden" name="part_filter" value="<?php echo catalog_escape($partFilter); ?>">
                    <button type="submit">Afficher les RDV du mois</button>
                </form>

                <p class="admin-muted-line" style="margin:-0.2rem 0 1rem 0;">Format d'affichage des dates : 01-12-26</p>

                <?php if ($rdvEditItem): ?>
                    <form method="post" class="admin-reminder-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                        <input type="hidden" name="action" value="rdv_update">
                        <input type="hidden" name="rdv_id" value="<?php echo (int) ($rdvEditItem['id'] ?? 0); ?>">
                        <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                        <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                        <label>Nom client
                            <input type="text" name="rdv_nom" value="<?php echo catalog_escape((string) ($rdvEditItem['nom'] ?? '')); ?>" required>
                        </label>
                        <label>Email
                            <input type="email" name="rdv_email" value="<?php echo catalog_escape((string) ($rdvEditItem['email'] ?? '')); ?>">
                        </label>
                        <label>Téléphone
                            <input type="text" name="rdv_telephone" value="<?php echo catalog_escape((string) ($rdvEditItem['telephone'] ?? '')); ?>">
                        </label>
                        <label>Adresse
                            <input type="text" name="rdv_address_line" value="<?php echo catalog_escape((string) ($rdvEditItem['address_line'] ?? '')); ?>">
                        </label>
                        <div data-postal-city-group data-postal-endpoint="postal_lookup.php" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.8rem; align-items:end;">
                            <label>Code postal
                                <input type="text" name="rdv_postal_code" maxlength="10" value="<?php echo catalog_escape((string) ($rdvEditItem['postal_code'] ?? '')); ?>" data-postal-code-input>
                            </label>
                            <label>Ville
                                <input type="text" name="rdv_city" list="admin-rdv-city-list" value="<?php echo catalog_escape((string) ($rdvEditItem['city'] ?? '')); ?>" data-city-input>
                            </label>
                            <datalist id="admin-rdv-city-list"></datalist>
                            <p class="admin-muted-line" data-postal-city-status style="margin:0;"></p>
                        </div>
                        <label>Date
                            <input type="text" name="rdv_date" value="<?php echo catalog_escape(admin_format_short_date((string) ($rdvEditItem['date'] ?? ''))); ?>" placeholder="01-12-26" inputmode="numeric" pattern="\d{2}-\d{2}-(\d{2}|\d{4})" required>
                        </label>
                        <label>Heure
                            <input type="time" name="rdv_heure" value="<?php echo catalog_escape((string) ($rdvEditItem['heure'] ?? '')); ?>">
                        </label>
                        <label>Service
                            <input type="text" name="rdv_service" value="<?php echo catalog_escape((string) ($rdvEditItem['service'] ?? '')); ?>" required>
                        </label>
                        <label>Statut
                            <select name="rdv_status">
                                <?php $rdvStatusValue = (string) ($rdvEditItem['status'] ?? 'En attente'); ?>
                                <option value="En attente" <?php echo $rdvStatusValue === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="Confirme" <?php echo $rdvStatusValue === 'Confirme' ? 'selected' : ''; ?>>Confirmé</option>
                                <option value="Annule" <?php echo $rdvStatusValue === 'Annule' ? 'selected' : ''; ?>>Annulé</option>
                                <option value="Termine" <?php echo $rdvStatusValue === 'Termine' ? 'selected' : ''; ?>>Terminé</option>
                            </select>
                        </label>
                        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
                            <button type="submit">Enregistrer les modifications</button>
                            <a class="btn-secondary admin-inline-action" href="admin.php?target_date=<?php echo urlencode($targetDate); ?>&amp;target_month=<?php echo urlencode($targetMonth); ?>#section-reminders">Annuler l'édition</a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="table-wrapper admin-rdv-table-wrapper">
                    <table class="admin-rdv-table">
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Client RDV</th>
                                <th>Service</th>
                                <th>Client identifié</th>
                                <th>Immatriculation</th>
                                <th>Rappel</th>
                                <th>Agenda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rdvPreview)): ?>
                                <tr>
                                    <td colspan="7">Aucun rendez-vous trouvé pour cette date.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rdvPreview as $appointment): ?>
                                    <?php if (admin_rdv_is_completed($appointment)) { continue; } ?>
                                    <?php $profile = $appointment['customer_profile'] ?? null; ?>
                                    <tr>
                                        <td><?php echo catalog_escape((string) ($appointment['heure'] ?? 'A confirmer')); ?></td>
                                        <td>
                                            <strong><?php echo catalog_escape((string) ($appointment['nom'] ?? '')); ?></strong><br>
                                            <small><?php echo catalog_escape((string) ($appointment['email'] ?? '')); ?></small><br>
                                            <small><?php echo catalog_escape((string) ($appointment['telephone'] ?? '')); ?></small>
                                            <?php if (!empty($appointment['postal_code']) || !empty($appointment['city'])): ?>
                                                <br><small><?php echo catalog_escape(trim((string) (($appointment['postal_code'] ?? '') . ' ' . ($appointment['city'] ?? '')))); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo catalog_escape((string) ($appointment['service'] ?? '')); ?></td>
                                        <td>
                                            <?php if (!empty($appointment['customer_identified'])): ?>
                                                <span class="status-pill">Profil trouvé</span>
                                                <div class="admin-muted-line"><?php echo catalog_escape(trim((string) (($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? '')))); ?></div>
                                                <?php if (!empty($profile['email'])): ?>
                                                    <a class="line-action-link" href="admin.php?customer_search=<?php echo urlencode((string) $profile['email']); ?>&customer_registration=<?php echo urlencode((string) ($profile['registration'] ?? '')); ?>#section-customers">Ouvrir fiche</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="status-pill is-muted">Non identifié</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo catalog_escape((string) ($profile['registration'] ?? 'Non renseignée')); ?></td>
                                        <td>
                                            <?php
                                                $reminderStatus = (string) ($appointment['reminder_status'] ?? 'pending');
                                                $label = 'En attente';
                                                if ($reminderStatus === 'sent') {
                                                    $label = 'Envoyé';
                                                } elseif ($reminderStatus === 'failed') {
                                                    $label = 'Échec';
                                                }
                                            ?>
                                            <?php echo catalog_escape($label); ?>
                                        </td>
                                        <td>
                                            <a class="line-action-link" href="<?php echo catalog_escape(admin_google_calendar_event_url($appointment)); ?>" target="_blank" rel="noopener noreferrer">Créer événement</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-wrapper admin-rdv-table-wrapper" style="margin-top:1rem;">
                    <table class="admin-rdv-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Source</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rdvMonthPreview)): ?>
                                <tr>
                                    <td colspan="7">Aucun rendez-vous trouvé pour ce mois.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rdvMonthPreview as $appointment): ?>
                                    <?php $appointmentCompleted = admin_rdv_is_completed($appointment); ?>
                                    <tr style="<?php echo $appointmentCompleted ? 'text-decoration:line-through; opacity:0.55;' : ''; ?><?php echo $appointmentCompleted ? ' display:none;' : ''; ?>">
                                        <td><?php echo catalog_escape(admin_format_short_date((string) ($appointment['date'] ?? ''))); ?></td>
                                        <td><?php echo catalog_escape((string) ($appointment['heure'] ?? 'A confirmer')); ?></td>
                                        <td>
                                            <strong><?php echo catalog_escape((string) ($appointment['nom'] ?? '')); ?></strong><br>
                                            <small><?php echo catalog_escape((string) ($appointment['email'] ?? '')); ?></small>
                                        </td>
                                        <td><?php echo catalog_escape((string) ($appointment['service'] ?? '')); ?></td>
                                        <td>
                                            <?php
                                                $source = (string) ($appointment['sync_source'] ?? 'local');
                                                $hasGoogleEvent = trim((string) ($appointment['google_event_id'] ?? '')) !== '';
                                                $sourceLabel = $source === 'google' ? 'Google (manuel)' : 'Site';
                                                if ($source !== 'google' && $hasGoogleEvent) {
                                                    $sourceLabel = 'Site + Google';
                                                }
                                            ?>
                                            <?php echo catalog_escape($sourceLabel); ?>
                                        </td>
                                        <td><?php echo catalog_escape((string) ($appointment['status'] ?? 'En attente')); ?></td>
                                        <td>
                                            <a class="line-action-link" href="admin.php?rdv_edit=<?php echo (int) ($appointment['id'] ?? 0); ?>&amp;target_date=<?php echo urlencode($targetDate); ?>&amp;target_month=<?php echo urlencode($targetMonth); ?>#section-reminders">Modifier</a>
                                            <?php if (!$appointmentCompleted): ?>
                                            <form method="post" style="display:inline; margin-left:0.45rem;" onsubmit="return confirm('Marquer ce rendez-vous comme termine ?');">
                                                <input type="hidden" name="action" value="rdv_complete">
                                                <input type="hidden" name="rdv_id" value="<?php echo (int) ($appointment['id'] ?? 0); ?>">
                                                <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                                                <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                                                <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;">Rendez-vous terminé</button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="post" style="display:inline; margin-left:0.45rem;">
                                                <input type="hidden" name="action" value="rdv_remind_one">
                                                <input type="hidden" name="rdv_id" value="<?php echo (int) ($appointment['id'] ?? 0); ?>">
                                                <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                                                <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                                                <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;">Relancer client</button>
                                            </form>
                                            <form method="post" style="display:inline; margin-left:0.45rem;" onsubmit="return confirm('Supprimer ce rendez-vous ?');">
                                                <input type="hidden" name="action" value="rdv_delete">
                                                <input type="hidden" name="rdv_id" value="<?php echo (int) ($appointment['id'] ?? 0); ?>">
                                                <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                                                <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                                                <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;color:#b91c1c;">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-customers" class="admin-customers-card" style="display:none;">
                <div class="admin-card-head">
                    <div>
                        <h3>Fiches clients</h3>
                        <p>Gestion complete des fiches clients: ajout, modification, suppression, campagnes email, suivi des rendez-vous et rappels.</p>
                    </div>
                </div>

                <form method="post" class="admin-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                    <input type="hidden" name="action" value="customer_create">
                    <div data-customer-type-context>
                    <input type="hidden" name="customer_type" value="individual" data-customer-type-input>
                    <label style="display:flex; align-items:center; gap:0.45rem; margin-bottom:0.6rem;">
                        <input type="checkbox" value="1" data-customer-type-checkbox>
                        Fiche professionnelle
                    </label>
                    <div class="admin-form-grid">
                        <label><span data-type-label-target data-individual-label="Prénom" data-professional-label="Nom du contact">Prénom</span>
                            <input type="text" name="firstname" placeholder="Prénom" data-type-placeholder-target data-individual-placeholder="Prénom" data-professional-placeholder="Nom et prénom du contact" required>
                        </label>
                        <label><span data-type-label-target data-individual-label="Nom" data-professional-label="Raison sociale">Nom</span>
                            <input type="text" name="lastname" placeholder="Nom" data-type-placeholder-target data-individual-placeholder="Nom" data-professional-placeholder="Raison sociale" required>
                        </label>
                        <label>Email
                            <input type="email" name="email" placeholder="email@client.fr" required>
                        </label>
                        <label>Téléphone
                            <input type="text" name="phone" placeholder="06 00 00 00 00">
                        </label>
                        <label>Immatriculation
                            <input type="text" name="registration" placeholder="AA-123-BB">
                        </label>
                        <label>Adresse
                            <input type="text" name="address_line" placeholder="Adresse complète">
                        </label>
                        <div data-postal-city-group data-postal-endpoint="postal_lookup.php" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.8rem; align-items:end;">
                            <label>Code postal
                                <input type="text" name="postal_code" maxlength="10" placeholder="74950" data-postal-code-input>
                            </label>
                            <label>Ville
                                <input type="text" name="city" list="admin-create-customer-city-list" placeholder="Scionzier" data-city-input>
                            </label>
                            <datalist id="admin-create-customer-city-list"></datalist>
                            <p class="admin-muted-line" data-postal-city-status style="margin:0;"></p>
                        </div>
                    </div>
                    <div class="admin-form-actions">
                        <button type="submit">Créer une fiche client manuellement →</button>
                    </div>
                    </div>
                </form>

                <form method="get" class="admin-customer-search" id="customer-search-form">
                    <input type="text" name="customer_search" value="<?php echo catalog_escape($customerSearch); ?>" placeholder="Recherche globale: nom, email, téléphone, code postal, ville">
                    <input type="text" name="customer_registration" value="<?php echo catalog_escape($customerRegistrationSearch); ?>" placeholder="Recherche par immatriculation (ex: AA-123-BB)">
                    <select name="customer_type_filter" aria-label="Filtrer par type client">
                        <option value="all" <?php echo $customerTypeFilter === 'all' ? 'selected' : ''; ?>>Tous les clients</option>
                        <option value="individual" <?php echo $customerTypeFilter === 'individual' ? 'selected' : ''; ?>>Particuliers</option>
                        <option value="professional" <?php echo $customerTypeFilter === 'professional' ? 'selected' : ''; ?>>Professionnels</option>
                    </select>
                    <select name="customer_sort" aria-label="Trier les fiches clients">
                        <option value="updated_desc" <?php echo $customerSort === 'updated_desc' ? 'selected' : ''; ?>>Tri: mises à jour récentes</option>
                        <option value="name_asc" <?php echo $customerSort === 'name_asc' ? 'selected' : ''; ?>>Tri: nom A → Z</option>
                        <option value="name_desc" <?php echo $customerSort === 'name_desc' ? 'selected' : ''; ?>>Tri: nom Z → A</option>
                        <option value="recent_first" <?php echo $customerSort === 'recent_first' ? 'selected' : ''; ?>>Tri: créations récentes</option>
                        <option value="oldest_first" <?php echo $customerSort === 'oldest_first' ? 'selected' : ''; ?>>Tri: plus anciennes</option>
                        <option value="type_then_name" <?php echo $customerSort === 'type_then_name' ? 'selected' : ''; ?>>Tri: type puis nom</option>
                        <option value="incomplete_only" <?php echo $customerSort === 'incomplete_only' ? 'selected' : ''; ?>>Fiches incomplètes</option>
                    </select>
                    <input type="hidden" name="customer_auto_refresh" value="0">
                    <label style="display:flex; align-items:center; gap:0.4rem; white-space:nowrap;">
                        <input type="checkbox" id="customer-auto-refresh-toggle" name="customer_auto_refresh" value="1" <?php echo $customerAutoRefresh ? 'checked' : ''; ?>>
                        Auto-refresh 5 min
                    </label>
                    <span id="customer-auto-refresh-status" style="font-size:0.82rem; font-weight:700; color:<?php echo $customerAutoRefresh ? '#15803d' : '#b91c1c'; ?>; white-space:nowrap;">
                        <?php echo $customerAutoRefresh ? 'ON' : 'OFF'; ?>
                    </span>
                    <input type="hidden" name="inventory_search" value="<?php echo catalog_escape($inventorySearch); ?>">
                    <input type="hidden" name="vehicle_filter" value="<?php echo catalog_escape($vehicleFilter); ?>">
                    <input type="hidden" name="part_filter" value="<?php echo catalog_escape($partFilter); ?>">
                    <button type="submit">Rechercher</button>
                </form>

                <div class="admin-customer-panel-meta">
                    <strong><?php echo count($customers); ?></strong>
                    <span>fiche(s) affichée(s)</span>
                    <span>La liste reste compacte et défilante, même si le volume augmente.</span>
                </div>

                <div class="admin-customer-layout">
                    <div class="admin-customer-list" id="customer-list-selectable">
                        <label class="admin-customer-row admin-customer-row-selectall">
                            <input type="checkbox" id="customer-select-all">
                            <span class="admin-customer-row-main">
                                <strong>Tout cocher / tout décocher</strong>
                                <small>Sélection rapide pour campagne email ou traitement groupé</small>
                            </span>
                        </label>
                        <?php foreach ($customers as $customer): ?>
                            <?php $rowId = (int) ($customer['id'] ?? 0); ?>
                            <?php
                                $rowType = (string) ($customer['customer_type'] ?? 'individual');
                                $rowFirst = trim((string) ($customer['firstname'] ?? ''));
                                $rowLast = trim((string) ($customer['lastname'] ?? ''));
                                $displayName = trim($rowFirst . ' ' . $rowLast);
                                if ($rowType === 'professional') {
                                    $displayName = $rowLast;
                                    if ($rowFirst !== '') {
                                        $displayName .= ' (contact: ' . $rowFirst . ')';
                                    }
                                }
                            ?>
                            <label class="admin-customer-row <?php echo $customerEditId === $rowId ? 'is-active' : ''; ?>">
                                <input form="customer-bulk-email-form" type="checkbox" name="customer_ids[]" value="<?php echo $rowId; ?>" class="customer-checkbox">
                                <span class="admin-customer-row-main">
                                    <strong><?php echo catalog_escape($displayName); ?></strong>
                                    <small><?php echo $rowType === 'professional' ? 'Professionnel' : 'Particulier'; ?></small>
                                    <small><?php echo catalog_escape((string) ($customer['email'] ?? '')); ?></small>
                                    <small><?php echo catalog_escape((string) ($customer['phone'] ?? '')); ?></small>
                                    <small><?php echo catalog_escape((string) ($customer['registration'] ?? '')); ?></small>
                                    <?php if (!empty($customer['postal_code']) || !empty($customer['city'])): ?>
                                        <small><?php echo catalog_escape(trim((string) (($customer['postal_code'] ?? '') . ' ' . ($customer['city'] ?? '')))); ?></small>
                                    <?php endif; ?>
                                    <?php if (trim((string) ($customer['firstname'] ?? '')) === '' || trim((string) ($customer['lastname'] ?? '')) === '' || trim((string) ($customer['email'] ?? '')) === '' || trim((string) ($customer['phone'] ?? '')) === ''): ?>
                                        <small style="color:#b91c1c;">Profil incomplet à compléter</small>
                                    <?php endif; ?>
                                </span>
                                <a class="line-action-link admin-customer-open-link" href="admin.php?customer_edit=<?php echo $rowId; ?>&customer_search=<?php echo urlencode($customerSearch); ?>&customer_registration=<?php echo urlencode($customerRegistrationSearch); ?>&customer_type_filter=<?php echo urlencode($customerTypeFilter); ?>&customer_sort=<?php echo urlencode($customerSort); ?>&customer_auto_refresh=<?php echo $customerAutoRefresh ? '1' : '0'; ?>&inventory_search=<?php echo urlencode($inventorySearch); ?>&vehicle_filter=<?php echo urlencode($vehicleFilter); ?>&part_filter=<?php echo urlencode($partFilter); ?>#customer-edit-focus">Ouvrir</a>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-customer-editor">
                        <form id="customer-bulk-email-form" method="post" class="admin-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                            <input type="hidden" name="action" value="customer_bulk_email">
                            <input type="hidden" name="customer_search" value="<?php echo catalog_escape($customerSearch); ?>">
                            <input type="hidden" name="customer_registration" value="<?php echo catalog_escape($customerRegistrationSearch); ?>">
                            <input type="hidden" name="customer_type_filter" value="<?php echo catalog_escape($customerTypeFilter); ?>">
                            <div class="admin-form-grid">
                                <label>Portée campagne
                                    <select name="campaign_scope">
                                        <option value="selected">Fiches cochées dans la liste</option>
                                        <option value="filtered">Toute la liste filtrée si aucune fiche cochée</option>
                                    </select>
                                </label>
                                <label style="grid-column: span 3;">Sujet email
                                    <input type="text" name="campaign_subject" value="<?php echo catalog_escape($customerCampaignSubject); ?>" placeholder="Ex: Informations atelier Clinik Auto" required>
                                </label>
                            </div>
                            <label>Message campagne
                                <textarea name="campaign_body" rows="5" placeholder="Ecrire le message a envoyer aux clients selectionnes." required><?php echo catalog_escape($customerCampaignBody); ?></textarea>
                            </label>
                            <div class="admin-form-actions">
                                <button type="submit">Envoyer campagne email</button>
                            </div>
                            <p class="admin-muted-line" style="margin-top:0.6rem;">Utilisez les cases à gauche pour cibler rapidement vos fiches sans dupliquer la liste.</p>
                        </form>

                        <form method="post" id="section-sms-quick" class="admin-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                            <input type="hidden" name="action" value="sms_quick_prepare">
                            <h4 style="margin:0 0 0.6rem 0;">SMS rapide (sans ouvrir une fiche)</h4>
                            <div class="admin-form-grid">
                                <label>Téléphone
                                    <input type="text" name="sms_quick_phone" value="<?php echo catalog_escape($smsQuickPhone); ?>" placeholder="06 00 00 00 00" required>
                                </label>
                                <label>Nom (si aucune fiche client trouvée)
                                    <input type="text" name="sms_quick_name" value="<?php echo catalog_escape($smsQuickName); ?>" placeholder="Nom du client" <?php echo ($smsQuickPhone !== '' && $smsQuickDetectedName === '') ? 'required' : ''; ?>>
                                </label>
                                <label>Modèle SMS
                                    <select name="sms_template_id" required>
                                        <?php foreach ($smsTemplates as $smsTemplate): ?>
                                            <?php $tplId = (string) ($smsTemplate['id'] ?? ''); ?>
                                            <option value="<?php echo catalog_escape($tplId); ?>" <?php echo $smsQuickTemplateId === $tplId ? 'selected' : ''; ?>>
                                                <?php echo catalog_escape((string) ($smsTemplate['label'] ?? 'Modele SMS')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="admin-form-actions">
                                <button type="submit">Préparer SMS rapide</button>
                            </div>
                            <?php if ($smsQuickDetectedName !== ''): ?>
                                <p class="admin-muted-line" style="margin-top:0.5rem;">Client détecté: <strong><?php echo catalog_escape($smsQuickDetectedName); ?></strong><?php if ($smsQuickMatchedCustomerId > 0): ?> (ID <?php echo (int) $smsQuickMatchedCustomerId; ?>)<?php endif; ?></p>
                            <?php elseif ($smsQuickPhone !== ''): ?>
                                <p class="admin-muted-line" style="margin-top:0.5rem; color:#b91c1c;">Aucune fiche client détectée: renseignez le champ Nom pour préparer le SMS.</p>
                            <?php endif; ?>

                            <?php if ($smsQuickPreparedMessage !== '' && $smsQuickPreparedHref !== ''): ?>
                                <label>Message prêt à envoyer
                                    <textarea rows="3" id="sms-quick-message"><?php echo catalog_escape($smsQuickPreparedMessage); ?></textarea>
                                </label>
                                <div class="admin-form-actions">
                                    <a id="sms-quick-open" class="btn-secondary" href="<?php echo catalog_escape($smsQuickPreparedHref); ?>">Préparer SMS (téléphone)</a>
                                    <button type="button" class="btn-secondary" data-sms-open-and-copy data-sms-copy-target="sms-quick-message" data-sms-open-target="sms-quick-open">1 clic PC (copier + ouvrir)</button>
                                    <button type="button" class="btn-secondary" data-sms-copy-target="sms-quick-message">Copier message SMS</button>
                                </div>
                            <?php endif; ?>
                        </form>

                        <form method="post" class="admin-form" style="margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                            <input type="hidden" name="action" value="sms_template_save">
                            <h4 style="margin:0 0 0.6rem 0;">Ajouter un modèle SMS</h4>
                            <div class="admin-form-grid">
                                <label>Nom du modèle
                                    <input type="text" name="sms_template_new_label" value="<?php echo catalog_escape($smsTemplateNewLabel); ?>" placeholder="Ex: Relance devis" required>
                                </label>
                                <label style="grid-column: span 2;">Texte du modèle (utilisez {NOM})
                                    <input type="text" name="sms_template_new_body" value="<?php echo catalog_escape($smsTemplateNewBody); ?>" placeholder="Bonjour {NOM}, ..." required>
                                </label>
                            </div>
                            <div class="admin-form-actions">
                                <button type="submit">Enregistrer le modèle</button>
                            </div>
                        </form>

                        <?php if ($customerEditItem): ?>
                            <div class="admin-form-actions" style="margin-bottom:0.7rem;">
                                <a class="btn-secondary" href="admin.php?customer_search=<?php echo urlencode($customerSearch); ?>&customer_registration=<?php echo urlencode($customerRegistrationSearch); ?>&customer_type_filter=<?php echo urlencode($customerTypeFilter); ?>&customer_sort=<?php echo urlencode($customerSort); ?>&customer_auto_refresh=<?php echo $customerAutoRefresh ? '1' : '0'; ?>&inventory_search=<?php echo urlencode($inventorySearch); ?>&vehicle_filter=<?php echo urlencode($vehicleFilter); ?>&part_filter=<?php echo urlencode($partFilter); ?>#section-customers">Fermer la fiche</a>
                            </div>
                            <?php
                                $smsDisplayName = admin_customer_display_name($customerEditItem);
                                $smsPhone = admin_sms_phone_link_target((string) ($customerEditItem['phone'] ?? ''));
                                $defaultTemplate = $smsTemplates[0] ?? ['id' => 'work_done', 'label' => 'Travaux termines', 'body' => 'Bonjour {NOM}, vos travaux sont termines. Votre vehicule est pret. Clinik Auto.'];
                                $smsMessage = admin_sms_render_message((string) ($defaultTemplate['body'] ?? ''), $smsDisplayName);
                                $smsHref = $smsPhone !== '' ? ('sms:' . $smsPhone . '?body=' . rawurlencode($smsMessage)) : '';
                            ?>
                            <form method="post" class="admin-form" id="customer-edit-focus">
                                <input type="hidden" name="action" value="customer_update">
                                <input type="hidden" name="customer_id" value="<?php echo (int) ($customerEditItem['id'] ?? 0); ?>">
                                <div data-customer-type-context>
                                <input type="hidden" name="customer_type" value="<?php echo catalog_escape((string) ($customerEditItem['customer_type'] ?? 'individual')); ?>" data-customer-type-input>
                                <label style="display:flex; align-items:center; gap:0.45rem; margin-bottom:0.6rem;">
                                    <input type="checkbox" value="1" data-customer-type-checkbox <?php echo (($customerEditItem['customer_type'] ?? 'individual') === 'professional') ? 'checked' : ''; ?>>
                                    Fiche professionnelle
                                </label>
                                <div class="admin-form-grid">
                                    <label><span data-type-label-target data-individual-label="Prénom" data-professional-label="Nom du contact">Prénom</span>
                                        <input type="text" name="firstname" value="<?php echo catalog_escape((string) ($customerEditItem['firstname'] ?? '')); ?>" data-type-placeholder-target data-individual-placeholder="Prénom" data-professional-placeholder="Nom et prénom du contact" required>
                                    </label>
                                    <label><span data-type-label-target data-individual-label="Nom" data-professional-label="Raison sociale">Nom</span>
                                        <input type="text" name="lastname" value="<?php echo catalog_escape((string) ($customerEditItem['lastname'] ?? '')); ?>" data-type-placeholder-target data-individual-placeholder="Nom" data-professional-placeholder="Raison sociale" required>
                                    </label>
                                    <label>Email
                                        <input type="email" name="email" value="<?php echo catalog_escape((string) ($customerEditItem['email'] ?? '')); ?>" required>
                                    </label>
                                    <label>Téléphone
                                        <input type="text" name="phone" value="<?php echo catalog_escape((string) ($customerEditItem['phone'] ?? '')); ?>">
                                    </label>
                                    <label>Immatriculation
                                        <input type="text" name="registration" value="<?php echo catalog_escape((string) ($customerEditItem['registration'] ?? '')); ?>">
                                    </label>
                                    <label>Adresse
                                        <input type="text" name="address_line" value="<?php echo catalog_escape((string) ($customerEditItem['address_line'] ?? '')); ?>">
                                    </label>
                                    <div data-postal-city-group data-postal-endpoint="postal_lookup.php" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.8rem; align-items:end;">
                                        <label>Code postal
                                            <input type="text" name="postal_code" maxlength="10" value="<?php echo catalog_escape((string) ($customerEditItem['postal_code'] ?? '')); ?>" data-postal-code-input>
                                        </label>
                                        <label>Ville
                                            <input type="text" name="city" list="admin-edit-customer-city-list" value="<?php echo catalog_escape((string) ($customerEditItem['city'] ?? '')); ?>" data-city-input>
                                        </label>
                                        <datalist id="admin-edit-customer-city-list"></datalist>
                                        <p class="admin-muted-line" data-postal-city-status style="margin:0;"></p>
                                    </div>
                                </div>
                                <div class="admin-form-actions">
                                    <button type="submit">Enregistrer la fiche client →</button>
                                </div>
                                </div>
                            </form>

                            <div class="admin-form" style="margin-top:0.6rem; border:1px solid #e5e7eb; border-radius:10px; padding:0.9rem;">
                                <h4 style="margin:0 0 0.6rem 0;">SMS depuis la fiche client</h4>
                                <div class="admin-form-grid">
                                    <label>Modèle SMS
                                        <select id="sms-customer-template" data-sms-template-select data-sms-message-target="sms-customer-message" data-sms-link-target="sms-customer-open" data-sms-name="<?php echo catalog_escape($smsDisplayName !== '' ? $smsDisplayName : 'client'); ?>">
                                            <?php foreach ($smsTemplates as $smsTemplate): ?>
                                                <option value="<?php echo catalog_escape((string) ($smsTemplate['id'] ?? '')); ?>" data-sms-body="<?php echo catalog_escape((string) ($smsTemplate['body'] ?? '')); ?>" <?php echo ((string) ($smsTemplate['id'] ?? '') === (string) ($defaultTemplate['id'] ?? '')) ? 'selected' : ''; ?>>
                                                    <?php echo catalog_escape((string) ($smsTemplate['label'] ?? 'Modele SMS')); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <?php if ($smsPhone !== ''): ?>
                                        <label>Numero
                                            <input type="text" value="<?php echo catalog_escape($smsPhone); ?>" readonly>
                                        </label>
                                    <?php endif; ?>
                                </div>
                                <label>Message prêt à envoyer
                                    <textarea id="sms-customer-message" rows="3"><?php echo catalog_escape($smsMessage); ?></textarea>
                                </label>
                                <div class="admin-form-actions" style="gap:0.6rem; flex-wrap:wrap;">
                                    <?php if ($smsHref !== ''): ?>
                                        <a id="sms-customer-open" class="btn-secondary" href="<?php echo catalog_escape($smsHref); ?>">Preparer SMS (telephone)</a>
                                        <button type="button" class="btn-secondary" data-sms-open-and-copy data-sms-copy-target="sms-customer-message" data-sms-open-target="sms-customer-open">1 clic PC (copier + ouvrir)</button>
                                    <?php else: ?>
                                        <button type="button" class="btn-secondary" disabled>Preparer SMS (numero manquant)</button>
                                    <?php endif; ?>
                                    <button type="button" class="btn-secondary" data-sms-copy-target="sms-customer-message">Copier message SMS</button>
                                </div>
                            </div>

                            <form method="post" onsubmit="return confirm('Supprimer cette fiche client ? Cette action est irreversible.');" style="margin-top:0.8rem;">
                                <input type="hidden" name="action" value="customer_delete">
                                <input type="hidden" name="customer_id" value="<?php echo (int) ($customerEditItem['id'] ?? 0); ?>">
                                <button type="submit" class="btn-secondary btn-danger">Supprimer cette fiche</button>
                            </form>

                            <div class="table-wrapper admin-rdv-table-wrapper" style="margin-top:1rem;">
                                <table class="admin-rdv-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <th>Service</th>
                                            <th>Statut RDV</th>
                                            <th>Rappel</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($customerAppointments)): ?>
                                            <tr>
                                                <td colspan="6">Aucun rendez-vous associe a cette fiche client.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($customerAppointments as $customerRdv): ?>
                                                                    <?php if (admin_rdv_is_completed($customerRdv)) { continue; } ?>
                                                                    <tr>
                                                                        <td><?php echo catalog_escape(admin_format_short_date((string) ($customerRdv['date'] ?? ''))); ?></td>
                                                    <td><?php echo catalog_escape((string) ($customerRdv['heure'] ?? 'A confirmer')); ?></td>
                                                    <td><?php echo catalog_escape((string) ($customerRdv['service'] ?? '')); ?></td>
                                                    <td><?php echo catalog_escape((string) ($customerRdv['status'] ?? 'En attente')); ?></td>
                                                    <td>
                                                        <?php echo catalog_escape(admin_rdv_reminder_label($customerRdv['reminder_status'] ?? 'pending')); ?>
                                                        <?php if (!empty($customerRdv['reminder_sent_at'])): ?>
                                                            <div class="admin-muted-line"><?php echo catalog_escape((string) $customerRdv['reminder_sent_at']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a class="line-action-link" href="admin.php?rdv_edit=<?php echo (int) ($customerRdv['id'] ?? 0); ?>&amp;target_date=<?php echo urlencode($targetDate); ?>&amp;target_month=<?php echo urlencode($targetMonth); ?>#section-reminders">Modifier</a>
                                                        <form method="post" style="display:inline; margin-left:0.45rem;">
                                                            <input type="hidden" name="action" value="rdv_remind_one">
                                                            <input type="hidden" name="rdv_id" value="<?php echo (int) ($customerRdv['id'] ?? 0); ?>">
                                                            <input type="hidden" name="target_date" value="<?php echo catalog_escape($targetDate); ?>">
                                                            <input type="hidden" name="target_month" value="<?php echo catalog_escape($targetMonth); ?>">
                                                            <button type="submit" class="line-action-link" style="border:0;background:none;padding:0;cursor:pointer;">Relancer</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Sélectionnez une fiche client dans la liste pour la modifier.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="admin-layout">
                <section id="section-create-ad" class="admin-editor-card" style="display:none;">
                    <div class="admin-card-head">
                        <div>
                            <h3><?php echo (int) ($editItem['id'] ?? 0) > 0 ? 'Modifier une annonce' : 'Créer une annonce'; ?></h3>
                            <p>Jusqu'à 8 photos par annonce, glissez-deposez les fichiers dans la zone prevue.</p>
                        </div>
                        <a class="cta-link cta-link-small" href="admin.php?type=<?php echo $editType === 'part' ? 'part' : 'vehicle'; ?>">Nouvelle annonce →</a>
                    </div>

                    <form method="post" data-admin-section="section-create-ad" enctype="multipart/form-data" class="admin-form" onsubmit="return confirm('Confirmer l\'enregistrement et la publication de cette annonce ?');">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="admin_section" value="section-create-ad">
                        <input type="hidden" name="id" value="<?php echo (int) ($editItem['id'] ?? 0); ?>">

                        <div class="admin-form-grid">
                            <label>Type d'annonce
                                <select name="type">
                                    <option value="vehicle" <?php echo ($editType === 'vehicle') ? 'selected' : ''; ?>>Véhicule d'occasion</option>
                                    <option value="part" <?php echo ($editType === 'part') ? 'selected' : ''; ?>>Pièce d'occasion</option>
                                </select>
                            </label>
                            <label>Titre
                                <input type="text" name="title" value="<?php echo catalog_escape($editItem['title']); ?>" placeholder="Titre de l'annonce" required>
                            </label>
                            <label>Sous-titre
                                <input type="text" name="subtitle" value="<?php echo catalog_escape($editItem['subtitle']); ?>" placeholder="Ex : 2020 - 68 500 km" required>
                            </label>
                            <label>Prix en €
                                <input type="number" min="0" step="0.01" name="price" value="<?php echo catalog_escape((string) $editItem['price']); ?>" placeholder="Prix affiché" required>
                            </label>
                            <label>Statut
                                <select name="status">
                                    <option value="available" <?php echo ($editItem['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Disponible</option>
                                    <option value="reserved" <?php echo ($editItem['status'] ?? '') === 'reserved' ? 'selected' : ''; ?>>Réservé / indisponible</option>
                                </select>
                            </label>
                            <?php if (!empty($editItem['transaction_in_progress'])): ?>
                            <div class="admin-warning-block" style="grid-column:1/-1;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;font-size:.9em;">
                                ⚠️ <strong>Transaction en cours</strong> — ce statut est géré automatiquement et ne peut pas être modifié via ce formulaire.
                                <form method="post" data-admin-section="section-vehicles" style="display:inline;margin-left:12px;" onsubmit="return confirm('Annuler la transaction et remettre ce véhicule disponible ?');">
                                    <input type="hidden" name="action" value="vehicle_release">
                                    <input type="hidden" name="admin_section" value="section-vehicles">
                                    <input type="hidden" name="id" value="<?php echo (int) $editItem['id']; ?>">
                                    <button type="submit" class="btn-secondary" style="padding:4px 10px;">Annuler la transaction</button>
                                </form>
                            </div>
                            <?php endif; ?>
                            <?php if (($editType ?? '') === 'part' && ((($editItem['status'] ?? '') === 'reserved') || !empty($editItem['payment_confirmed']) || !empty($editItem['current_part_request_id']))): ?>
                            <div class="admin-warning-block" style="grid-column:1/-1;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;font-size:.9em;">
                                ⚠️ <strong>Réservation pièce en cours</strong> — ce statut est piloté automatiquement par l'acompte et la file d'attente, il ne doit pas être corrigé à la main dans ce formulaire.
                                <form method="post" data-admin-section="section-parts" style="display:inline;margin-left:12px;" onsubmit="return confirm('Annuler la réservation et remettre cette pièce disponible ?');">
                                    <input type="hidden" name="action" value="part_cancel_sale">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $editItem['id']; ?>">
                                    <button type="submit" class="btn-secondary" style="padding:4px 10px;">Annuler la réservation</button>
                                </form>
                                <form method="post" data-admin-section="section-parts" style="display:inline;margin-left:8px;" onsubmit="return confirm('Forcer la suppression de cette annonce pièce malgré la réservation ? Cette action est irréversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $editItem['id']; ?>">
                                    <button type="submit" class="btn-secondary btn-danger" style="padding:4px 10px;">Forcer suppression</button>
                                </form>
                            </div>
                            <?php endif; ?>
                            <label class="checkbox-toggle inline-checkbox">
                                <input type="checkbox" name="payment_confirmed" value="1" <?php echo !empty($editItem['payment_confirmed']) ? 'checked' : ''; ?>>
                                Acompte confirmé / annonce grisée
                            </label>
                        </div>

                        <label>Résumé court
                            <textarea name="short_description" rows="3" placeholder="Texte visible dans la liste des annonces" required><?php echo catalog_escape($editItem['short_description']); ?></textarea>
                        </label>

                        <label>Description détaillée
                            <textarea name="description" rows="6" placeholder="Contenu complet de l'annonce" required><?php echo catalog_escape($editItem['description']); ?></textarea>
                        </label>

                        <div class="admin-specs-group" data-annonce-type="vehicle">
                            <h4>Renseignements véhicule</h4>
                            <div class="admin-form-grid admin-specs-grid">
                                <label>Marque
                                    <input type="text" name="vehicle_brand" value="<?php echo catalog_escape($vehicleBrand); ?>" placeholder="Ex : Peugeot" list="vehicle-brand-list">
                                </label>
                                <label>Modèle
                                    <input type="text" name="vehicle_model" value="<?php echo catalog_escape($vehicleModel); ?>" placeholder="Ex : 208 Allure">
                                </label>
                                <label>Année
                                    <input type="text" name="vehicle_year" value="<?php echo catalog_escape($vehicleYear); ?>" placeholder="Ex : 2020">
                                </label>
                                <label>Kilométrage (km)
                                    <input type="text" name="vehicle_km" value="<?php echo catalog_escape($vehicleKm); ?>" placeholder="Ex : 68500">
                                </label>
                                <label>Carburant
                                    <input type="text" name="vehicle_fuel" value="<?php echo catalog_escape($vehicleFuel); ?>" placeholder="Ex : Essence" list="vehicle-fuel-list">
                                </label>
                                <label>Boîte
                                    <input type="text" name="vehicle_gearbox" value="<?php echo catalog_escape($vehicleGearbox); ?>" placeholder="Ex : Manuelle" list="vehicle-gearbox-list">
                                </label>
                                <label>Couleur
                                    <input type="text" name="vehicle_color" value="<?php echo catalog_escape($vehicleColor); ?>" placeholder="Ex : Gris métallisé">
                                </label>
                            </div>
                        </div>

                        <div class="admin-specs-group" data-annonce-type="part">
                            <h4>Renseignements pièce</h4>
                            <div class="admin-form-grid admin-specs-grid">
                                <label>Famille
                                    <input type="text" name="part_family" value="<?php echo catalog_escape($partFamily); ?>" placeholder="Ex : Eclairage" list="part-family-list">
                                </label>
                                <label>Compatibilité
                                    <input type="text" name="part_compatibility" value="<?php echo catalog_escape($partCompatibility); ?>" placeholder="Ex : Peugeot 208 phase 2">
                                </label>
                                <label>Diamètre
                                    <input type="text" name="part_diameter" value="<?php echo catalog_escape($partDiameter); ?>" placeholder="Ex : 16 pouces">
                                </label>
                                <label>Entraxe
                                    <input type="text" name="part_spacing" value="<?php echo catalog_escape($partSpacing); ?>" placeholder="Ex : 4x100">
                                </label>
                                <label>État
                                    <input type="text" name="part_condition" value="<?php echo catalog_escape($partCondition); ?>" placeholder="Ex : Très bon état" list="part-condition-list">
                                </label>
                                <label>Référence
                                    <input type="text" name="part_reference" value="<?php echo catalog_escape($partReference); ?>" placeholder="Ex : 9812345677">
                                </label>
                                <label>Garantie
                                    <input type="text" name="part_warranty" value="<?php echo catalog_escape($partWarranty); ?>" placeholder="Ex : 3 mois">
                                </label>
                            </div>
                        </div>

                        <label>Autres renseignements (optionnel)
                            <textarea name="specs_extra" rows="4" placeholder="Une information par ligne, ex : Origine : France"><?php echo catalog_escape($specsExtra); ?></textarea>
                        </label>

                        <datalist id="vehicle-brand-list">
                            <?php foreach ($vehicleBrandSuggestions as $suggestion): ?>
                                <option value="<?php echo catalog_escape($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <datalist id="part-family-list">
                            <?php foreach ($partFamilySuggestions as $suggestion): ?>
                                <option value="<?php echo catalog_escape($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <datalist id="vehicle-fuel-list">
                            <?php foreach ($vehicleFuelSuggestions as $suggestion): ?>
                                <option value="<?php echo catalog_escape($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <datalist id="vehicle-gearbox-list">
                            <?php foreach ($vehicleGearboxSuggestions as $suggestion): ?>
                                <option value="<?php echo catalog_escape($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <datalist id="part-condition-list">
                            <?php foreach ($partConditionSuggestions as $suggestion): ?>
                                <option value="<?php echo catalog_escape($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <div class="dropzone" id="dropzone">
                            <input id="admin-images" type="file" name="images[]" accept="image/*" multiple>
                            <strong>Glissez-déposez vos photos ici</strong>
                            <span>ou cliquez pour sélectionner jusqu'à 8 images</span>
                            <small>JPG, PNG, WEBP, SVG ou GIF</small>
                        </div>
                        <div id="dropzone-files" class="dropzone-files"></div>

                        <?php if (!empty($editItem['images'])): ?>
                            <div class="existing-images-grid">
                                <?php foreach ($editItem['images'] as $image): ?>
                                    <label class="existing-image-card">
                                        <img src="<?php echo catalog_escape($image['data']); ?>" alt="Image annonce">
                                        <span><?php echo catalog_escape($image['name']); ?></span>
                                        <input type="checkbox" name="remove_images[]" value="<?php echo catalog_escape($image['id']); ?>">
                                        <em>Supprimer cette image</em>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="admin-form-actions">
                            <button type="submit">Enregistrer l'annonce →</button>
                        </div>
                    </form>
                </section>

                <section class="admin-list-card" id="section-inventory">
                    <div class="admin-card-head">
                        <div>
                            <h3 id="section-vehicles">Annonces véhicules</h3>
                            <p>Gestion complète avec filtres, recherche, et boutons d'actions par fonction.</p>
                        </div>
                        <a class="cta-link cta-link-small" href="catalogue/occasion.php">Voir la page →</a>
                    </div>
                    <form method="get" class="admin-inventory-toolbar">
                        <input type="text" name="inventory_search" value="<?php echo catalog_escape($inventorySearch); ?>" placeholder="Rechercher dans les annonces (titre, sous-titre, specs)">
                        <label>Véhicules
                            <select name="vehicle_filter">
                                <option value="all" <?php echo $vehicleFilter === 'all' ? 'selected' : ''; ?>>Tous</option>
                                <option value="available" <?php echo $vehicleFilter === 'available' ? 'selected' : ''; ?>>Disponibles</option>
                                <option value="reserved" <?php echo $vehicleFilter === 'reserved' ? 'selected' : ''; ?>>Réservés</option>
                                <option value="transaction" <?php echo $vehicleFilter === 'transaction' ? 'selected' : ''; ?>>En transaction</option>
                                <option value="only_vehicle" <?php echo $vehicleFilter === 'only_vehicle' ? 'selected' : ''; ?>>Que les véhicules</option>
                            </select>
                        </label>
                        <label>Pièces
                            <select name="part_filter">
                                <option value="all" <?php echo $partFilter === 'all' ? 'selected' : ''; ?>>Toutes</option>
                                <option value="available" <?php echo $partFilter === 'available' ? 'selected' : ''; ?>>Disponibles</option>
                                <option value="reserved" <?php echo $partFilter === 'reserved' ? 'selected' : ''; ?>>Réservées</option>
                                <option value="transaction" <?php echo $partFilter === 'transaction' ? 'selected' : ''; ?>>En transaction</option>
                                <option value="only_part" <?php echo $partFilter === 'only_part' ? 'selected' : ''; ?>>Que les pièces</option>
                            </select>
                        </label>
                        <input type="hidden" name="customer_search" value="<?php echo catalog_escape($customerSearch); ?>">
                        <input type="hidden" name="customer_registration" value="<?php echo catalog_escape($customerRegistrationSearch); ?>">
                        <button type="submit">Appliquer</button>
                        <a class="btn-secondary admin-inline-action" href="admin.php?vehicle_filter=all&part_filter=all#section-inventory">Réinitialiser</a>
                    </form>

                    <div class="admin-function-buttons">
                        <a class="cta-link cta-link-small" href="#section-create-ad">Créer une annonce</a>
                        <button type="button" class="btn-secondary admin-inline-action admin-section-toggle" data-target-section="section-reminders">Rappels RDV</button>
                        <button type="button" class="btn-secondary admin-inline-action admin-section-toggle" data-target-section="section-customers">Fiches clients</button>
                        <button type="button" class="btn-secondary admin-inline-action admin-section-toggle" data-target-section="section-vehicles">Bloc véhicules</button>
                        <button type="button" class="btn-secondary admin-inline-action admin-section-toggle" data-target-section="section-parts">Bloc pièces</button>
                    </div>
                    <?php if ($showVehiclesBlock): ?>
                    <div id="section-vehicles-block" class="admin-listing-table">
                        <?php if (empty($vehicles)): ?>
                            <p>Aucune annonce véhicule ne correspond aux filtres.</p>
                        <?php endif; ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <?php
                                $vehicleRequests = is_array($vehicle['vehicle_requests'] ?? null) ? $vehicle['vehicle_requests'] : [];
                                $vehicleActiveCount = 0;
                                $vehicleQueuedCount = 0;
                                $vehicleCurrentRequest = admin_vehicle_current_request($vehicle);
                                $vehicleCountdownLabel = admin_vehicle_transaction_countdown_label($vehicle);
                                foreach ($vehicleRequests as $request) {
                                    if (($request['request_status'] ?? 'queued') === 'active') {
                                        $vehicleActiveCount++;
                                    } elseif (($request['request_status'] ?? 'queued') === 'queued') {
                                        $vehicleQueuedCount++;
                                    }
                                }
                            ?>
                            <div class="admin-line-item <?php echo ($vehicle['status'] ?? '') === 'reserved' ? 'is-unavailable' : ''; ?>">
                                <img class="admin-line-thumb" src="<?php echo catalog_escape(catalog_primary_image($vehicle)); ?>" alt="<?php echo catalog_escape($vehicle['title']); ?>">
                                <div class="admin-line-content">
                                    <strong><?php echo catalog_escape($vehicle['title']); ?></strong>
                                    <small><?php echo catalog_escape($vehicle['subtitle']); ?></small>
                                    <small>Dossier actif : <?php echo $vehicleActiveCount; ?> | En attente : <?php echo $vehicleQueuedCount; ?></small>
                                    <?php if ($vehicleCurrentRequest): ?>
                                        <small>Client prioritaire : <?php echo catalog_escape(trim((string) (($vehicleCurrentRequest['firstname'] ?? '') . ' ' . ($vehicleCurrentRequest['lastname'] ?? '')))); ?><?php echo !empty($vehicleCurrentRequest['email']) ? ' | ' . catalog_escape((string) $vehicleCurrentRequest['email']) : ''; ?></small>
                                    <?php endif; ?>
                                    <?php if ($vehicleCountdownLabel !== ''): ?>
                                        <small>Delai restant : <?php echo catalog_escape($vehicleCountdownLabel); ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($vehicle['price'])); ?> €</span>
                                <span class="status-pill <?php echo ($vehicle['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($vehicle)); ?></span>
                                <a class="line-action-link" href="admin.php?edit=<?php echo (int) $vehicle['id']; ?>">Modifier</a>
                                <?php if (!empty($vehicle['transaction_in_progress'])): ?>
                                <form method="post" data-admin-section="section-vehicles" onsubmit="return confirm('Confirmer la vente et supprimer cette annonce véhicule ?');">
                                    <input type="hidden" name="action" value="vehicle_sold">
                                    <input type="hidden" name="admin_section" value="section-vehicles">
                                    <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Vente conclue</button>
                                </form>
                                <form method="post" data-admin-section="section-vehicles" onsubmit="return confirm('Marquer la transaction comme non conclue et remettre le véhicule disponible ?');">
                                    <input type="hidden" name="action" value="vehicle_release">
                                    <input type="hidden" name="admin_section" value="section-vehicles">
                                    <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                    <button class="btn-secondary" type="submit">Non conclue</button>
                                </form>
                                <form method="post" data-admin-section="section-vehicles" onsubmit="return confirm('Forcer la suppression de cette annonce (transaction en cours ignorée) ? Cette action est irréversible.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_section" value="section-vehicles">
                                    <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Forcer suppression</button>
                                </form>
                                <?php else: ?>
                                <form method="post" data-admin-section="section-vehicles" onsubmit="return confirm('Supprimer définitivement cette annonce et toutes ses images ? Cette action est irréversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_section" value="section-vehicles">
                                    <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Supprimer</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($showPartsBlock): ?>
                    <div id="section-parts-block">
                    <div class="admin-card-head spaced-section-small">
                        <div>
                            <h3 id="section-parts">Annonces pièces</h3>
                            <p>Les pièces réservées avec acompte validé sont automatiquement grisées.</p>
                        </div>
                        <a class="cta-link cta-link-small" href="catalogue/pieces.php">Voir la page →</a>
                    </div>
                    <div class="admin-listing-table">
                        <?php if (empty($parts)): ?>
                            <p>Aucune annonce pièce ne correspond aux filtres.</p>
                        <?php endif; ?>
                        <?php foreach ($parts as $part): ?>
                            <?php
                                $partRequests = is_array($part['part_requests'] ?? null) ? $part['part_requests'] : [];
                                $partActiveCount = 0;
                                $partQueuedCount = 0;
                                $partCurrentRequest = admin_part_current_request($part);
                                $partTransferStatusLabel = admin_part_transfer_status_label($partCurrentRequest);
                                $partTransferCountdown = admin_part_transfer_countdown_label($partCurrentRequest);
                                foreach ($partRequests as $request) {
                                    if (($request['request_status'] ?? 'queued') === 'active') {
                                        $partActiveCount++;
                                    } elseif (($request['request_status'] ?? 'queued') === 'queued') {
                                        $partQueuedCount++;
                                    }
                                }
                            ?>
                            <div class="admin-line-item <?php echo ($part['status'] ?? '') === 'reserved' ? 'is-unavailable' : ''; ?>">
                                <img class="admin-line-thumb" src="<?php echo catalog_escape(catalog_primary_image($part)); ?>" alt="<?php echo catalog_escape($part['title']); ?>">
                                <div class="admin-line-content">
                                    <strong><?php echo catalog_escape($part['title']); ?></strong>
                                    <small><?php echo catalog_escape($part['subtitle']); ?></small>
                                    <small>Dossier actif : <?php echo $partActiveCount; ?> | En attente : <?php echo $partQueuedCount; ?></small>
                                    <?php if (is_array($partCurrentRequest)): ?>
                                        <small>Client actif : <?php echo catalog_escape(trim((string) (($partCurrentRequest['firstname'] ?? '') . ' ' . ($partCurrentRequest['lastname'] ?? '')))); ?> - <?php echo catalog_escape((string) ($partCurrentRequest['email'] ?? '')); ?></small>
                                        <small>Etat virement : <?php echo catalog_escape($partTransferStatusLabel); ?><?php echo $partTransferCountdown !== '' ? ' - Temps restant : ' . catalog_escape($partTransferCountdown) : ''; ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($part['price'])); ?> €</span>
                                <span class="status-pill <?php echo ($part['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($part)); ?></span>
                                <a class="line-action-link" href="admin.php?edit=<?php echo (int) $part['id']; ?>">Modifier</a>
                                <?php if (is_array($partCurrentRequest) && (($partCurrentRequest['transfer_verification_status'] ?? 'none') === 'pending') && empty($part['payment_confirmed'])): ?>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Valider la reception du virement et reserver cette piece ?');">
                                    <input type="hidden" name="action" value="part_validate_transfer">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary" type="submit">Virement recu</button>
                                </form>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Confirmer que le virement n\'a pas ete recu et passer au dossier suivant ?');">
                                    <input type="hidden" name="action" value="part_reject_transfer">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Virement non recu</button>
                                </form>
                                <?php elseif (($part['status'] ?? '') === 'reserved'): ?>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Confirmer la vente et supprimer cette annonce pièce ?');">
                                    <input type="hidden" name="action" value="part_confirm_sale">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Vente confirmée</button>
                                </form>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Annuler la vente et repasser la pièce en disponible ?');">
                                    <input type="hidden" name="action" value="part_cancel_sale">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary" type="submit">Non confirmée</button>
                                </form>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Forcer la suppression de cette annonce pièce (réservation ignorée) ? Cette action est irréversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Forcer suppression</button>
                                </form>
                                <?php else: ?>
                                <form method="post" data-admin-section="section-parts" onsubmit="return confirm('Supprimer cette annonce et toutes ses images ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_section" value="section-parts">
                                    <input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>">
                                    <button class="btn-secondary btn-danger" type="submit">Supprimer</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

                </div>
            </div>

            <button type="button" class="admin-back-top-floating" id="admin-back-top" aria-label="Retour en haut de la page">↑</button>

            <script>
            (function () {
                const dropzone = document.getElementById('dropzone');
                const input = document.getElementById('admin-images');
                const list = document.getElementById('dropzone-files');
                const typeSelect = document.querySelector('select[name="type"]');
                const specGroups = document.querySelectorAll('[data-annonce-type]');
                const quickTopButton = document.querySelector('.admin-quick-top');
                const backTopFloating = document.getElementById('admin-back-top');
                const sectionToggleButtons = document.querySelectorAll('.admin-section-toggle');
                const sectionJumpLinks = document.querySelectorAll('.admin-section-jump');
                const customerSelectAll = document.getElementById('customer-select-all');
                const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
                const customerSearchForm = document.getElementById('customer-search-form');
                const customerAutoRefreshToggle = document.getElementById('customer-auto-refresh-toggle');
                const customerAutoRefreshStatus = document.getElementById('customer-auto-refresh-status');
                const selectedFiles = [];
                const allowedSections = ['section-security', 'section-kpi', 'section-analytics', 'section-banks', 'section-reminders', 'section-customers', 'section-create-ad', 'section-vehicles', 'section-parts', 'section-inventory', 'section-sms-quick'];
                const allAdminPostForms = document.querySelectorAll('form[method="post"]');
                let customerAutoRefreshEnabled = <?php echo $customerAutoRefresh ? 'true' : 'false'; ?>;
                const sectionAliases = {
                    'section-vehicles': 'section-vehicles-block',
                    'section-parts': 'section-parts-block'
                };
                const sectionParents = {
                    'section-vehicles': 'section-inventory',
                    'section-parts': 'section-inventory',
                    'section-sms-quick': 'section-customers'
                };

                if (!dropzone || !input || !list) {
                    return;
                }

                function resolveCurrentAdminSection() {
                    const hashSection = (window.location.hash || '').replace('#', '');
                    if (allowedSections.indexOf(hashSection) !== -1) {
                        return hashSection;
                    }
                    return 'section-inventory';
                }

                function resolveSectionElement(sectionId) {
                    const elementId = sectionAliases[sectionId] || sectionId;
                    return document.getElementById(elementId);
                }

                function isSectionVisible(sectionId) {
                    const element = resolveSectionElement(sectionId);
                    if (!element) {
                        return false;
                    }
                    return element.style.display !== 'none';
                }

                function setSectionVisibility(sectionId, visible) {
                    const element = resolveSectionElement(sectionId);
                    if (!element) {
                        return;
                    }
                    element.style.display = visible ? '' : 'none';
                    const parentId = sectionParents[sectionId] || '';
                    if (visible && parentId !== '') {
                        const parentElement = resolveSectionElement(parentId);
                        if (parentElement) {
                            parentElement.style.display = '';
                        }
                    }
                }

                function loadVisibilityState() {
                    try {
                        const raw = window.sessionStorage.getItem('admin-visible-sections') || '';
                        if (raw === '') {
                            return {};
                        }
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (error) {
                        return {};
                    }
                }

                function saveVisibilityState() {
                    const state = {};
                    sectionToggleButtons.forEach(function (button) {
                        const target = button.getAttribute('data-target-section') || '';
                        if (target !== '') {
                            state[target] = isSectionVisible(target);
                        }
                    });
                    window.sessionStorage.setItem('admin-visible-sections', JSON.stringify(state));
                }

                function updateToggleLabels() {
                    sectionToggleButtons.forEach(function (button) {
                        const target = button.getAttribute('data-target-section') || '';
                        const section = resolveSectionElement(target);
                        if (!section) {
                            return;
                        }
                        const visible = section.style.display !== 'none';
                        const base = (button.textContent || '').replace(/^Masquer\s+/i, '').replace(/^Afficher\s+/i, '').trim() || 'Bloc';
                        button.textContent = (visible ? 'Masquer ' : 'Afficher ') + base;
                        button.classList.toggle('is-visible', visible);
                        button.classList.toggle('is-hidden', !visible);
                    });
                }

                function ensureSectionField(form) {
                    const explicit = form.getAttribute('data-admin-section');
                    const section = allowedSections.indexOf(explicit) !== -1 ? explicit : resolveCurrentAdminSection();
                    let field = form.querySelector('input[name="admin_section"]');
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'admin_section';
                        form.appendChild(field);
                    }
                    field.value = section;
                }

                allAdminPostForms.forEach(function (form) {
                    ensureSectionField(form);
                    form.addEventListener('submit', function () {
                        ensureSectionField(form);
                    });
                });

                function syncSpecGroups() {
                    if (!typeSelect || !specGroups.length) {
                        return;
                    }

                    const selectedType = typeSelect.value === 'part' ? 'part' : 'vehicle';
                    specGroups.forEach(function (group) {
                        const groupType = group.getAttribute('data-annonce-type');
                        const active = groupType === selectedType;
                        group.style.display = active ? '' : 'none';
                        group.querySelectorAll('input, textarea, select').forEach(function (field) {
                            field.disabled = !active;
                        });
                    });
                }

                if (typeSelect) {
                    typeSelect.addEventListener('change', syncSpecGroups);
                    syncSpecGroups();
                }

                function goToTop() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                if (quickTopButton) {
                    quickTopButton.addEventListener('click', goToTop);
                }

                if (backTopFloating) {
                    backTopFloating.addEventListener('click', goToTop);
                    const syncBackTopVisibility = function () {
                        if (window.scrollY > 420) {
                            backTopFloating.classList.add('is-visible');
                        } else {
                            backTopFloating.classList.remove('is-visible');
                        }
                    };

                    window.addEventListener('scroll', syncBackTopVisibility, { passive: true });
                    syncBackTopVisibility();
                }

                sectionToggleButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const target = button.getAttribute('data-target-section') || '';
                        if (allowedSections.indexOf(target) === -1) {
                            return;
                        }
                        const nextVisible = !isSectionVisible(target);
                        setSectionVisibility(target, nextVisible);
                        updateToggleLabels();
                        saveVisibilityState();
                        if (nextVisible) {
                            window.history.replaceState(null, '', '#' + target);
                            const scrollTarget = resolveSectionElement(target) || document.getElementById(target);
                            if (scrollTarget) {
                                scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }
                    });
                });

                sectionJumpLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        const target = link.getAttribute('data-target-section') || '';
                        if (allowedSections.indexOf(target) === -1) {
                            return;
                        }

                        setSectionVisibility(target, true);
                        updateToggleLabels();
                        saveVisibilityState();
                        window.history.replaceState(null, '', '#' + target);

                        const scrollTarget = resolveSectionElement(target) || document.getElementById(target);
                        if (scrollTarget) {
                            scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                const savedVisibility = loadVisibilityState();
                Object.keys(savedVisibility).forEach(function (sectionId) {
                    if (allowedSections.indexOf(sectionId) === -1) {
                        return;
                    }
                    setSectionVisibility(sectionId, !!savedVisibility[sectionId]);
                });

                const hashSection = resolveCurrentAdminSection();
                if ((window.location.hash || '') !== '' && allowedSections.indexOf(hashSection) !== -1) {
                    setSectionVisibility(hashSection, true);
                }

                updateToggleLabels();
                saveVisibilityState();

                function renderFiles(files) {
                    list.innerHTML = '';
                    Array.from(files).forEach(function (file) {
                        const item = document.createElement('span');
                        item.className = 'file-chip';
                        item.textContent = file.name;
                        list.appendChild(item);
                    });
                }

                function syncInputFromSelectedFiles() {
                    const transfer = new DataTransfer();
                    selectedFiles.forEach(function (file) {
                        transfer.items.add(file);
                    });
                    input.files = transfer.files;
                    renderFiles(input.files);
                }

                function addFilesToSelection(fileList) {
                    if (!fileList || !fileList.length) {
                        return;
                    }

                    Array.from(fileList).forEach(function (file) {
                        const duplicate = selectedFiles.some(function (existing) {
                            return existing.name === file.name
                                && existing.size === file.size
                                && existing.lastModified === file.lastModified;
                        });
                        if (!duplicate) {
                            selectedFiles.push(file);
                        }
                    });

                    if (selectedFiles.length > 8) {
                        selectedFiles.splice(8);
                        alert('Maximum 8 images par annonce.');
                    }

                    syncInputFromSelectedFiles();
                }

                input.addEventListener('change', function () {
                    addFilesToSelection(input.files);
                });

                dropzone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });

                ['dragleave', 'dragend'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function () {
                        dropzone.classList.remove('is-dragover');
                    });
                });

                dropzone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                    if (event.dataTransfer && event.dataTransfer.files) {
                        addFilesToSelection(event.dataTransfer.files);
                    }
                });

                dropzone.addEventListener('click', function () {
                    input.click();
                });

                if (customerSelectAll && customerCheckboxes.length) {
                    customerSelectAll.addEventListener('change', function () {
                        customerCheckboxes.forEach(function (checkbox) {
                            checkbox.checked = customerSelectAll.checked;
                        });
                    });

                    customerCheckboxes.forEach(function (checkbox) {
                        checkbox.addEventListener('change', function () {
                            const checkedCount = Array.from(customerCheckboxes).filter(function (item) {
                                return item.checked;
                            }).length;
                            customerSelectAll.checked = checkedCount === customerCheckboxes.length;
                        });
                    });
                }

                if (customerSearchForm && customerAutoRefreshToggle) {
                    customerAutoRefreshToggle.addEventListener('change', function () {
                        customerAutoRefreshEnabled = customerAutoRefreshToggle.checked;
                        if (customerAutoRefreshStatus) {
                            customerAutoRefreshStatus.textContent = customerAutoRefreshEnabled ? 'ON' : 'OFF';
                            customerAutoRefreshStatus.style.color = customerAutoRefreshEnabled ? '#15803d' : '#b91c1c';
                        }
                        saveVisibilityState();
                        customerSearchForm.submit();
                    });
                }

                const copySmsText = function (message) {
                    if (!message) {
                        return Promise.reject(new Error('empty_message'));
                    }
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(message);
                    }
                    return Promise.reject(new Error('clipboard_unavailable'));
                };

                const smsCopyButtons = document.querySelectorAll('[data-sms-copy-target]');
                smsCopyButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const targetId = button.getAttribute('data-sms-copy-target') || '';
                        if (!targetId) {
                            return;
                        }
                        const source = document.getElementById(targetId);
                        if (!source) {
                            return;
                        }
                        const message = (source.value || source.textContent || '').trim();
                        if (!message) {
                            return;
                        }
                        copySmsText(message).then(function () {
                            alert('Message SMS copie dans le presse-papiers.');
                        }).catch(function () {
                            alert('Copie automatique non disponible sur ce navigateur.');
                        });
                    });
                });

                const smsOpenAndCopyButtons = document.querySelectorAll('[data-sms-open-and-copy]');
                smsOpenAndCopyButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const targetId = button.getAttribute('data-sms-copy-target') || '';
                        const openTargetId = button.getAttribute('data-sms-open-target') || '';
                        const source = targetId ? document.getElementById(targetId) : null;
                        const openLink = openTargetId ? document.getElementById(openTargetId) : null;
                        if (!source || !openLink) {
                            return;
                        }

                        const message = (source.value || source.textContent || '').trim();
                        const href = openLink.getAttribute('href') || '';
                        if (!message || !href) {
                            return;
                        }

                        const openSms = function () {
                            window.location.href = href;
                        };

                        copySmsText(message).then(function () {
                            openSms();
                        }).catch(function () {
                            openSms();
                            alert('Lien SMS ouvert. Si le message n\'est pas pre-rempli, utilisez aussi le bouton Copier message SMS.');
                        });
                    });
                });

                const smsTemplateSelects = document.querySelectorAll('[data-sms-template-select]');
                smsTemplateSelects.forEach(function (select) {
                    const messageTargetId = select.getAttribute('data-sms-message-target') || '';
                    const linkTargetId = select.getAttribute('data-sms-link-target') || '';
                    const customerName = (select.getAttribute('data-sms-name') || 'client').trim() || 'client';
                    const messageTarget = messageTargetId ? document.getElementById(messageTargetId) : null;
                    const linkTarget = linkTargetId ? document.getElementById(linkTargetId) : null;

                    const applyTemplate = function () {
                        const selectedOption = select.options[select.selectedIndex];
                        if (!selectedOption || !messageTarget) {
                            return;
                        }
                        const bodyTemplate = selectedOption.getAttribute('data-sms-body') || '';
                        const message = bodyTemplate.replace(/\{NOM\}/g, customerName);
                        messageTarget.value = message;
                        if (linkTarget && linkTarget.getAttribute('href')) {
                            const href = linkTarget.getAttribute('href');
                            const base = href.split('?body=')[0];
                            linkTarget.setAttribute('href', base + '?body=' + encodeURIComponent(message));
                        }
                    };

                    select.addEventListener('change', applyTemplate);
                    applyTemplate();
                });

                if (customerAutoRefreshEnabled) {
                    window.setInterval(function () {
                        const remindersVisible = isSectionVisible('section-reminders');
                        const customersVisible = isSectionVisible('section-customers');
                        if (!remindersVisible && !customersVisible) {
                            return;
                        }
                        const active = document.activeElement;
                        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) {
                            return;
                        }
                        saveVisibilityState();
                        window.location.reload();
                    }, 300000);
                }
            })();
            </script>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2026 Clinik Auto. Tous droits réservés.</p>
    </footer>
</body>
</html>