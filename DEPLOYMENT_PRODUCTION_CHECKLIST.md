# Checklist production - Clinik Auto

## 1) Configuration serveur
- Copier config.production.example.php vers config.php sur le serveur.
- Remplir toutes les valeurs __A_COMPLETER__.
- Garder CATALOG_ADMIN_REQUIRE_DB a true.
- Mettre ADMIN_ALLOWED_IPS avec votre IP publique fixe.
- Verifier ADMIN_HIDDEN_ENTRY_KEY avec une valeur longue et unique.

## 2) Base de donnees
- Creer la base MySQL/MariaDB cible.
- Importer database.sql.
- Verifier DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME.
- Verifier que le port SQL est ouvert depuis PHP (pas depuis Internet public).

## 3) PHP et dependances
- Installer les dependances Composer dans le dossier clinikauto.
- Verifier COMPOSER_AUTOLOAD_PATH.
- Verifier extensions PHP requises: mysqli, curl, mbstring, openssl, gd.

## 4) Securite
- Regenerer et remplacer tous les secrets (SMTP, Google, ADMIN_HIDDEN_ENTRY_KEY).
- Changer ADMIN_PASSWORD_HASH avec un hash neuf.
- Restreindre l'acces HTTP a admin.php et admin_gate.php (WAF, firewall, ou .htaccess selon hebergeur).
- Verifier que config.php n'est pas telechargeable publiquement.
- Verifier que .env n'est jamais accessible via HTTP (bloque par .htaccess).
- Retirer/neutraliser les utilitaires debug avant mise en ligne (test_*.php, tmp_*.php, email-log.php, scripts diag_*, *.bak/*.new).

## 4.1) Rotation obligatoire des secrets
- Regenerer le mot de passe SMTP applicatif (si Gmail: nouveau mot de passe d'application).
- Regenerer GOOGLE_CLIENT_SECRET et surtout GOOGLE_REFRESH_TOKEN.
- Regenerer ADMIN_HIDDEN_ENTRY_KEY et le mot de passe admin (nouveau hash bcrypt).
- Verifier que les anciennes valeurs ne fonctionnent plus.

## 5) Verification fonctionnelle
- Ouvrir admin_gate.php puis se connecter a admin.php.
- Verifier qu'aucun lien Retour admin n'apparait sur les pages client.
- Creer une annonce test et la supprimer.
- Tester un envoi email (contact ou relance RDV).
- Tester le flux reservation piece/vehicule et validation admin.

## 6) Verification mode strict DB
- Arreter la DB temporairement.
- Confirmer que l'admin affiche le message de blocage strict DB.
- Redemarrer la DB et verifier retour normal des actions.

## 7) Avant mise en ligne
- Sauvegarde complete: fichiers + base.
- Activer HTTPS et forcer la redirection HTTP -> HTTPS.
- Mettre en place supervision (uptime + espace disque + erreurs PHP).
- Lancer un smoke test HTTP: /, /catalogue/catalogue.php, /catalogue/occasion.php, /catalogue/pieces.php, /contact/contact.php, /rdv/rdv.php, /devis/devis.php, /admin_gate.php.
