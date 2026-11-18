<?php

/**
 * LOT 1.2/1.3 (v8.0) : convertit les docblocks Doctrine (annotations,
 * @Entity/@Table/@MappedSuperclass/@Id/@GeneratedValue/@Column/@ManyToOne/
 * @JoinColumn/@UniqueConstraint) en attributs PHP 8 (#[ORM\...]), en
 * preservant le reste du docblock (texte libre, @var, @param) et tout le
 * code autour. Utilisee a la fois pour convertir le framework lui-meme
 * (ETAPE 1.2) et par la commande `php devups upgrade:entities` (ETAPE 1.3,
 * memes regles appliquees a un projet utilisateur). Idempotente : une
 * entite deja en attributs n'a plus aucune annotation reconnue, donc un
 * second passage ne modifie rien.
 *
 * Volontairement limitee aux formes REELLEMENT utilisees dans Devups (voir
 * TODO.md ETAPE 1.0) : @Entity @Table(name=...[, uniqueConstraints={...
 * (une ligne par @UniqueConstraint)...}]), @MappedSuperclass,
 * @Id @GeneratedValue @Column(type="integer"), @Column(...),
 * @Id @ManyToOne(...), @ManyToOne(...), @JoinColumn(...). Une annotation
 * Doctrine non reconnue est laissee telle quelle dans le docblock (jamais
 * supprimee silencieusement) - a convertir a la main si rencontree.
 */
class EntityAttributeConverter
{
    /** Convertit le contenu PHP d'un fichier. Retourne [code converti, nombre d'attributs ajoutes]. */
    public static function convert(string $code): array
    {
        $attributeCount = 0;

        $code = preg_replace_callback(
            '/^([ \t]*)\/\*\*(.*?)\*\//ms',
            function (array $m) use (&$attributeCount) {
                $indent = $m[1];
                $inner = $m[2];
                $lines = array_map(
                    fn($l) => rtrim(preg_replace('/^[ \t]*\*+\s*/', '', $l)),
                    explode("\n", $inner)
                );

                $kept = [];
                $attrs = [];
                $inUc = false;
                $ucTable = null;
                $ucConstraints = [];

                foreach ($lines as $line) {
                    if ($line === '') {
                        continue;
                    }

                    if ($inUc) {
                        if (preg_match('/^@UniqueConstraint\(name="([^"]+)",\s*columns=\{([^}]*)\}\)$/', $line, $mm)) {
                            $columns = [];
                            foreach (preg_split('/\s*,\s*/', trim($mm[2])) as $col) {
                                $columns[] = trim($col, " \t\"");
                            }
                            $ucConstraints[] = ['name' => $mm[1], 'columns' => $columns];
                            continue;
                        }
                        if ($line === '})') {
                            $attrs[] = '#[ORM\\Entity]';
                            $ucParts = [];
                            foreach ($ucConstraints as $uc) {
                                $cols = implode(', ', array_map(fn($c) => '"' . $c . '"', $uc['columns']));
                                $ucParts[] = 'new ORM\\UniqueConstraint(name: "' . $uc['name'] . '", columns: [' . $cols . '])';
                            }
                            $attrs[] = '#[ORM\\Table(name: "' . $ucTable . '", uniqueConstraints: [' . implode(', ', $ucParts) . '])]';
                            $inUc = false;
                            continue;
                        }
                        // ligne inattendue dans un bloc uniqueConstraints : conservee telle quelle (forme non reconnue).
                        $kept[] = $line;
                        continue;
                    }

                    if (preg_match('/^@Entity\s*@Table\(name="([^"]+)",\s*uniqueConstraints=\{$/', $line, $mm)) {
                        $inUc = true;
                        $ucTable = $mm[1];
                        $ucConstraints = [];
                        continue;
                    }

                    if (preg_match('/^@Entity\s*@Table\(name="([^"]+)"\)$/', $line, $mm)) {
                        $attrs[] = '#[ORM\\Entity]';
                        $attrs[] = '#[ORM\\Table(name: "' . $mm[1] . '")]';
                        continue;
                    }

                    if ($line === '@MappedSuperclass') {
                        $attrs[] = '#[ORM\\MappedSuperclass]';
                        continue;
                    }

                    if (preg_match('/^@Id\s*@GeneratedValue\s*@Column\(type="integer"\)$/', $line)) {
                        $attrs[] = '#[ORM\\Id]';
                        $attrs[] = '#[ORM\\GeneratedValue]';
                        $attrs[] = '#[ORM\\Column(type: "integer")]';
                        continue;
                    }

                    if (preg_match('/^@Id\s*@ManyToOne\(targetEntity="([^"]+)"\)$/', $line, $mm)) {
                        $attrs[] = '#[ORM\\Id]';
                        $attrs[] = '#[ORM\\ManyToOne(targetEntity: "' . $mm[1] . '")]';
                        continue;
                    }

                    if (preg_match('/^@ManyToOne\(targetEntity="([^"]+)"\)$/', $line, $mm)) {
                        $attrs[] = '#[ORM\\ManyToOne(targetEntity: "' . $mm[1] . '")]';
                        continue;
                    }

                    if (preg_match('/^@JoinColumn\((.*)\)$/', $line, $mm)) {
                        $attrs[] = '#[ORM\\JoinColumn(' . self::convertArgs($mm[1]) . ')]';
                        continue;
                    }

                    if (preg_match('/^@Column\((.*)\)$/', $line, $mm)) {
                        $attrs[] = '#[ORM\\Column(' . self::convertArgs($mm[1]) . ')]';
                        continue;
                    }

                    // ligne non reconnue (texte libre, @var, @param, autre annotation) : conservee.
                    $kept[] = $line;
                }

                if (!$attrs) {
                    // aucune annotation Doctrine reconnue dans ce docblock : inchange.
                    return $indent . '/**' . $inner . '*/';
                }

                $attributeCount += count($attrs);

                $out = '';
                if ($kept) {
                    $out .= $indent . "/**\n";
                    foreach ($kept as $k) {
                        $out .= $indent . ' * ' . $k . "\n";
                    }
                    $out .= $indent . ' */' . "\n";
                }
                $attrLines = [];
                foreach ($attrs as $i => $a) {
                    $attrLines[] = ($i === 0 && !$kept ? '' : $indent) . $a;
                }
                $out .= implode("\n", $attrLines);

                return $out;
            },
            $code
        );

        return [$code, $attributeCount];
    }

    /** `name="x", type="y", length=55, nullable=true, options={"default": "1"}` -> `name: "x", type: "y", length: 55, nullable: true, options: ["default" => "1"]` */
    private static function convertArgs(string $args): string
    {
        $out = [];
        preg_match_all(
            '/(\w+)\s*=\s*("(?:[^"\\\\]|\\\\.)*"|\{[^}]*\}|true|false|\d+)/',
            $args,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $m) {
            $key = $m[1];
            $value = $m[2];
            if ($value !== '' && $value[0] === '{') {
                $value = preg_replace('/\{\s*"([^"]+)"\s*:\s*("(?:[^"\\\\]|\\\\.)*")\s*\}/', '["$1" => $2]', $value);
            }
            $out[] = $key . ': ' . $value;
        }
        return implode(', ', $out);
    }

    /** Ajoute `use Doctrine\ORM\Mapping as ORM;` (si absent) apres le dernier `use` existant, ou apres <?php [namespace]. */
    public static function ensureOrmImport(string $code): string
    {
        if (str_contains($code, 'Doctrine\\ORM\\Mapping as ORM')) {
            return $code;
        }

        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $code, $m, PREG_OFFSET_CAPTURE)) {
            $last = end($m[0]);
            $insertAt = $last[1] + strlen($last[0]);
            return substr_replace($code, "\nuse Doctrine\\ORM\\Mapping as ORM;", $insertAt, 0);
        }

        // pas de `use` existant : insere juste apres l'ouverture <?php (et la ligne de namespace, le cas echeant).
        if (preg_match('/^<\?php\s*\n(\s*\n)?(namespace\s+[^;]+;\s*\n)?/', $code, $m)) {
            $insertAt = strlen($m[0]);
            return substr_replace($code, "use Doctrine\\ORM\\Mapping as ORM;\n\n", $insertAt, 0);
        }

        return "use Doctrine\\ORM\\Mapping as ORM;\n" . $code;
    }

    /** Convertit un fichier sur disque. Retourne true si le fichier a ete modifie (ou le serait, en dry-run). */
    public static function convertFile(string $path, bool $dryRun = false): bool
    {
        $original = file_get_contents($path);
        [$converted, $count] = self::convert($original);
        if ($count === 0) {
            return false;
        }
        $converted = self::ensureOrmImport($converted);
        if ($converted === $original) {
            return false;
        }
        if (!$dryRun) {
            file_put_contents($path, $converted);
        }
        return true;
    }
}
