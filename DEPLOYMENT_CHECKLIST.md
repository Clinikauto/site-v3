# 📋 DEPLOYMENT CHECKLIST - ClinikAuto v3

**Date de déploiement prévu:** 14 mai 2026  
**Durée estimée:** 2h à 3h (première fois)  
**Objectif:** Site en ligne sur o2switch + tests basiques validés

---

## ✅ PHASE 0 : SÉCURITÉ & PRÉPARATION (À FAIRE CE SOIR OU TÔT LE MATIN)

### Avant de toucher à quoi que ce soit :
- [ ] Note tes identifiants o2switch/cPanel dans un document sécurisé (pas dans le code)
- [ ] Teste l'accès à ton cPanel o2switch (vérifie que tu peux te logger)
- [ ] Teste l'accès à phpMyAdmin (cPanel → Databases → phpMyAdmin)
- [ ] Prépare une zone de travail : cette checklist + accès cPanel visible

### Sauvegarde AVANT tout :
- [ ] Dans cPanel : File Manager → Crée un dossier `site-backup-DATE` 
- [ ] Dans cPanel : phpMyAdmin → Exporte la base de données actuelle en .sql
- [ ] Télécharge ces 2 sauvegardes sur ton ordinateur (zone sûre)
- [ ] Note où tu les as mises (tu auras besoin de les retrouver en cas de problème)

**⚠️ SI TU AS UN PROBLÈME :** Tu peux restaurer depuis ces sauvegardes.

---

## 🚀 PHASE 1 : DÉPLOIEMENT DES FICHIERS (Environ 20-40 min)

### Étape 1a : Préparer les fichiers localement
- [ ] Ouvre VS Code
- [ ] Vérifie que tu es dans le dossier `d:\site clinikauto v3\clinikauto`
- [ ] Terminal → `git log --oneline | head -5` → copie les 3 premiers commit IDs (pour référence)
- [ ] Terminal → `git status --short` → doit être VIDE (pas de changements)

**Si ce n'est pas vide:** STOP. Contacte-moi avant de continuer.

### Étape 1b : Télécharger les fichiers sur o2switch
**Via cPanel File Manager (plus facile que FTP) :**

1. Va dans cPanel → File Manager
2. Clique sur le dossier `public_html` (c'est la racine du site)
3. S'il y a du contenu ancien : renomme-le en `old-backup-DATE` (sécurité)
4. Upload tous les fichiers du dossier `d:\site clinikauto v3\clinikauto` dans `public_html`
   - Utilise "Upload" (glisser-déposer ou sélectionner fichiers)
   - Uploads les 2-3 fichiers les plus importants en priorité: index.html, admin.php, config.php
   - Puis le reste (can take 10-20 min selon la connexion)

**Attendre que tous les fichiers soient téléchargés (vérifier barre de progression).**

---

## 🗄️ PHASE 2 : BASE DE DONNÉES (Environ 10-25 min)

### Étape 2a : Vérifier/créer la BD de production
1. Va dans cPanel → Databases (ou MySQL Databases)
2. Cherche une base de données pour clinikauto (ex: `clinikauto_prod` ou `clinikauto`)
3. **Si elle n'existe pas:** Crée une nouvelle BD + utilisateur (garde les identifiants)
4. **Si elle existe:** Note son nom exact (tu le mettras dans config.php)

### Étape 2b : Importer le schéma/données
1. Va dans cPanel → phpMyAdmin
2. Sélectionne ta BD de production
3. Onglet "Import" → Sélectionne le fichier `database.sql` de ton dossier local
4. Clique "Go" → Attends la fin (quelques secondes)

**Résultat attendu:** Tables crées (customer_profiles, rendez_vous, etc.)

**Si erreur SQL:** Note l'erreur, contacte-moi avant de continuer.

---

## ⚙️ PHASE 3 : CONFIGURATION PRODUCTION (Environ 10-15 min)

### Étape 3a : Éditer config.php pour o2switch
1. Dans cPanel File Manager → public_html → Clique droit sur `config.php` → Edit
2. Mets à jour ces variables (garde les autres comme elles sont):

```php
define('DB_HOST', 'localhost');  // o2switch default
define('DB_USER', 'TON_USER_BD');  // celui que tu as créé/noté
define('DB_PASS', 'TON_PASS_BD');  // celui que tu as créé/noté
define('DB_NAME', 'TA_BD');       // celui que tu as créé/noté
define('DB_PORT', 3306);          // default o2switch

// IMPORTANT: À vérifier
define('GARAGE_EMAIL', 'clinikauto74@gmail.com');  // Email qui reçoit les formulaires
define('SMTP_HOST', 'mail.o2switch.com');          // o2switch SMTP
define('SMTP_PORT', 587);                          // o2switch default
define('SMTP_USER', 'ton_email@ton_domaine.fr');   // Email o2switch
define('SMTP_PASS', 'ton_mot_passe_email');        // Mdp email o2switch

// SÉCURITÉ: À désactiver en production
define('DEBUG_MODE', false);  // JAMAIS true en production
```

3. Clique "Save" → Retour

### Étape 3b : Vérifier les permissions (optionnel mais recommandé)
En cPanel File Manager:
- [ ] Dossier `data/` → Clic droit → Permissions → 755 (ou 775 si c'est un dossier d'écriture)
- [ ] Dossier `email-logs/` → Permissions → 755

---

## 🧪 PHASE 4 : TESTS SMOKE (CRITIQUES) (Environ 20-35 min)

### Étape 4a : Vérifier l'accès au site
1. Ouvre un navigateur
2. Va à `https://www.clinikauto.fr` (ou le domaine o2switch)
3. **Attendu:** Page d'accueil charge, pas d'erreur 500

**Erreur 500 ou blank page?** 
→ Va dans cPanel → Error Logs → regarde les dernières erreurs
→ Note-les, on les corrigera

### Étape 4b : Tester les formulaires (Contact, RDV, Devis)
1. Va à `https://www.clinikauto.fr/contact/contact.php`
2. Remplis le formulaire avec des données TEST
3. Envoie → **Attendu:** Message "Demande bien reçue" ou redirection
4. Vérifier l'email a bien été reçu à clinikauto74@gmail.com
5. **Répète pour:** rdv/rdv.php et devis/devis.php

**Emails ne arrivent pas?** 
→ Vérifie SMTP_HOST/SMTP_USER/SMTP_PASS dans config.php

### Étape 4c : Vérifier le catalogue/annonces
1. Va à `https://www.clinikauto.fr/catalogue/catalogue.php`
2. **Attendu:** Page charge (même vide si pas d'annonces)
3. Va à l'admin: `https://www.clinikauto.fr/admin.php`
4. Login avec tes identifiants
5. Ajoute une annonce TEST (voir section admin)

### Étape 4d : Test de reconnaissance client (optionnel)
1. Va à `https://www.clinikauto.fr/contact/contact.php`
2. Envoie un formulaire avec un email valide
3. Reviens au formulaire contact
4. **Attendu:** Tes infos (nom, prénom, email) sont préremplies

---

## ✨ PHASE 5 : POST-DÉPLOIEMENT IMMÉDIATS (si temps)

- [ ] Vérifier HTTPS fonctionne (certificat SSL actif)
- [ ] Vérifier favicon charge (logo.png)
- [ ] Vérifier CSS charge (pas de 404 sur style.css)

---

## 📊 PHASE 6 : PROCHAINES ÉTAPES (à faire demain ou cette semaine)

**Dans les prochaines 24-48h :**
- [ ] Soumettre sitemap à Google Search Console
- [ ] Activer Google Analytics (ou vérifier que c'est bon)
- [ ] Vérifier Core Web Vitals via PageSpeed Insights
- [ ] Activer fiche Google Business Profile

**Dans la semaine :**
- [ ] Ajouter vrais données de test (annonces, prestations)
- [ ] Optimiser images
- [ ] Recueillir feedback utilisateur réel

---

## 🆘 SOS : JE SUIS BLOQUÉ

### Erreur 500 ou page blanche:
1. Va dans cPanel → Error Logs
2. Regarde la dernière erreur PHP
3. Message courant : "Database connection failed" → Vérifie config.php
4. Message courant : "Cannot find file" → Vérifie que les fichiers sont uploadés

### Formulaires ne marchent pas:
1. Vérifie que la BD est connectée (test accueil d'abord)
2. Vérifie que la table customer_profiles existe (phpMyAdmin)
3. Vérifie que les dossiers data/ et email-logs/ sont accessibles

### Emails ne s'envoient pas:
1. Vérifie SMTP_HOST/USER/PASS dans config.php
2. Demande à o2switch: support o2switch par chat
3. Test: envoie un mail simple depuis cPanel (Mail → Send Mail)

### Site lent ou timeout:
1. Check : as-tu uploadé TOUS les fichiers (notamment vendor/)?
2. Vérifier les logs d'erreur PHP

---

## 📝 CONTACTS URGENCE

- **O2switch support:** https://www.o2switch.fr/contact
- **Ton cPanel:** https://[ton-domaine].o2switch.com:2083
- **phpMyAdmin:** Depuis cPanel → Databases → phpMyAdmin

---

## ✅ CHECKLIST FINALE (avant de dire "c'est bon")

- [ ] Site load correctement (pas 500 erreur)
- [ ] Contact formulaire envoie et tu reçois mail
- [ ] RDV formulaire fonctionne
- [ ] Admin login fonctionne
- [ ] Catalogue page charge
- [ ] HTTPS fonctionne
- [ ] Logo/favicon charge

**SI TOUS LES POINTS SONT COCHÉS:** Déploiement réussi! 🎉

---

**Créé pour ClinikAuto v3 - 13 mai 2026**  
**À consulter pendant le déploiement (14 mai 2026)**
