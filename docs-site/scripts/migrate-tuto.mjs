// Migre tuto_doc/source/tome{1..4}.md vers docs-site/docs/reference/.
// Decoupe chaque tome en un fichier par chapitre (titres `# Chapitre N - ...`
// de premier niveau, en ignorant tout titre qui tombe a l'interieur d'un bloc
// de code ``` ... ```), et convertit les encadres <div class="new|warn|note">
// en admonitions Docusaurus (:::tip/:::warning/:::note) pour un rendu MDX
// propre plutot que du HTML brut imbrique.
import { readFileSync, writeFileSync, mkdirSync, rmSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { escapeStrayBraces } from './mdx-safe.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..');
const sourceDir = join(repoRoot, 'tuto_doc', 'source');
const outRoot = join(__dirname, '..', 'docs', 'reference');

const tomes = [
  { file: 'tome1.md', dir: '01-tome-1-le-socle-et-le-demarrage', label: 'Tome I — Le socle et le démarrage' },
  { file: 'tome2.md', dir: '02-tome-2-les-donnees', label: 'Tome II — Les données' },
  { file: 'tome3.md', dir: '03-tome-3-linterface', label: "Tome III — L'interface" },
  { file: 'tome4.md', dir: '04-tome-4-les-modules-fournis', label: 'Tome IV — Les modules fournis' },
];

const admonitionKind = { new: 'tip', warn: 'warning', note: 'note' };
const admonitionLabel = { new: 'Nouveau', warn: 'Piège', note: 'À retenir' };

function convertCallouts(text) {
  const re = /<div class="(new|warn|note)" markdown="1">\n([\s\S]*?)\n<\/div>/g;
  return text.replace(re, (_, kind, body) => {
    const tag = admonitionKind[kind];
    const indented = body.trim();
    return `:::${tag} ${admonitionLabel[kind]}\n${indented}\n:::`;
  });
}

function slugify(title) {
  return title
    .toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[`'"]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60);
}

function splitChapters(text) {
  const lines = text.split('\n');
  const chapters = [];
  let current = null;
  let inFence = false;

  for (const line of lines) {
    if (/^```/.test(line.trim())) {
      inFence = !inFence;
    }
    const isTopHeading = !inFence && /^# \S/.test(line);
    if (isTopHeading) {
      if (current) chapters.push(current);
      current = { title: line.replace(/^#\s+/, '').trim(), lines: [] };
      continue;
    }
    if (current) current.lines.push(line);
    else {
      // texte avant le premier titre de niveau 1 (ne devrait pas arriver ici)
    }
  }
  if (current) chapters.push(current);
  return chapters;
}

function escapeFrontmatterTitle(title) {
  return title.replace(/"/g, '\\"');
}

if (existsSync(outRoot)) rmSync(outRoot, { recursive: true, force: true });
mkdirSync(outRoot, { recursive: true });

writeFileSync(
  join(outRoot, '_category_.json'),
  JSON.stringify({ label: 'Référence', position: 3 }, null, 2) + '\n'
);

const referenceIndex = [
  '---',
  'title: Référence',
  'sidebar_position: 1',
  '---',
  '',
  '# Référence officielle',
  '',
  "La référence complète du framework Devups, organisée en quatre tomes — issue des mêmes sources que les PDF `tuto_doc/`, pour que la version web et la version imprimable ne divergent jamais.",
  '',
].join('\n');
writeFileSync(join(outRoot, 'index.md'), referenceIndex + '\n');

for (const tome of tomes) {
  const srcPath = join(sourceDir, tome.file);
  const raw = readFileSync(srcPath, 'utf8');
  const chapters = splitChapters(raw);

  const tomeDir = join(outRoot, tome.dir);
  mkdirSync(tomeDir, { recursive: true });

  writeFileSync(
    join(tomeDir, '_category_.json'),
    JSON.stringify({ label: tome.label, position: tomes.indexOf(tome) + 1 }, null, 2) + '\n'
  );

  chapters.forEach((chapter, i) => {
    const num = String(i).padStart(2, '0');
    const slug = slugify(chapter.title) || `chapitre-${i}`;
    const body = escapeStrayBraces(convertCallouts(chapter.lines.join('\n'))).trim();
    const frontmatter = [
      '---',
      `title: "${escapeFrontmatterTitle(chapter.title)}"`,
      `sidebar_position: ${i}`,
      '---',
      '',
      '',
    ].join('\n');
    writeFileSync(join(tomeDir, `${num}-${slug}.md`), `${frontmatter}${body}\n`);
  });

  console.log(`${tome.file}: ${chapters.length} chapitres -> ${tomeDir}`);
}
