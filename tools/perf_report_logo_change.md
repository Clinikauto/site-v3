**Rapport comparatif — Remplacement `assets/logo.png` → `assets/logo.avif`**

- Fichiers sources des audits : [tools/lighthouse_index_after_logo2.json](tools/lighthouse_index_after_logo2.json) (avant), [tools/lighthouse_after_redirect.json](tools/lighthouse_after_redirect.json) (après)

## Valeurs clés

| Métrique | Avant (ms) | Après (ms) | Δ (ms) | Δ (%) |
|---|---:|---:|---:|---:|
| FCP | 1270.484 | 1238.964 | -31.52 | -2.48% |
| LCP | 5659.967 | 2257.928 | -3402.04 | -60.14% |
| Speed Index | 1836.602 | 3107.609 | +1271.01 | +69.23% |
| TBT | 775 | 756 | -19 | -2.45% |

## Identification LCP
- Dans l'audit **avant** (`tools/lighthouse_index_after_logo2.json`) la table `network-requests` contient une requête vers `http://127.0.0.1:8000/assets/logo.png` (grosse ressource, resourceType: Other) — c'était l'élément LCP.
- Dans l'audit **après** (`tools/lighthouse_after_redirect.json`) la même table montre `http://127.0.0.1:8000/assets/logo.avif` (image/avif) et **pas** de requête vers `/assets/logo.png` : la redirection + remplacement ont supprimé la requête PNG.

Preuve : consulter `network-requests` dans chacun des JSONs mentionnés ci‑dessus.

## Conclusion courte
Le passage à `logo.avif` (et la redirection 301 empêchant le PNG) a retiré la requête lourde `logo.png` — l'élément LCP — et a réduit le LCP d'environ 5.66 s → 2.26 s. Cela démontre un gain significatif sur le rendu du plus gros contenu.

## Fichiers générés
- `tools/perf_report_logo_change.md` (ce fichier)
- `tools/perf_report_logo_change.csv` (valeurs numériques)
