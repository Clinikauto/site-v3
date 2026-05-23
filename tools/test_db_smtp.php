<?php
// Test DB & SMTP pour Clinik Auto (script temporaire)
$mysqli_report_old = null;
// Désactiver les exceptions mysqli pour récupérer proprement les erreurs
if (function_exists('mysqli_report')) {
    $mysqli_report_old = mysqli_report(MYSQLI_REPORT_OFF);
}
require_once __DIR__ . '/../config.php';
$output = [];

// --- Test DB ---
// Tentative de connexion (non-fatal)
$mysqli = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$mysqli) {
    $output['db'] = ['ok' => false, 'error' => mysqli_connect_error()];
} else {
    $output['db'] = ['ok' => true, 'host' => DB_HOST, 'port' => DB_PORT, 'user' => DB_USER, 'db' => DB_NAME];
    $tbl = 'tmp_ai_test_' . bin2hex(random_bytes(4));
    $created = $mysqli->query("CREATE TABLE `{$tbl}` (id INT PRIMARY KEY AUTO_INCREMENT, msg VARCHAR(255)) ENGINE=InnoDB CHARSET=utf8mb4");
    if (!$created) {
        $output['db']['create_error'] = $mysqli->error;
    } else {
        $ins = $mysqli->query("INSERT INTO `{$tbl}` (msg) VALUES ('hello')");
        if (!$ins) {
            $output['db']['insert_error'] = $mysqli->error;
        } else {
            $res = $mysqli->query("SELECT msg FROM `{$tbl}` LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $output['db']['row'] = $row;
            } else {
                $output['db']['select_error'] = $mysqli->error;
            }
        }
        $drop = $mysqli->query("DROP TABLE `{$tbl}`");
        if (!$drop) {
            $output['db']['drop_error'] = $mysqli->error;
        }
    }
    $mysqli->close();
}

// --- Test SMTP ---
$output['smtp'] = ['enabled' => SMTP_ENABLED === true];
if (defined('DRY_RUN_MODE') && DRY_RUN_MODE === true) {
    $output['smtp']['dry_run'] = true;
    $output['smtp']['message'] = 'DRY_RUN_MODE activé — envoi réel ignoré.';
} else {
    if (!SMTP_ENABLED) {
        $output['smtp']['ok'] = false;
        $output['smtp']['error'] = 'SMTP_DISABLED';
    } else {
        // Tentative d'envoi via PHPMailer (si présent)
        $ok = false;
        $err = '';
        if (is_readable(COMPOSER_AUTOLOAD_PATH)) {
            require_once COMPOSER_AUTOLOAD_PATH;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                if (in_array(strtolower(SMTP_SECURE), ['ssl','tls'], true)) {
                    if (strtolower(SMTP_SECURE) === 'ssl') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                }
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->setFrom(EMAIL_EXPEDITEUR, GARAGE_NOM);
                $dest = ADMIN_PASSWORD_RESET_EMAIL ?: EMAIL_EXPEDITEUR;
                $mail->addAddress($dest);
                $mail->Subject = 'Test SMTP Clinik Auto';
                $mail->Body = 'Test SMTP généré depuis l’environnement local.';
                $mail->send();
                $ok = true;
            } catch (Exception $e) {
                $err = $e->getMessage();
            }
        } else {
            $err = 'Composer autoload not found: ' . COMPOSER_AUTOLOAD_PATH;
        }
        $output['smtp']['ok'] = $ok;
        if (!$ok) $output['smtp']['error'] = $err;
    }
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
