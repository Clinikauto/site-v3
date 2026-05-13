Clinik Auto - Guide d'installation et d'utilisation
====================================================

📋 STRUCTURE DU PROJET
=====================
/clinikauto/
├── index.html                  # Page d'accueil
├── assets/
│   └── style.css              # Feuille de styles CSS
├── catalogue/
│   └── catalogue.php          # Page du catalogue des véhicules
├── rdv/
│   └── rdv.php                # Formulaire de rendez-vous
└── contact/
    └── contact.php            # Page de contact

🚀 DÉPLOIEMENT SUR O2SWITCH
===========================

1. Préparation locale :
   - Tous les fichiers sont prêts dans le dossier "clinikauto"
   - Tester localement : ouvrir index.html dans un navigateur

2. Upload sur o2switch :
  - Se connecter à o2switch (cPanel)
   - Aller à "Gestionnaire de fichiers" ou "FTP"
   - Uploader TOUS les fichiers du dossier "clinikauto" vers la racine (public_html ou www)
   - Structure finale attendue :
     * /public_html/index.html
     * /public_html/assets/style.css
     * /public_html/catalogue/catalogue.php
     * /public_html/rdv/rdv.php
     * /public_html/contact/contact.php

3. Configuration base de données (optionnel mais recommandé) :
  - Créer une base de données MySQL dans o2switch
   - Pour enregistrer les rendez-vous, modifier rdv/rdv.php :
     * Ajouter les identifiants de connexion BDD
     * Créer une table "rendez_vous" avec les colonnes : id, nom, email, date, service

4. Configuration des emails (optionnel) :
   - Pour envoyer les confirmations, configurer dans PHP :
     * contact/contact.php et rdv/rdv.php
     * Utiliser la fonction mail() ou phpMailer

5. Configuration SMTP robuste (recommande) :
   - Installer PHPMailer via Composer depuis la racine du projet :
     * composer require phpmailer/phpmailer
   - Ouvrir config.php et renseigner :
     * SMTP_ENABLED = true
     * SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_SECURE
     * EMAIL_EXPEDITEUR
   - Le formulaire contact utilisera SMTP (PHPMailer) avec fallback mail() automatique.

6. Variables d'environnement (local + production) :
  - Utiliser le fichier .env.example comme modele.
  - En local : copier .env.example vers .env et adapter les valeurs.
  - En production : definir les memes variables dans l'hebergeur (cPanel / panneau d'environnement).
  - Variables DB requises : DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME.
  - Pour l'admin : CATALOG_ADMIN_REQUIRE_DB=1 recommande en production.

📝 FICHIERS PRINCIPAUX
=====================

index.html
----------
- Page d'accueil avec :
  * Présentation du garage
  * Liste des services
  * Bouton d'appel à l'action

catalogue.php
--------------
- Affiche liste des véhicules disponibles
- Actuellement un tableau avec 3 exemples
- À modifier avec vos propres véhicules

rdv.php
-------
- Formulaire de prise de rendez-vous
- Champs : Nom, Email, Date, Service
- Actuellement affiche un message de confirmation
- À connecter à une base de données

contact.php
-----------
- Formulaire de contact
- Informations du garage
- Champs : Nom, Email, Téléphone, Sujet, Message
- À configurer pour envoyer des emails

🎨 PERSONNALISATION
===================

Modifier les couleurs dans assets/style.css :
- Couleur principale : #1e3c72 (bleu foncé)
- Couleur secondaire : #2a5298 (bleu clair)
- Couleur d'accent : #ffc107 (jaune)

Modifier les informations dans contact.php :
- Adresse
- Téléphone
- Email
- Horaires d'ouverture

Ajouter des véhicules dans catalogue.php :
- Modifier le tableau $voitures

✅ CHECKLIST AVANT LANCEMENT
============================
- [ ] Tous les fichiers uploadés sur o2switch
- [ ] Tester tous les liens de navigation
- [ ] Tester le formulaire de contact
- [ ] Tester le formulaire de rendez-vous
- [ ] Vérifier que le CSS s'affiche correctement
- [ ] Vérifier sur mobile
- [ ] Configurer les emails (optionnel)
- [ ] Configurer la base de données (optionnel)
- [ ] Mettre à jour les informations du garage
- [ ] Ajouter vos véhicules au catalogue

📞 SUPPORT
==========
Pour toute question ou modification, consultez :
- Documentation o2switch
- Documentation PHP
- Support de votre prestataire d'hébergement