<?php
require_once dirname(__DIR__) . '/includes/catalog_store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$showAdminReturn = catalog_is_admin_session_active();

catalog_track_visit('catalogue_part');

$items = catalog_all_items('part');
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selected = $selectedId > 0 ? catalog_find_item($selectedId, 'part') : null;
$selectedBankAccount = catalog_bank_account_selected();

$deposit = $selected ? number_format(catalog_reservation_amount($selected['price']), 2, ',', ' ') : '0,00';
$selectedDepositRaw = $selected ? (float) catalog_reservation_amount($selected['price']) : 0.0;
$selectedCurrentRequest = $selected ? catalog_part_current_request($selected) : null;
$selectedTransferSeconds = $selectedCurrentRequest ? catalog_part_request_remaining_seconds($selectedCurrentRequest) : null;
$selectedTransferCountdown = '';
if ($selectedTransferSeconds !== null) {
    $selectedTransferCountdown = sprintf('%02dh %02dmin', (int) floor($selectedTransferSeconds / 3600), (int) floor(($selectedTransferSeconds % 3600) / 60));
}

$partListItems = [];
foreach (array_slice($items, 0, 12) as $index => $item) {
    $partListItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => 'https://www.clinikauto.fr/catalogue/pieces.php?id=' . (int) ($item['id'] ?? 0),
        'item' => [
            '@type' => 'Product',
            'name' => (string) ($item['title'] ?? ''),
            'description' => (string) (($item['subtitle'] ?? '') ?: ($item['description'] ?? '')),
            'image' => catalog_primary_image($item),
            'itemCondition' => 'https://schema.org/UsedCondition',
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => (string) ((float) ($item['price'] ?? 0)),
                'availability' => (($item['status'] ?? '') === 'reserved')
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock',
                'url' => 'https://www.clinikauto.fr/catalogue/pieces.php?id=' . (int) ($item['id'] ?? 0)
            ]
        ]
    ];
}

$piecesStructuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => 'https://www.clinikauto.fr/catalogue/pieces.php#webpage',
            'url' => 'https://www.clinikauto.fr/catalogue/pieces.php',
            'name' => 'Pièces Auto d\'Occasion à Scionzier | Clinik Auto',
            'description' => 'Pièces détachées automobiles d\'occasion contrôlées chez Clinik Auto à Scionzier.',
            'about' => [
                '@id' => 'https://www.clinikauto.fr/#garage'
            ]
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://www.clinikauto.fr/catalogue/pieces.php#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => 'https://www.clinikauto.fr/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Catalogue', 'item' => 'https://www.clinikauto.fr/catalogue/catalogue.php'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Pièces d\'occasion', 'item' => 'https://www.clinikauto.fr/catalogue/pieces.php']
            ]
        ],
        [
            '@type' => 'ItemList',
            'name' => 'Pièces auto d\'occasion disponibles',
            'numberOfItems' => count($partListItems),
            'itemListElement' => $partListItems
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pièces Auto Occasion Scionzier (74950) | Jantes, Freinage, Moteur | Clinik Auto</title>
    <meta name="description" content="Pièces détachées auto d'occasion contrôlées à Scionzier (74950) : jantes, freinage, moteur, carrosserie. Livraison ou retrait garage – proche Cluses, Bonneville, Sallanches. Tél : 06 20 18 56 27.">
    <meta name="keywords" content="pièces auto occasion Scionzier, pièces détachées 74, jantes occasion Cluses, freinage auto 74950, pièces voiture Haute-Savoie, carrosserie occasion 74, moteur occasion Bonneville">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <link rel="canonical" href="https://www.clinikauto.fr/catalogue/pieces.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pièces Auto d'Occasion à Scionzier (74) | Clinik Auto">
    <meta property="og:description" content="Pièces détachées automobiles d'occasion contrôlées chez Clinik Auto à Scionzier (74950). Jantes, freinage, moteur, carrosserie en Haute-Savoie.">
    <meta property="og:url" content="https://www.clinikauto.fr/catalogue/pieces.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Pièces Auto d'Occasion à Scionzier (74) | Clinik Auto">
    <meta name="twitter:description" content="Pièces détachées auto d'occasion contrôlées chez Clinik Auto, Scionzier (74950).">
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <?php echo catalog_get_google_analytics_script(); ?>
    <script type="application/ld+json">
        <?php echo json_encode($piecesStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <?php if (function_exists('csrf_print_meta_and_js')) { csrf_print_meta_and_js(); } ?>
</head>
<body class="public-page">
    <header>
        <div class="site-brand">
            <a class="site-brand-link" href="../index.html" aria-label="Clinik Auto accueil">
                <img class="site-logo" src="../assets/logo.png" alt="Logo Clinik Auto">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="../contact/contact.php">Contact</a></li>
                <?php if ($showAdminReturn): ?>
                    <li><a href="../admin.php">Retour admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="inventory-page">
        <div class="inventory-page-head">
            <div>
                <span class="hero-badge">Pièces</span>
                <h1>Nos pièces auto d'occasion</h1>
                <p>Cliquez sur une ligne pour consulter la fiche détaillée et initier la réservation de la pièce sélectionnée.</p>
            </div>
            <a class="cta-link cta-link-small" href="occasion.php">Voir les véhicules d'occasion →</a>
        </div>

        <div class="inventory-layout">
            <section class="inventory-panel">
                <h3>Liste des annonces</h3>
                <div class="inventory-list">
                    <?php foreach ($items as $item): ?>
                        <a class="inventory-row <?php echo ($selected && (int) $selected['id'] === (int) $item['id']) ? 'is-active' : ''; ?> <?php echo ($item['status'] ?? '') === 'reserved' ? 'is-unavailable' : ''; ?>" href="pieces.php?id=<?php echo (int) $item['id']; ?>">
                            <img class="inventory-thumb" src="<?php echo catalog_escape(catalog_primary_image($item)); ?>" alt="<?php echo catalog_escape($item['title']); ?>">
                            <span class="inventory-row-text">
                                <strong><?php echo catalog_escape($item['title']); ?></strong>
                                <small><?php echo catalog_escape($item['subtitle']); ?></small>
                            </span>
                            <span class="inventory-side-meta">
                                <span class="inventory-price"><?php echo catalog_escape(catalog_format_price($item['price'])); ?> €</span>
                                <span class="status-pill <?php echo ($item['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($item)); ?></span>
                                <?php if (($item['status'] ?? '') !== 'reserved' && empty($item['transaction_in_progress'])): ?>
                                    <label class="part-selector-line">
                                        <input
                                            type="checkbox"
                                            class="part-selector"
                                            value="<?php echo (int) $item['id']; ?>"
                                            data-part-id="<?php echo (int) $item['id']; ?>"
                                            data-part-title="<?php echo catalog_escape($item['title']); ?>"
                                            data-part-deposit="<?php echo catalog_escape((string) catalog_reservation_amount($item['price'])); ?>"
                                        >
                                        Sélectionner
                                    </label>
                                <?php elseif (!empty($item['transaction_in_progress']) && empty($item['payment_confirmed'])): ?>
                                    <small class="part-selector-note">Positionnement en file uniquement</small>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="inventory-detail-panel">
                <?php if ($selected): ?>
                    <div class="detail-heading">
                        <div>
                            <span class="detail-category"><?php echo catalog_escape(catalog_type_label($selected['type'])); ?></span>
                            <h3><?php echo catalog_escape($selected['title']); ?></h3>
                            <p class="detail-subtitle"><?php echo catalog_escape($selected['subtitle']); ?></p>
                        </div>
                        <div class="detail-price-box">
                            <strong><?php echo catalog_escape(catalog_format_price($selected['price'])); ?> €</strong>
                            <span class="status-pill <?php echo ($selected['status'] ?? '') === 'reserved' ? 'is-muted' : ''; ?>"><?php echo catalog_escape(catalog_status_label($selected)); ?></span>
                        </div>
                    </div>

                    <div class="detail-gallery">
                        <?php foreach ($selected['images'] as $image): ?>
                            <figure class="detail-photo-card">
                                <img src="<?php echo catalog_escape($image['data']); ?>" alt="<?php echo catalog_escape($selected['title']); ?>">
                            </figure>
                        <?php endforeach; ?>
                    </div>

                    <div class="detail-grid">
                        <article class="detail-card">
                            <h4>Description</h4>
                            <p><?php echo nl2br(catalog_escape($selected['description'])); ?></p>
                        </article>
                        <article class="detail-card">
                            <h4>Renseignements pièce</h4>
                            <p><?php echo nl2br(catalog_escape($selected['specs'])); ?></p>
                        </article>
                    </div>

                    <div class="deposit-note">
                        <strong>Acompte de réservation :</strong> <?php echo catalog_escape((string) $deposit); ?> € par virement instantané, soit 30 % du montant affiché.
                    </div>

                    <div class="deposit-note multi-deposit-note">
                        <strong>Articles selectionnes :</strong> <span id="selected-parts-count">1</span><br>
                        <strong>Acompte total a virer :</strong> <span id="selected-parts-deposit"><?php echo catalog_escape(number_format($selectedDepositRaw, 2, ',', ' ')); ?> €</span>
                    </div>

                    <div class="detail-actions">
                        <?php if (($selected['status'] ?? '') === 'reserved'): ?>
                            <span class="cta-link cta-link-disabled">Pièce indisponible après confirmation du virement</span>
                            <?php if (!empty($selected['payment_confirmed'])): ?>
                            <p class="form-note">Si l'article n'est pas retiré, vous aurez la possibilité de le réserver ultérieurement.</p>
                            <a class="cta-link cta-link-small" href="<?php echo catalog_escape(catalog_build_contact_link($selected)); ?>">Me positionner pour réservation ultérieure →</a>
                            <?php endif; ?>
                        <?php elseif (!empty($selected['transaction_in_progress'])): ?>
                            <span class="cta-link cta-link-disabled">Transaction en cours sur cette pièce</span>
                            <p class="form-note">
                                Un client prioritaire est en cours de traitement.
                                <?php if ($selectedTransferCountdown !== ''): ?>
                                    Temps restant avant relance automatique : <?php echo catalog_escape($selectedTransferCountdown); ?>.
                                <?php endif; ?>
                            </p>
                            <a class="cta-link cta-link-small" href="<?php echo catalog_escape(catalog_build_contact_link($selected)); ?>">Me positionner sur la liste d'attente →</a>
                        <?php else: ?>
                            <?php
                                $contactLink = catalog_build_contact_link($selected);
                                if (is_array($selectedBankAccount) && !empty($selectedBankAccount['id'])) {
                                    $contactLink .= (strpos($contactLink, '?') === false ? '?' : '&') . http_build_query([
                                        'virement_compte_id' => (string) ($selectedBankAccount['id'] ?? '')
                                    ]);
                                }
                            ?>
                            <div class="devis-form-actions">
                                <a
                                    class="cta-link"
                                    href="<?php echo catalog_escape($contactLink); ?>"
                                    data-virement-target="<?php echo catalog_escape($contactLink); ?>"
                                    data-has-virement-popup="<?php echo is_array($selectedBankAccount) && !empty($selectedBankAccount['iban']) ? '1' : '0'; ?>"
                                    data-selected-id="<?php echo (int) ($selected['id'] ?? 0); ?>"
                                    data-selected-title="<?php echo catalog_escape((string) ($selected['title'] ?? '')); ?>"
                                    data-selected-deposit="<?php echo catalog_escape((string) $selectedDepositRaw); ?>"
                                >Valider ma commande →</a>
                                <button type="button" class="btn-secondary" id="pieces-continue-btn">Continuer la navigation →</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (!empty($items)): ?>
                    <div class="detail-card detail-card-placeholder">
                        <h3>Selectionnez une annonce</h3>
                        <p>La liste affiche l'integralite des pieces publiees et leur statut. Cliquez sur une annonce pour ouvrir sa fiche detaillee.</p>
                    </div>
                <?php else: ?>
                    <p>Aucune annonce pièce n'est disponible pour le moment.</p>
                <?php endif; ?>
            </section>
        </div>

        <?php if (is_array($selectedBankAccount) && !empty($selectedBankAccount['iban'])): ?>
            <div class="virement-modal" id="virement-modal" hidden>
                <div class="virement-modal-card" role="dialog" aria-modal="true" aria-labelledby="virement-modal-title">
                    <h3 id="virement-modal-title">Confirmation du virement</h3>
                    <p>Pour la sécurité de vos données bancaires, faites le virement depuis chez vous puis confirmez ci-dessous pour continuer votre parcours de réservation.</p>
                    <p><strong>Nombre d'articles sélectionnés :</strong> <span id="virement-selected-count">1</span></p>
                    <p><strong>Montant total de l'acompte à virer :</strong> <span id="virement-selected-deposit"><?php echo catalog_escape(number_format($selectedDepositRaw, 2, ',', ' ')); ?> €</span></p>
                    <div class="virement-modal-bank">
                        <p><strong>Compte :</strong> <?php echo catalog_escape((string) ($selectedBankAccount['label'] ?? 'Compte principal')); ?></p>
                        <p><strong>Beneficiaire :</strong> <?php echo catalog_escape((string) ($selectedBankAccount['beneficiary'] ?? '')); ?></p>
                        <?php if (!empty($selectedBankAccount['bank_name'])): ?>
                            <p><strong>Banque :</strong> <?php echo catalog_escape((string) ($selectedBankAccount['bank_name'] ?? '')); ?></p>
                        <?php endif; ?>
                        <p><strong>IBAN :</strong> <?php echo catalog_escape((string) ($selectedBankAccount['iban'] ?? '')); ?></p>
                        <?php if (!empty($selectedBankAccount['bic'])): ?>
                            <p><strong>BIC :</strong> <?php echo catalog_escape((string) ($selectedBankAccount['bic'] ?? '')); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selectedBankAccount['note'])): ?>
                            <p class="form-note"><?php echo catalog_escape((string) ($selectedBankAccount['note'] ?? '')); ?></p>
                        <?php endif; ?>
                    </div>
                    <label class="checkbox-toggle">
                        <input type="checkbox" id="virement-confirm-checkbox" value="1">
                        Je confirme avoir effectué le virement instantané depuis chez moi.
                    </label>
                    <div class="admin-form-actions" style="gap:0.6rem; flex-wrap:wrap;">
                        <button type="button" class="btn-secondary" id="virement-cancel">Annuler</button>
                        <button type="button" id="virement-continue" disabled>Valider le virement et continuer</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <script>
    (function () {
        var trigger = document.querySelector('[data-virement-target]');
        if (!trigger) {
            return;
        }

        var hasPopup = trigger.getAttribute('data-has-virement-popup') === '1';
        if (!hasPopup) {
            return;
        }

        var modal = document.getElementById('virement-modal');
        var confirmCheckbox = document.getElementById('virement-confirm-checkbox');
        var continueButton = document.getElementById('virement-continue');
        var cancelButton = document.getElementById('virement-cancel');
        var selectedCountLabel = document.getElementById('selected-parts-count');
        var selectedDepositLabel = document.getElementById('selected-parts-deposit');
        var modalCountLabel = document.getElementById('virement-selected-count');
        var modalDepositLabel = document.getElementById('virement-selected-deposit');
        var selectors = document.querySelectorAll('.part-selector');
        if (!modal || !confirmCheckbox || !continueButton || !cancelButton) {
            return;
        }

        var targetUrl = trigger.getAttribute('data-virement-target') || trigger.getAttribute('href') || '';
        var selectedIds = [];
        var selectedTitles = [];
        var selectedTotalDeposit = 0;
        var storageKey = 'clinikauto_selected_parts_v1';
        var selectedId = parseInt(trigger.getAttribute('data-selected-id') || '0', 10);
        var selectedTitle = trigger.getAttribute('data-selected-title') || '';
        var selectedDeposit = parseFloat(trigger.getAttribute('data-selected-deposit') || '0');

        var updateSummary = function () {
            var count = selectedIds.length;
            if (count <= 0 && selectedId > 0) {
                selectedIds = [selectedId];
                selectedTitles = [selectedTitle];
                selectedTotalDeposit = isNaN(selectedDeposit) ? 0 : selectedDeposit;
                count = 1;
            }

            var formatted = (isNaN(selectedTotalDeposit) ? 0 : selectedTotalDeposit).toFixed(2).replace('.', ',') + ' €';
            if (selectedCountLabel) {
                selectedCountLabel.textContent = String(count);
            }
            if (selectedDepositLabel) {
                selectedDepositLabel.textContent = formatted;
            }
            if (modalCountLabel) {
                modalCountLabel.textContent = String(count);
            }
            if (modalDepositLabel) {
                modalDepositLabel.textContent = formatted;
            }
        };

        var syncSelectedFromCheckboxes = function () {
            selectedIds = [];
            selectedTitles = [];
            selectedTotalDeposit = 0;

            selectors.forEach(function (checkbox) {
                if (!checkbox.checked) {
                    return;
                }
                var id = parseInt(checkbox.getAttribute('data-part-id') || checkbox.value || '0', 10);
                var title = checkbox.getAttribute('data-part-title') || '';
                var depositValue = parseFloat(checkbox.getAttribute('data-part-deposit') || '0');
                if (id > 0) {
                    selectedIds.push(id);
                }
                if (title !== '') {
                    selectedTitles.push(title);
                }
                if (!isNaN(depositValue)) {
                    selectedTotalDeposit += depositValue;
                }
            });

            try {
                localStorage.setItem(storageKey, JSON.stringify(selectedIds));
            } catch (error) {
                // Aucun blocage fonctionnel si localStorage indisponible.
            }

            updateSummary();
        };

        var restoreSelection = function () {
            var restoredIds = [];
            try {
                var raw = localStorage.getItem(storageKey);
                var parsed = raw ? JSON.parse(raw) : [];
                if (Array.isArray(parsed)) {
                    restoredIds = parsed.map(function (value) {
                        return parseInt(value, 10);
                    }).filter(function (value) {
                        return value > 0;
                    });
                }
            } catch (error) {
                restoredIds = [];
            }

            if (restoredIds.length > 0) {
                selectors.forEach(function (checkbox) {
                    var id = parseInt(checkbox.getAttribute('data-part-id') || checkbox.value || '0', 10);
                    checkbox.checked = restoredIds.indexOf(id) !== -1;
                });
            }

            syncSelectedFromCheckboxes();
        };

        selectors.forEach(function (checkbox) {
            var selectorLine = checkbox.closest('.part-selector-line');

            // Prevent the parent row link from hijacking selection clicks.
            checkbox.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                checkbox.checked = !checkbox.checked;
                syncSelectedFromCheckboxes();
            });

            if (selectorLine) {
                selectorLine.addEventListener('click', function (event) {
                    if (event.target === checkbox) {
                        return;
                    }
                    event.preventDefault();
                    event.stopPropagation();
                    checkbox.checked = !checkbox.checked;
                    syncSelectedFromCheckboxes();
                });
            }
        });

        restoreSelection();

        var closeModal = function () {
            modal.hidden = true;
            document.body.style.overflow = '';
        };
        var openModal = function () {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        };

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            syncSelectedFromCheckboxes();
            openModal();
        });

        confirmCheckbox.addEventListener('change', function () {
            continueButton.disabled = !confirmCheckbox.checked;
        });

        cancelButton.addEventListener('click', function () {
            confirmCheckbox.checked = false;
            continueButton.disabled = true;
            closeModal();
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                cancelButton.click();
            }
        });

        continueButton.addEventListener('click', function () {
            if (!confirmCheckbox.checked || targetUrl === '') {
                return;
            }

            var destination = new URL(targetUrl, window.location.href);
            destination.searchParams.set('selected_parts_count', String(selectedIds.length));
            destination.searchParams.set('selected_parts_ids', selectedIds.join(','));
            destination.searchParams.set('selected_parts_titles', selectedTitles.join(' | '));
            destination.searchParams.set('acompte_total', String((isNaN(selectedTotalDeposit) ? 0 : selectedTotalDeposit).toFixed(2)));
            destination.searchParams.set('acompte_confirme', '1');
            destination.searchParams.set('virement_confirme_client', '1');
            window.location.href = destination.toString();
        });

        var piecesContinueBtn = document.getElementById('pieces-continue-btn');
        if (piecesContinueBtn) {
            piecesContinueBtn.addEventListener('click', function () {
                syncSelectedFromCheckboxes();
                alert('Vos choix restent actifs pendant votre navigation sur le site et seront conservés jusqu\'à l\'envoi final de votre demande.');
                window.location.href = 'catalogue.php';
            });
        }
    })();
    </script>
    <footer>
        <address>
            <strong>Clinik Auto</strong> &mdash; Garage Automobile<br>
            118 Clos des Teppes, 74950 Scionzier<br>
            <a href="tel:+33620185627">06 20 18 56 27</a> &bull;
            <a href="mailto:clinikauto74@gmail.com">clinikauto74@gmail.com</a>
        </address>
        <p class="footer-hours">Lun&ndash;Ven : 9h&ndash;12h / 14h&ndash;18h &bull; Sam : 9h&ndash;12h &bull; Dim : Ferm&eacute;</p>
        <p class="footer-social">
            <a href="https://www.facebook.com/Clinik.Auto" target="_blank" rel="noopener noreferrer">&#x1F4D8; Suivez-nous sur Facebook</a>
        </p>
        <p class="footer-copy">&copy; 2026 Clinik Auto. Tous droits r&eacute;serv&eacute;s.</p>