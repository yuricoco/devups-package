# docs-site

Site de documentation web de Devups, construit avec [Docusaurus](https://docusaurus.io/). Toolchain Node isolée de `composer.json` — n'affecte en rien le runtime PHP du framework.

## Principe : une seule source de vérité

Le contenu n'est jamais écrit à la main dans `docs/reference/` ni `docs/migration/` : ces dossiers sont **générés** depuis les fichiers que lisent déjà les contributeurs qui ne passent pas par le site web, pour qu'il ne puisse jamais y avoir de divergence.

| Dossier généré | Source | Script |
|---|---|---|
| `docs/reference/` | `../tuto_doc/source/tome{1..4}.md` | `scripts/migrate-tuto.mjs` |
| `docs/migration/` | `../UPGRADE-*.md` | `scripts/sync-upgrade-guides.mjs` |

Après toute modification de `tuto_doc/source/*.md` ou `UPGRADE-*.md`, relancer :

```bash
npm run sync
```

`docs/demarrage/` et `docs/guides/` sont écrits directement (pas de source externe).

## Développement local

```bash
npm install
npm run sync
npm start
```

## Build

```bash
npm run build
```

Génère le site statique dans `build/`, ainsi que `build/llms.txt` et `build/llms-full.txt` (plugin maison `plugins/llms-txt/`, voir [llmstxt.org](https://llmstxt.org/)) : un index et un dump Markdown complet de la doc, pensés pour qu'un agent IA puisse l'ingérer directement plutôt que de scraper le HTML rendu.

## Structure

- **Démarrage** — installation, quickstart.
- **Guides** — scénarios pratiques courts, un par fonctionnalité.
- **Référence** — les quatre tomes officiels (mêmes sources que les PDF `tuto_doc/`).
- **Migration** — `UPGRADE-*.md` du dépôt, exposés comme pages versionnées.
