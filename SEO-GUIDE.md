# Guide SEO – Google Search Console & Google Business Profile
## Clinik Auto – Scionzier (74950)

---

## 1. Google Search Console

### Étape 1 – Vérifier la propriété du site

1. Allez sur [search.google.com/search-console](https://search.google.com/search-console)
2. Cliquez **Ajouter une propriété** → choisissez **Domaine** (couvre http/https/www/non-www)
3. Entrez `clinikauto.fr`
4. **Méthode de vérification recommandée** : Enregistrement DNS TXT
   - Connectez-vous à votre registrar (ex : o2switch)
   - Ajoutez l'enregistrement TXT fourni par Google dans la zone DNS
   - Attendez 1 à 24h, puis cliquez "Vérifier"

### Étape 2 – Soumettre le sitemap

1. Dans Search Console → **Sitemaps** (menu gauche)
2. Entrez l'URL : `https://www.clinikauto.fr/sitemap.xml`
3. Cliquez **Envoyer**
4. Attendez que Google indique "Succès" et affiche le nombre d'URLs détectées (actuellement **11 URLs**)

### Étape 3 – Inspecter les URLs clés

Pour chaque page importante, faites **Inspecter l'URL** dans Search Console et cliquez **Demander l'indexation** :

| URL | Priorité |
|-----|----------|
| `https://www.clinikauto.fr/` | ⭐⭐⭐ Critique |
| `https://www.clinikauto.fr/revision-scionzier.php` | ⭐⭐⭐ Critique |
| `https://www.clinikauto.fr/garage-cluses.php` | ⭐⭐ Important |
| `https://www.clinikauto.fr/garage-bonneville.php` | ⭐⭐ Important |
| `https://www.clinikauto.fr/garage-sallanches.php` | ⭐⭐ Important |
| `https://www.clinikauto.fr/catalogue/occasion.php` | ⭐⭐ Important |
| `https://www.clinikauto.fr/catalogue/pieces.php` | ⭐ Utile |
| `https://www.clinikauto.fr/devis/devis.php` | ⭐ Utile |

### Étape 4 – Surveiller les performances (hebdomadaire)

- **Rapport de performances** : vérifiez les requêtes qui génèrent des impressions et des clics
- **Rapport de couverture** : identifiez les pages en erreur ou exclues
- **Expérience de page** : vérifiez les Core Web Vitals (LCP, INP, CLS)
- **Liens** : surveillez les backlinks entrants

### Requêtes cibles à surveiller

```
garage Scionzier
révision auto Scionzier
garage Cluses 74
réparation voiture Cluses
garage Bonneville 74130
pièces auto occasion Scionzier
voiture occasion Scionzier
```

---

## 2. Google Business Profile (ex-Google My Business)

### Étape 1 – Revendiquer ou créer la fiche

1. Allez sur [business.google.com](https://business.google.com)
2. Recherchez "Clinik Auto Scionzier" → **Revendiquer cette fiche** OU **Créer une fiche**
3. Vérification : Google envoie une carte postale ou propose la vérification par téléphone/email

### Étape 2 – Compléter la fiche à 100 %

Remplissez **tous** les champs :

| Champ | Valeur |
|-------|--------|
| Nom | Clinik Auto |
| Catégorie principale | Garage automobile |
| Catégories secondaires | Vendeur de voitures d'occasion, Magasin de pièces automobiles |
| Adresse | 118 Clos des Teppes, 74950 Scionzier |
| Téléphone | 06 20 18 56 27 |
| Site web | https://www.clinikauto.fr |
| Horaires | Lun–Ven 9h–12h / 14h–18h, Sam 9h–12h |
| Description | Garage automobile multimarque à Scionzier (74950). Révision, entretien, réparation auto, vente de véhicules d'occasion et pièces détachées contrôlées. Devis gratuit en ligne. |
| Zone de service | Scionzier, Cluses, Bonneville, Sallanches, Marnaz, Haute-Savoie |
| Services | Révision, Vidange, Réparation moteur, Freinage, Diagnostic électronique, Vente VO, Pièces d'occasion |

### Étape 3 – Ajouter des photos (priorité haute)

- **Logo** : logo Clinik Auto (haute résolution)
- **Photo de couverture** : façade de l'atelier ou intérieur
- **Photos de l'atelier** : équipements, zone de travail propre
- **Photos de véhicules** : VO disponibles
- **Photos de pièces** : pièces en stock
- Objectif : **10+ photos** dans les 2 premières semaines

### Étape 4 – Répondre aux avis existants

- Répondre à **chaque avis** (positif ET négatif) sous 48h
- Pour les avis positifs : remercier, mentionner le service rendu si possible
- Pour les avis négatifs : répondre avec calme, proposer de contacter directement

**Formule type pour un avis 5 étoiles :**
> "Merci beaucoup [Prénom] ! Nous sommes ravis que votre [révision/réparation] se soit bien passée. À bientôt chez Clinik Auto ! 🔧"

### Étape 5 – Publier des posts réguliers

Publiez **1 post par semaine** via la rubrique "Publications" de Google Business Profile :

| Type de post | Exemple |
|--------------|---------|
| Offre | "Révision complète à partir de XX€ – Devis gratuit sur clinikauto.fr" |
| Actualité | "Nouveau véhicule VO disponible : [marque modèle année km]" |
| Événement | "Opération freinage gratuit ce samedi matin !" |
| Nouveauté | "Nouvelles pièces auto d'occasion disponibles : jantes XX pouces" |

---

## 3. Vérification Rich Results

Utilisez le **Rich Results Test** de Google pour valider les données structurées de chaque page :

```
https://search.google.com/test/rich-results
```

Pages à tester en priorité :

| Page | Schema attendu |
|------|----------------|
| `https://www.clinikauto.fr/` | AutoRepair, FAQPage, WebSite |
| `https://www.clinikauto.fr/revision-scionzier.php` | AutoRepair, Service |
| `https://www.clinikauto.fr/garage-cluses.php` | AutoRepair, BreadcrumbList |
| `https://www.clinikauto.fr/catalogue/occasion.php` | CollectionPage, Car, BreadcrumbList |
| `https://www.clinikauto.fr/catalogue/pieces.php` | CollectionPage, Product, BreadcrumbList |

---

## 4. Plan d'action prioritaire (30 jours)

### Semaine 1 – Fondations
- [ ] Vérifier Search Console (DNS TXT)
- [ ] Soumettre sitemap.xml
- [ ] Demander l'indexation des 8 URLs clés
- [ ] Revendiquer/créer fiche Google Business Profile
- [ ] Compléter la fiche GBP à 100%

### Semaine 2 – Contenu & Photos
- [ ] Ajouter 10+ photos sur GBP
- [ ] Publier le 1er post GBP
- [ ] Tester toutes les pages avec Rich Results Test
- [ ] Corriger les éventuelles erreurs de schema

### Semaine 3 – Avis & Liens
- [ ] Contacter les 5–10 premiers clients satisfaits pour leur demander un avis Google
- [ ] Placer le lien direct d'avis Google sur le site (dans le footer ou sur la page contact)
- [ ] Vérifier que le site est listé sur : PagesJaunes, Yelp, Kompass, Annuaire du BTP

### Semaine 4 – Suivi & Ajustements
- [ ] Consulter Search Console : premières données d'impressions
- [ ] Identifier les requêtes qui génèrent des clics
- [ ] Créer du contenu supplémentaire si une requête locale manquante est identifiée
- [ ] Publier le 2e post GBP

---

## 5. Lien direct d'avis Google à placer sur le site

Pour générer le lien direct vers le formulaire d'avis Google :

1. Cherchez "Clinik Auto Scionzier" sur Google Maps
2. Dans la fiche, cliquez **"Laisser un avis"**
3. Copiez l'URL complète
4. Placez ce lien dans : footer du site, page contact, email de confirmation de RDV

**Formule d'invitation dans les emails** :
> "Vous êtes satisfait de notre service ? Un avis Google nous aide beaucoup : [lien direct]"

---

## 6. Ressources utiles

| Outil | URL |
|-------|-----|
| Google Search Console | https://search.google.com/search-console |
| Google Business Profile | https://business.google.com |
| Rich Results Test | https://search.google.com/test/rich-results |
| PageSpeed Insights | https://pagespeed.web.dev |
| Google Analytics | https://analytics.google.com (ID : G-922P3Q1MBQ) |
| Schema Validator | https://validator.schema.org |
