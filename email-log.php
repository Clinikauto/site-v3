<?php
/**
 * Email Log Viewer - Affiche tous les emails envoyés en mode développement local
 * Utilité: Tester les emails sans avoir besoin de Gmail/SMTP en local
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$emailLogDir = dirname(__DIR__) . '/email-logs';
if (!is_dir($emailLogDir)) {
    mkdir($emailLogDir, 0777, true);
}

// Lire les emails envoyés
$emailFiles = glob($emailLogDir . '/*.json');
rsort($emailFiles); // Trier par date décroissante (plus récent d'abord)

$emails = [];
foreach ($emailFiles as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        $email = json_decode($content, true);
        if ($email) {
            $emails[] = $email;
        }
    }
}

// Supprimer un email si demandé
if ($_POST['action'] ?? '' === 'delete' && isset($_POST['email_id'])) {
    $emailId = basename($_POST['email_id']);
    if (preg_match('/^[0-9a-f-]+\.json$/', $emailId)) {
        $filePath = $emailLogDir . '/' . $emailId;
        if (is_file($filePath)) {
            unlink($filePath);
            header('Location: email-log.php');
            exit;
        }
    }
}

// Vider tous les emails si demandé
if ($_POST['action'] ?? '' === 'clear_all') {
    foreach ($emailFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    header('Location: email-log.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📧 Email Log - Clinik Auto Dev</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #f56565;
            color: white;
        }
        .btn-danger:hover {
            background: #e53e3e;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-card .label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }
        .emails-list {
            display: grid;
            gap: 15px;
        }
        .email-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.2s;
        }
        .email-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .email-header {
            padding: 16px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .email-from {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .email-timestamp {
            font-size: 12px;
            color: #999;
        }
        .email-subject {
            padding: 16px;
            background: white;
        }
        .email-subject strong {
            color: #667eea;
        }
        .email-body {
            padding: 16px;
            background: #fafafa;
            border-top: 1px solid #eee;
            font-size: 13px;
            line-height: 1.5;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .email-actions {
            padding: 12px 16px;
            background: #f9f9f9;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-small:hover {
            background: #f5f5f5;
        }
        .btn-small-delete {
            color: #f56565;
            border-color: #f56565;
        }
        .btn-small-delete:hover {
            background: #fff5f5;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .recipient-badge {
            display: inline-block;
            background: #e6f3ff;
            color: #0066cc;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>📧 Email Log - Clinik Auto Dev</h1>
        <p>Emails capturés en local (fichiers stockés dans /email-logs/)</p>
    </header>

    <div class="stats">
        <div class="stat-card">
            <div class="number"><?php echo count($emails); ?></div>
            <div class="label">Emails reçus</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php
                $types = array_count_values(array_map(function($e) { return $e['type'] ?? 'other'; }, $emails));
                echo count($types);
            ?></div>
            <div class="label">Types d'emails</div>
        </div>
    </div>

    <div class="controls">
        <?php if (count($emails) > 0): ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer tous les emails ?');">🗑️ Vider le log</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="emails-list">
        <?php if (count($emails) === 0): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="m22 6-10 5L2 6"></path>
                </svg>
                <p><strong>Aucun email capturé</strong></p>
                <p style="font-size: 13px; margin-top: 8px;">Remplissez un formulaire sur le site pour voir les emails ici !</p>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <div class="email-card">
                    <div class="email-header">
                        <div>
                            <div class="email-from">
                                📬 De: <?php echo htmlspecialchars($email['from'] ?? 'unknown'); ?>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                Vers:
                                <?php foreach ((array)($email['to'] ?? []) as $recipient): ?>
                                    <span class="recipient-badge"><?php echo htmlspecialchars($recipient); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="email-timestamp">
                            <?php
                            $timestamp = $email['timestamp'] ?? 0;
                            echo $timestamp ? date('d/m/Y H:i:s', $timestamp) : 'N/A';
                            ?>
                        </div>
                    </div>
                    <div class="email-subject">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject'] ?? '(no subject)'); ?>
                    </div>
                    <?php if (!empty($email['body'])): ?>
                        <div class="email-body"><?php echo htmlspecialchars($email['body']); ?></div>
                    <?php endif; ?>
                    <div class="email-actions">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="email_id" value="<?php echo htmlspecialchars($email['id'] ?? ''); ?>">
                            <button type="submit" class="btn-small btn-small-delete">Supprimer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
