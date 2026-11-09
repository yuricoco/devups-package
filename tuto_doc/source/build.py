#!/usr/bin/env python3
"""Construit les tomes de la documentation Devups (Markdown -> HTML -> PDF)."""
import re
import sys
from pathlib import Path

import markdown
from weasyprint import HTML

ROOT = Path(__file__).parent
SRC = ROOT
OUT = ROOT.parent
OUT.mkdir(exist_ok=True)

TOMES = {
    1: ("Tome I — Le socle et le démarrage", "Devups - Tome 1 - Le socle et le demarrage"),
    2: ("Tome II — Les données", "Devups - Tome 2 - Les donnees"),
    3: ("Tome III — L'interface", "Devups - Tome 3 - L'interface"),
    4: ("Tome IV — Les modules fournis", "Devups - Tome 4 - Les modules fournis"),
}

EDITION = "Deuxième édition — novembre 2026"
VERSION = "Devups 7.1.0"


def slug(text, used):
    s = re.sub(r"<[^>]+>", "", text)
    s = re.sub(r"[^a-z0-9]+", "-", s.lower()).strip("-") or "s"
    base, i = s, 2
    while s in used:
        s = f"{base}-{i}"
        i += 1
    used.add(s)
    return s


def build(n):
    title, filename = TOMES[n]
    md_text = (SRC / f"tome{n}.md").read_text(encoding="utf-8")
    body = markdown.markdown(
        md_text,
        extensions=["tables", "fenced_code", "md_in_html", "attr_list", "sane_lists"],
        output_format="html5",
    )

    # Ancres + sommaire (h1 = chapitre, h2 = section)
    used, toc = set(), []

    def add_id(m):
        level, inner = m.group(1), m.group(2)
        sid = slug(inner, used)
        if level in ("1", "2"):
            toc.append((int(level), inner, sid))
        cls = ' class="chapter"' if level == "1" else ""
        return f'<h{level} id="{sid}"{cls}>{inner}</h{level}>'

    body = re.sub(r"<h([1-3])>(.*?)</h\1>", add_id, body)

    toc_html = ['<nav class="toc"><h1 class="toc-title">Sommaire</h1><ul>']
    for level, text, sid in toc:
        plain = re.sub(r"<(?!/?code)[^>]+>", "", text)
        toc_html.append(f'<li class="l{level}"><a href="#{sid}">{plain}</a></li>')
    toc_html.append("</ul></nav>")

    css = (ROOT / "style.css").read_text(encoding="utf-8")
    html = f"""<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>Devups — {title}</title>
<style>{css}</style></head><body>
<section class="cover">
  <div class="band">
    <div class="brand">Devups</div>
    <div class="rule"></div>
    <div class="subtitle">{title}</div>
  </div>
  <div class="meta">
    <p><strong>Documentation du framework</strong></p>
    <p>{EDITION} · {VERSION}</p>
    <p>Établie à partir du code du dépôt <strong>spacekola/devups</strong>
    (github.com/yuricoco/devups-package), version 7.0.0, et de sa suite de tests.</p>
  </div>
  <div class="foot">Devups — framework PHP de Spacekola · Documentation destinée à être itérée</div>
</section>
{''.join(toc_html)}
<main>{body}</main>
</body></html>"""
    (OUT / f"tome{n}.html").write_text(html, encoding="utf-8")
    pdf = OUT / f"{filename}.pdf"
    HTML(string=html, base_url=str(ROOT)).write_pdf(str(pdf))
    print("ok", pdf)


if __name__ == "__main__":
    for n in (map(int, sys.argv[1:]) if len(sys.argv) > 1 else TOMES):
        build(n)
