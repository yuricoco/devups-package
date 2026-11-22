// MDX (utilise par Docusaurus 3) interprete `{`/`}` en dehors d'un bloc de
// code comme le debut d'une expression JS. Le contenu source (ecrit pour du
// Markdown/Pandoc classique, pas pour MDX) contient par endroits des
// accolades litterales en prose (ex. signatures d'annotations Doctrine
// `@Table(uniqueConstraints={...})`) qui font planter la compilation. On les
// echappe (`\{` `\}`, syntaxe MDX valide) partout SAUF a l'interieur d'un
// bloc de code (fence ``` ou inline `...`), que l'on protege d'abord.
export function escapeStrayBraces(text) {
  const protectedSpans = [];
  const placeholder = (s) => {
    protectedSpans.push(s);
    return `\u0000${protectedSpans.length - 1}\u0000`;
  };

  // 1) protege les blocs de code fences ``` ... ```
  let out = text.replace(/```[\s\S]*?```/g, placeholder);
  // 2) protege les spans de code inline bien formes (une seule ligne)
  out = out.replace(/`[^`\n]+`/g, placeholder);
  // 3) echappe les accolades restantes (prose)
  out = out.replace(/\{/g, '\\{').replace(/\}/g, '\\}');
  // 4) restaure les blocs proteges, dans l'ordre inverse d'imbrication
  out = out.replace(/\u0000(\d+)\u0000/g, (_, i) => protectedSpans[Number(i)]);
  return out;
}
