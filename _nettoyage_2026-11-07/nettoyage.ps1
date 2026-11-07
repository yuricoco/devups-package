# Nettoyage du depot Devups (2026-11-07)
#
# Deplace (sans rien supprimer) les fichiers propres aux anciens projets vers
#   ..\backendv4_retire_2026-11-07\
# en conservant l'arborescence. Une fois verifie, supprimer ce dossier a la main.
#
# Usage (PowerShell, depuis n'importe ou) :
#   powershell -ExecutionPolicy Bypass -File C:\spacekola\devupsdev\backendv4\_nettoyage_2026-11-07\nettoyage.ps1

$ErrorActionPreference = "Stop"
$root   = Split-Path -Parent $PSScriptRoot
$target = Join-Path (Split-Path -Parent $root) "backendv4_retire_2026-11-07"

# 1. Fichiers et dossiers propres aux anciens projets (code, notes, journaux)
$items = @(
    "BACKOFFICE-NOTES.md",
    "DEVUPS_GUIDE.md",
    "DEVUPS_TCHUTTE_ORM_DOC.md",
    "HARMONISATION-NOTES.md",
    "MIGRATION-GENRES-NOTES.md",
    "PUSH-NOTES.md",
    "STICKERS-NOTES.md",
    "admin\assets\images\filigramme.png",
    "admin\assets\images\logomini.jpg",
    "admin\assets\js\post\post-image-manager.js",
    "admin\assets\js\post\post-manager.js",
    "admin\views\_partial\_kola.blade.php",
    "admin\views\_partial\_kola_compact.blade.php",
    "admin\views\_partial\commentview.blade.php",
    "admin\views\_partial\post-manager.blade.php",
    "admin\views\_partial\post_item.blade.php",
    "admin\views\_partial\post_item_chapter.blade.php",
    "admin\views\_partial\post_item_comment.blade.php",
    "admin\views\_partial\post_single_item_comment.blade.php",
    "admin\views\_partial\postcomment.blade.php",
    "admin\views\_partial\usermodel.blade.php",
    "config\constante.prod.php",
    "dclass\devups\System_prod.php",
    "dclass\devups\UploadFile.php",
    "dclass\extends\CurrencyTrait.php",
    "dclass\extends\Item.php",
    "dclass\extends\OrderCore.php",
    "dclass\extends\RoleGuardTrait.php",
    "dclass\extends\ScanCore.php",
    "monebilreques.log",
    "share.php",
    "src\devups\ModuleAdmin\error_log",
    "src\devups\ModuleConfig\index.php",
    "src\devupscms\ModuleLocation\Ressource",
    "src\devupscms\ModuleLocation\marketplace.modulelocation.php",
    "src\devupscms\ModuleNotification\error_log",
    "src\devupscms\ModuleReporting\Resource\views\admin\reportingmodel\form_bu.blade.php",
    "src\devupscms\ModuleReporting\error_log",
    "tests\ComicbookTest.php",
    "web\views\mails\alert_paiement.blade.php",
    "web\views\mails\payoutrequest.blade.php",
    "web\views\mails\payoutvalidation.blade.php"
)

# 2. Donnees d'execution des anciens projets (mails de test, sauvegarde SQL,
#    journaux de transactions, migrations applicatives). La sauvegarde SQL
#    contient des donnees personnelles : la conserver hors du depot.
$globs = @(
    "cache\*.html",
    "database\backup",
    "database\dump",
    "database\log",
    "database\transaction",
    "database\migration\*.sql"
)

$toMove = @()
foreach ($rel in $items) {
    $p = Join-Path $root $rel
    if (Test-Path -LiteralPath $p) { $toMove += $rel }
}
foreach ($g in $globs) {
    foreach ($f in Get-ChildItem -Path (Join-Path $root $g) -Force -ErrorAction SilentlyContinue) {
        $toMove += $f.FullName.Substring($root.Length + 1)
    }
}

if ($toMove.Count -eq 0) {
    Write-Host "Rien a deplacer : le depot est deja propre."
} else {
    Write-Host ("{0} element(s) seront deplaces vers {1} :" -f $toMove.Count, $target)
    $toMove | ForEach-Object { Write-Host "  - $_" }
    $answer = Read-Host "Continuer ? (o/N)"
    if ($answer -ne "o" -and $answer -ne "O") { Write-Host "Annule."; exit 0 }

    foreach ($rel in $toMove) {
        $src = Join-Path $root $rel
        $dst = Join-Path $target $rel
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dst) | Out-Null
        Move-Item -LiteralPath $src -Destination $dst -Force
    }
    Write-Host "Deplacement termine."
}

# 3. Ce dossier de nettoyage (script + sauvegarde des fichiers modifies) rejoint
#    lui aussi le dossier retire.
$self = $PSScriptRoot
$selfDst = Join-Path $target (Split-Path -Leaf $self)
New-Item -ItemType Directory -Force -Path $target | Out-Null
Move-Item -LiteralPath $self -Destination $selfDst -Force
Write-Host "Sauvegarde et script ranges dans $selfDst"
Write-Host ""
Write-Host "Etapes suivantes dans $root :"
Write-Host "  composer update          (installe PHPUnit et regenere l'autoload)"
Write-Host "  composer test            (DB_USER / DB_PASSWORD si besoin)"
