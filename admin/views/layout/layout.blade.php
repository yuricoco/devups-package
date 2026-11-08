<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <!-- iso-8859-1  -->
    <meta name="author" content="spacekola">
    <?php
    // le <title> etait fixe en dur pour les 29
    // modules et tous leurs ecrans - deux onglets ouverts etaient
    // indiscernables. @yield('title')/@section('title') ne suffit pas ici :
    // Chaque module a sa propre copie de admin/layout.blade.php avec un
    // @section('title') generique : le titre est donc calcule ici depuis
    // $moduledata, global peuple pour chaque rendu (Controller::views()/
    // viewsModule()).
    // Le nom d'entite n'a pas d'equivalent global : repli par lecture de
    // l'URL (admin/<projet>/<Module>/<entite>/<chemin>, le segment 4),
    // absent pour un tableau de bord de module (4 segments, pas 5).
    global $moduledata;
    $dv_title_bits = [];
    $dv_uri_path = parse_url(\Router::$uri ?? ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $dv_uri_segments = array_values(array_filter(explode('/', trim((string) $dv_uri_path, '/'))));
    if (isset($dv_uri_segments[3]) && !in_array(strtolower($dv_uri_segments[3]), ['dashboard', 'layout'], true)) {
        $dv_title_bits[] = ucfirst($dv_uri_segments[3]);
    }
    if (isset($moduledata) && is_array($moduledata)) {
        $dv_title_bits[] = $moduledata['label'] ?? ($moduledata['name'] ?? null);
    }
    $dv_title_bits = array_filter($dv_title_bits);
    $dv_title = $dv_title_bits ? implode(' · ', $dv_title_bits) : 'Dashboard';
    $dv_title .= ' | ' . PROJECT_NAME;
    ?>
    <title><?= htmlspecialchars($dv_title) ?></title>
    <meta charset="utf-8">
    <link href="<?= __admin; ?>images/favicon.png" rel="icon">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content="<?= PROJECT_NAME ?> — back-office d'administration">
    <meta name="msapplication-tap-highlight" content="no">
    <!-- inject:css -->
    <link rel="stylesheet" href="<?= __env; ?>admin/main.css">
    <link rel="stylesheet" href="<?= assets ?>plugins/sweetalert2/sweetalert2.min.css">

    <link rel="stylesheet" href="<?= assets; ?>css/dv_theme.css">
    <link rel="stylesheet" href="<?= assets; ?>css/dv_style.css">

    @yield('cssimport')

    <?php
    if (function_exists("style"))
        style();
    ?>

    <script>
        var __env = '<?= __env ?>';
        var __lang = '<?= __lang ?>';
        var langs = <?php Genesis::json_encode2(Dvups_lang::all()) ?>;

        var _admin_id = '{{getadmin()->id}}';
        var _role = '{{getadmin()->dvups_role->name}}';
    </script>
</head>

<body>

<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    @include("layout.navbartop")

    <div class="app-main">

        @if(\dclass\devups\Controller\Controller::$sidebar)
            @include("layout.sidebar", ['admin' => getadmin()])
            {{--@ include("layout.navbar", ['admin' => getadmin()])--}}
        @endif

        <div class="{{\dclass\devups\Controller\Controller::$sidebar ? 'app-main__outer' : ""}}">
            <div id="dv_main_container" class="app-main__inner">
                @yield('content')
            </div>
            <div class="app-wrapper-footer">
                <div class="app-footer">
                    <div class="app-footer__inner">
                        <div class="app-footer-left">
                            <ul class="nav">
                                <li class="nav-item">
                                    Copyright © {{date("Y")}} {{PROJECT_NAME}} All rights reserved.
                                </li>
                            </ul>
                        </div>
                        <div class="app-footer-right">
                            <ul class="nav">
                                <li class="nav-item">
                                    {{t('Developed by ')}}
                                </li>
                                <li class="nav-item ml-3">
                                    <a href="https://spacekola.com/" target="_blank" >
                                         Spacekola
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="dialog-container" class="swal2-container swal2-fade swal2-shown" style="display:none; overflow-y: auto !important;">
    <div role="dialog" aria-labelledby="swal2-title" aria-describedby="swal2-content" class="swal2-modal swal2-show dv_modal box-container" tabindex="1"
         style="margin:auto">
        <div class="main-card mb-3 card " style="width: 100%" >
            <div class="card-header">
                <h3 class="card-title">. </h3>
                <div class="card-toolbar text-right">
                    <button type="button" onclick="model._dismissmodal()"
                            class="btn btn-danger "
                            aria-label="Close this dialog"
                            style="display: block;">×
                    </button>
                </div>
            </div>
            <div class="card-body">
            </div>
        </div>
    </div>
</div>


<script src="<?= assets; ?>scripts/main.js"></script>
<!-- End custom js for this page-->
<script src="<?= assets; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?= assets; ?>plugins/notify.min.js"></script>
<script src="<?= CLASSJS; ?>Request.js"></script>
<script src="<?= CLASSJS; ?>devups.js"></script>
<script src="<?= CLASSJS; ?>dialogbox.js"></script>
<script >
    // devups.timerNotification(_admin_id, '{{date("Y-m-d H:i:s")}}')
</script>

<?php
if (function_exists("script"))
    script();
?>
@yield('jsimport')

</body>

</html>


