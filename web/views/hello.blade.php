<!DOCTYPE html>

<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ PROJECT_NAME }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    <!-- Styles -->

    <?php foreach (App::$cssfiles as $cssfile){ ?>
    <link href="<?= $cssfile ?>" rel="stylesheet">
    <?php } ?>

    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Raleway', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">

    <div class="top-right links">
        <a href="{{ __env }}admin">Go to the backend</a>
    </div>

    <div class="content">
        <div class="title m-b-md">
            @tt("Hello Devups. Waitting for your front-end template. Do your best ") :)
        </div>

        <div class="links">
            <a href="https://devups.spacekola.com">Documentation</a>
            <a href="https://github.com/yuricoco/devups-package">Contribut</a>
            <a href="#">News</a>
            <a href="{{route("contact")}}">Contact</a>
        </div>
    </div>
</div>

<?php foreach (App::$jsfiles as $cssfile){ ?>
<script src="<?= $cssfile ?>"></script>
<?php } ?>
</body>
</html>
