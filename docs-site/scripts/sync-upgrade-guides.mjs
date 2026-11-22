// Copie les guides UPGRADE-*.md du depot (source unique de verite, a cote de
// TODO.md) vers docs-site/docs/migration/, avec juste le frontmatter Docusaurus
// ajoute en tete - pour que la page web ne puisse jamais diverger du fichier
// que lisent les contributeurs qui ne passent pas par le site.
import { readFileSync, writeFileSync, mkdirSync, readdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { escapeStrayBraces } from './mdx-safe.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..');
const outDir = join(__dirname, '..', 'docs', 'migration');

mkdirSync(outDir, { recursive: true });

writeFileSync(
  join(outDir, '_category_.json'),
  JSON.stringify({ label: 'Migration', position: 4 }, null, 2) + '\n'
);

const upgradeFiles = readdirSync(repoRoot).filter((f) => /^UPGRADE-.*\.md$/i.test(f));

for (const file of upgradeFiles) {
  const raw = readFileSync(join(repoRoot, file), 'utf8');
  const firstLine = raw.split('\n', 1)[0].replace(/^#+\s*/, '');
  const slug = file.replace(/^UPGRADE-/i, '').replace(/\.md$/i, '').toLowerCase();
  const body = escapeStrayBraces(raw.replace(/^#[^\n]*\n/, '')).trim();
  const out = [
    '---',
    `title: "${firstLine.replace(/"/g, '\\"')}"`,
    `sidebar_position: ${slug < '8' ? 0 : 1}`,
    '---',
    '',
    `# ${firstLine}`,
    '',
    body,
    '',
  ].join('\n');
  writeFileSync(join(outDir, `${slug}.md`), out);
  console.log(`${file} -> docs/migration/${slug}.md`);
}
