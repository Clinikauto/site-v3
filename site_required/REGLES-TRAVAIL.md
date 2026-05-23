# RÈGLES DE TRAVAIL — CLINIKAUTO.FR
> Fichier de référence à respecter dans toutes les sessions de travail sur ce projet.

---

## RÈGLE N°1 — LANGUE ET NIVEAU

- Toutes les explications sont rédigées **entièrement en français**.
- L'utilisateur est **novice** : aucun jargon technique sans explication simple derrière.
- Chaque terme technique inconnu doit être défini en une phrase claire.

---

## RÈGLE N°2 — EXPLICATION ÉTAPE PAR ÉTAPE

- Chaque action est décrite **clic par clic**, commande par commande.
- Rien n'est supposé connu à l'avance.
- Format attendu :
  1. **Quoi faire** (ex : "Ouvre le fichier config.php")
  2. **Comment le faire** (ex : "Dans VS Code, clique sur le fichier dans le panneau de gauche")
  3. **Pourquoi** (ex : "Ce fichier contient les paramètres de connexion à la base de données")

---

## RÈGLE N°3 — VALIDATION AVANT CHAQUE ACTION SENSIBLE

- Avant toute modification sur le serveur en ligne (o2switch), créer un **checkpoint** (sauvegarde horodatée locale).
- Toujours lancer une **simulation (dry-run)** avant un envoi réel.
- Attendre la **validation explicite** de l'utilisateur avant de continuer.

---

## RÈGLE N°4 — ACTIONS IRRÉVERSIBLES

- Toute action qui supprime, écrase ou envoie des fichiers en production doit être **annoncée clairement**.
- Formulé ainsi : ⚠️ **Action irréversible — confirme avant de continuer.**
- L'utilisateur doit répondre "oui" ou "je confirme" avant que l'action soit exécutée.

---

## RÈGLE N°5 — RÉSUMÉ EN FIN D'ÉTAPE

- À la fin de chaque étape accomplie, un résumé court est affiché :
  - ✅ Ce qui a été fait
  - 📋 Ce qui reste à faire
  - ⚠️ Ce qu'il faut surveiller si applicable

---

## RÈGLE N°6 — SAUVEGARDE DE SESSION

- La commande **"fin de converssation"** déclenche une sauvegarde du point de travail en cours.
- La commande **"on repart"** reprend exactement depuis ce point sauvegardé.
- Aucune action n'est perdue entre deux sessions.

---

## RAPPEL OUTILS DU PROJET

| Outil | Rôle |
|---|---|
| `php -S 127.0.0.1:8000` | Serveur local pour tester les modifications |
| `deploy/sync-o2switch.ps1` | Synchronisation locale → o2switch |
| `deploy/rclone-o2switch.conf` | Paramètres de connexion SFTP o2switch |
| `deploy/o2sync.exclude` | Liste des dossiers/fichiers à ne pas envoyer |
| `backups/` | Checkpoints horodatés (zip + hash) |
