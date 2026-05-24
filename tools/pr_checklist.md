PR Checklist — Remarques pour la revue

- **But**: Valider le rapport comparatif et la preuve réseau montrant la suppression de `assets/logo.png`.
- **Files ajoutés**: `tools/perf_report_logo_change.md`, `tools/perf_report_logo_change.csv`

Checklist:

- [ ] Vérifier que les métriques (FCP, LCP, Speed Index, TBT) correspondent aux JSON Lighthouse.
- [ ] Confirmer l'absence de `/assets/logo.png` dans `network-requests` de l'audit après.
- [ ] Valider que les templates servissent `picture` / `logo.avif` et que OG/Twitter utilisent `logo.avif`.
- [ ] Valider que la redirection 301 dans `.htaccess` est souhaitée en production.
- [ ] Approver et merger si OK.

Review request:

Merci de reviewer : @Clinikauto (ou indiquer le reviewer souhaité).
