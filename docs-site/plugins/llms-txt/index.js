// Plugin Docusaurus maison : genere /llms.txt (index structure, format
// https://llmstxt.org/) et /llms-full.txt (contenu complet concatene) a
// partir des memes fichiers Markdown que le site humain, pour qu'un agent
// IA puisse ingerer la doc sans scraper le HTML rendu - et sans jamais
// pouvoir diverger du contenu que lisent les humains, puisque la source
// est strictement la meme.
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';

function stripNumPrefix(segment) {
  return segment.replace(/^\d+-/, '');
}

function parseFrontmatter(raw) {
  const m = raw.match(/^---\n([\s\S]*?)\n---\n*/);
  if (!m) return { data: {}, body: raw };
  const data = {};
  for (const line of m[1].split('\n')) {
    const kv = line.match(/^(\w+):\s*"?(.*?)"?\s*$/);
    if (kv) data[kv[1]] = kv[2];
  }
  return { data, body: raw.slice(m[0].length) };
}

function firstParagraph(body) {
  const text = body
    .replace(/^#.*$/gm, '')
    .replace(/:::\w*.*$/gm, '')
    .trim();
  const para = text.split(/\n\s*\n/).find((p) => p.trim().length > 0) || '';
  const oneLine = para.replace(/\n/g, ' ').replace(/[*_`]/g, '').trim();
  return oneLine.length > 160 ? oneLine.slice(0, 157) + '...' : oneLine;
}

function walk(dir) {
  const out = [];
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    if (statSync(full).isDirectory()) {
      out.push(...walk(full));
    } else if (/\.mdx?$/.test(entry) && entry !== '_category_.json') {
      out.push(full);
    }
  }
  return out;
}

function pageUrl(docsDir, filePath, frontmatterSlug) {
  if (frontmatterSlug) return frontmatterSlug === '/' ? '/' : `/${frontmatterSlug.replace(/^\//, '')}`;
  const rel = relative(docsDir, filePath);
  const segments = rel.split(sep);
  const fileName = segments.pop().replace(/\.mdx?$/, '');
  const dirSegments = segments.map(stripNumPrefix);
  if (fileName === 'index') {
    return '/' + dirSegments.join('/');
  }
  return '/' + [...dirSegments, stripNumPrefix(fileName)].join('/');
}

const SECTION_LABELS = {
  demarrage: 'Démarrage',
  guides: 'Guides',
  reference: 'Référence',
  migration: 'Migration',
};

/** @returns {import('@docusaurus/types').Plugin} */
export default function llmsTxtPlugin(context) {
  return {
    name: 'llms-txt',
    async postBuild({ outDir, siteConfig }) {
      const docsDir = join(context.siteDir, 'docs');
      const files = walk(docsDir).sort();

      const bySection = {};
      const fullParts = [];

      for (const file of files) {
        const raw = readFileSync(file, 'utf8');
        const { data, body } = parseFrontmatter(raw);
        const rel = relative(docsDir, file);
        const topSection = rel.split(sep)[0];
        const url = pageUrl(docsDir, file, data.slug);
        const absUrl = `${siteConfig.url}${url === '/' ? '' : url}`;
        const title = data.title || rel;
        const description = firstParagraph(body);

        (bySection[topSection] ||= []).push({ title, absUrl, description, url });

        fullParts.push(
          `# ${title}\n\nURL: ${absUrl}\n\n${body.trim()}\n`
        );
      }

      const lines = [
        `# ${siteConfig.title}`,
        '',
        `> ${siteConfig.tagline}`,
        '',
        'Documentation complete du framework PHP Devups. Voir aussi /llms-full.txt pour le contenu integral en un seul fichier.',
        '',
      ];

      for (const [section, pages] of Object.entries(bySection)) {
        lines.push(`## ${SECTION_LABELS[section] || section}`, '');
        for (const p of pages) {
          lines.push(`- [${p.title}](${p.absUrl})${p.description ? `: ${p.description}` : ''}`);
        }
        lines.push('');
      }

      writeFileSync(join(outDir, 'llms.txt'), lines.join('\n'));
      writeFileSync(join(outDir, 'llms-full.txt'), fullParts.join('\n---\n\n'));
    },
  };
}
