<?php
// Récupérer les données dynamiquement depuis le backend

// Vérifier si les données existent
if (!isset($_GET['path'])) {
    header("HTTP/1.0 401 Unauthorized URL");
    exit("Page not found");
}

//$id = $_GET['id'] ?? null; // Récupération de l'ID depuis l'URL
$fullPath = $_GET['path'];
$path = explode("/", $_GET['path']); // Récupération de l'ID depuis l'URL
$apiUrl = "https://backend.3ag-edition.com/api/share?path={$path[0]}&id=".$path[1]; // Appel de l'API
$data = json_decode(file_get_contents($apiUrl), true); // Récupération des données

// Vérifier si les données existent
if (!$data) {
    header("HTTP/1.0 404 Not Found");
    exit("Page not found");
}

// Générer les balises Open Graph avec les données récupérées
$title = htmlspecialchars($data['title']);
$description = htmlspecialchars($data['description']);
$image = htmlspecialchars($data['image']);
$url = "https://3ag-edition.com/share.php?path=".$_GET['path'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta property="og:title" content="<?= $title ?>" />
    <meta property="og:description" content="<?= $description ?>" />
    <meta property="og:image" content="<?= $image ?>" />
    <meta property="og:url" content="<?= $url ?>" />
    <meta property="og:type" content="website" />
    <meta http-equiv="refresh" content="0;url=https://reader.3ag-edition.com/<?= $fullPath ?>" />
</head>
<body>
<p>Redirection vers <a href="https://reader.3ag-edition.com/<?= $fullPath ?>">le livre</a>...</p>
</body>
</html>
