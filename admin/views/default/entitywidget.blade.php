@php
    // Compte des lignes par entite, en UNE requete (UNION ALL) par page et
    // non une par entite : ce widget est inclus une fois par entite depuis
    // overview.blade.php, d'ou la memoisation en variable globale
    // ci-dessous. Si une entite n'a pas de table reelle (whitelist via
    // information_schema, jamais d'interpolation brute d'un nom d'entite),
    // rien n'est affiche plutot qu'un mauvais chiffre.
    global $__entitywidget_counts_cache;
    if (!isset($__entitywidget_counts_cache)) {
        $__entitywidget_counts_cache = [];
    }
    $__widget_module_key = $moduledata['name'] ?? '';
    if ($__widget_module_key !== '' && !array_key_exists($__widget_module_key, $__entitywidget_counts_cache)) {
        $__counts = [];
        if (isset($moduledata['entities']) && is_array($moduledata['entities'])) {
            $__candidates = [];
            foreach ($moduledata['entities'] as $__e) {
                $__candidates[strtolower($__e['name'])] = true;
            }
            $__candidates = array_keys($__candidates);
            if ($__candidates) {
                $pdo = DBAL::pdo();
                $__placeholders = implode(',', array_fill(0, count($__candidates), '?'));
                $__stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ($__placeholders)");
                $__stmt->execute($__candidates);
                $__realTables = $__stmt->fetchAll(PDO::FETCH_COLUMN);

                if ($__realTables) {
                    $__unionParts = [];
                    foreach ($__realTables as $__t) {
                        $__unionParts[] = "SELECT '" . $__t . "' AS t, COUNT(*) AS c FROM `" . $__t . "`";
                    }
                    $__countStmt = $pdo->query(implode(' UNION ALL ', $__unionParts));
                    foreach ($__countStmt->fetchAll(PDO::FETCH_ASSOC) as $__row) {
                        $__counts[$__row['t']] = (int)$__row['c'];
                    }
                }
            }
        }
        $__entitywidget_counts_cache[$__widget_module_key] = $__counts;
    }
    $entityCount = $__entitywidget_counts_cache[$__widget_module_key][strtolower($entity['name'])] ?? null;
@endphp
<div class="col-md-6 col-xl-3">
    <div class="card mb-3">
        <div class="card-body">
            <div class="card-title">
                {{ $entity['label'] ?? $entity['name'] }}
                @if ($entityCount !== null)
                    <span class="badge badge-pill badge-light">{{ $entityCount }}</span>
                @endif
            </div>
            <a href="{{ __env }}admin/{{ $entity['path'] }}{{ $entity['name'] }}/list">
                <i class="fa fa-external-link-alt"></i> {{t("Details")}}
            </a>
        </div>
    </div>
</div>
