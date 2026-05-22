# Checklist production - Clinik Auto
# Mise a jour : 2026-05-13 — coherente avec etat de developpement BUILD v3.1

---

## 1) Configuration serveur
- [ ] Copier config.production.example.php vers config.php sur le serveur.
- [ ] Remplir toutes les valeurs __A_COMPLETER__ dans config.php (voir liste section 1.1).
- [ ] Garder CATALOG_ADMIN_REQUIRE_DB a true (deja prevu dans config.production.example.php).
- [ ] Renseigner ADMIN_ALLOWED_IPS avec votre IP publique fixe (actuellement vide = acces ouvert).
- [ ] Remplacer ADMIN_HIDDEN_ENTRY_KEY par une valeur longue et unique (valeur locale actuelle: CLINIKAUTO-ACCES-2026 — a changer obligatoirement).

### 1.1) Valeurs __A_COMPLETER__ dans config.production.example.php
- [ ] GARAGE_ADRESSE
- [ ] GARAGE_TEL
- [ ] GARAGE_EMAIL
- [ ] GARAGE_HORAIRES
- [ ] DB_HOST, DB_USER, DB_PASS, DB_NAME
- [ ] EMAIL_EXPEDITEUR
- [ ] SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD
- [ ] GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REFRESH_TOKEN (voir section 4.2)
- [ ] ADMIN_LOGIN, ADMIN_PASSWORD_HASH, ADMIN_PASSWORD_RESET_EMAIL
- [ ] ADMIN_HIDDEN_ENTRY_KEY
- [ ] ADMIN_ALLOWED_IPS

---

## 2) Base de donnees
- [ ] Creer la base MySQL/MariaDB cible.
- [ ] Importer database.sql.
- [ ] Verifier que le port SQL est ouvert depuis PHP uniquement (pas depuis Internet public).

---

## 3) PHP et dependances
- [ ] Executer : composer install --no-dev dans le dossier clinikauto-v3-final sur le serveur.
- [ ] Verifier COMPOSER_AUTOLOAD_PATH pointe bien vers vendor/autoload.php.
- [ ] Verifier extensions PHP requises actives : mysqli, curl, mbstring, openssl, gd.

---

## 4) Securite — actions obligatoires avant mise en ligne

### 4.1) Nettoyage des fichiers de developpement
- [ ] Supprimer ou bloquer via .htaccess (deja configure) les fichiers suivants presents dans le depot :
  - admin.php.bak, admin.php.new
  - diag_balance.ps1, diag_balance_large.ps1
  - extracted_section.txt
  - process_cmd.txt
  - "test depart en conditionréel.bat", "test depart en conditionréel.ps1"
  NOTE : .htaccess bloque deja l'acces HTTP a *.bak, *.new, *.ps1, *.bat, tmp_*.php, test_*.php,
         diag_*.php, email-log.php, google-oauth-setup.php. Verifier que le bloc FilesMatch est actif.

### 4.2) Rotation obligatoire des secrets
- [ ] Nouveau mot de passe SMTP applicatif (si Gmail : nouveau mot de passe d'application).
- [ ] Nouvelle valeur ADMIN_HIDDEN_ENTRY_KEY (chaine aleatoire longue, pas CLINIKAUTO-ACCES-2026).
- [ ] Nouveau ADMIN_PASSWORD_HASH : generer avec password_hash('nouveau_mdp', PASSWORD_DEFAULT).
- [ ] Verifier que les anciennes valeurs ne fonctionnent plus apres deploiement.

### 4.3) Restrictions acces admin
- [ ] Renseigner ADMIN_ALLOWED_IPS avec l'IP fixe du garage (actuellement tableau vide = aucune restriction).
- [ ] Verifier que config.php n'est pas accessible via HTTP (protege par .htaccess).
- [ ] Verifier que .env n'est pas accessible via HTTP (protege par .htaccess).

---

## 5) Synchronisation Google Agenda
RAPPEL : desactivee automatiquement en local (CATALOG_IS_LOCAL_RUNTIME=true).
En production : activee (GOOGLE_CALENDAR_ENABLED=true) mais silencieuse si credentials vides.

- [ ] Option A — Activer la synchro :
  1. Google Cloud Console > Identifiants > creer client OAuth 2.0 (type "application Web").
  2. Renseigner GOOGLE_CLIENT_ID dans config.php.
  3. Definir variable d'env GOOGLE_CLIENT_SECRET.
  4. Executer google-oauth-setup.php sur le serveur pour obtenir le REFRESH_TOKEN.
  5. Definir variable d'env GOOGLE_REFRESH_TOKEN.
  6. Tester "Synchroniser Google Agenda maintenant" dans admin.php > Rappels rendez-vous.
  7. Verifier qu'un evenement test apparait dans Google Agenda sans erreur.
- [ ] Option B — Ne pas utiliser : laisser les 3 credentials vides, la synchro reste inactive sans erreur.

---

## 6) Etat du panneau admin — points specifiques a valider en production

### 6.1) Badge de build (admin-build-marker)
- [ ] Le badge "BUILD v3.1" est visible en haut de la page admin en local.
- [ ] DECISION : le conserver pour confirmer la version en prod, ou le retirer avant mise en ligne.
  Pour retirer : supprimer la div id="admin-build-marker" et son bloc CSS dans admin.php (lignes ~2136 et ~2212).

### 6.2) Section "Gestion devis" (section-devis)
- [ ] La section est masquee par defaut (allowLegacyDevisSection=false).
- [ ] Elle reste accessible uniquement via ?show_devis_panel=1 (acces de secours).
- [ ] Verifier en production que le bouton "Gestion devis" n'apparait pas dans la console admin.
- [ ] Si la section devis doit rester definitivement desactivee : supprimer le code mort en fin de sprint.

### 6.3) Bouton "Acces FTP" dans la console (Categorie 1)
- [ ] Verifier que l'URL du bouton "Acces FTP" correspond bien a votre cPanel o2switch.
  URL actuelle dans le code : https://cpanel.o2switch.net/
  Si votre URL personnalisee est differente (ex. https://mondomaine.o2switch.net:2083/),
  mettre a jour dans admin.php la valeur href de l'element <a> "Acces FTP" (Categorie 1, sous "Rappels RDV").
- [ ] Tester le lien en production (doit ouvrir le cPanel dans un nouvel onglet).

### 6.4) Section SMS rapide — bloc "Modele de sms"
- [ ] Le champ Numero est editable (pas readonly).
- [ ] Le lien "Preparer SMS" se met a jour en temps reel quand le numero ou le modele change (JS).
- [ ] Tester les 3 boutons : Preparer SMS, 1 clic PC, Copier message SMS.

---

## 7) Verification fonctionnelle complete
- [ ] Ouvrir admin_gate.php puis se connecter a admin.php.
- [ ] Verifier qu'aucun lien "Retour admin" n'apparait sur les pages client.
- [ ] Creer une annonce test (vehicule + piece) et la supprimer.
- [ ] Tester un envoi email depuis le formulaire contact.
- [ ] Tester un envoi de relance RDV (bouton Relancer dans Rappels rendez-vous).
- [ ] Tester le flux reservation piece/vehicule et validation admin.
- [ ] Tester l'ajout, modification et suppression d'une fiche client.
- [ ] Tester la recherche client par telephone (bloc SMS rapide > verification detection nom).
- [ ] Tester l'envoi SMS depuis la fiche client (boutons Preparer SMS, 1 clic PC, Copier).
- [ ] Tester la recherche code postal dans les formulaires RDV, devis, fiche client.

---

## 8) Verification mode strict DB
- [ ] Arreter la DB temporairement.
- [ ] Confirmer que l'admin affiche le message de blocage strict DB.
- [ ] Redemarrer la DB et verifier retour normal des actions.

---

## 9) Avant mise en ligne finale
- [ ] Sauvegarde complete : fichiers + base de donnees.
- [ ] Activer HTTPS et forcer redirection HTTP -> HTTPS.
- [ ] Mettre en place supervision (uptime + espace disque + erreurs PHP).
- [ ] Smoke test HTTP complet :
  - /
  - /catalogue/catalogue.php
  - /catalogue/occasion.php
  - /catalogue/pieces.php
  - /contact/contact.php
  - /rdv/rdv.php
  - /devis/devis.php
  - /admin_gate.php (verifier blocage sans cle)
  - /admin_gate.php?key=VOTRE_CLE (verifier acces)
  - /admin.php#section-reminders
  - /admin.php#section-customers
  - /admin.php#section-sms-quick
  - /admin.php#section-inventory
