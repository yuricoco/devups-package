@extends('layout.layout')
@section('title', 'Page Title')

<?php function style(){ ?>

<?php foreach (dclass\devups\Controller\Controller::$cssfiles as $cssfile){ ?>
<link href="<?= $cssfile ?>" rel="stylesheet">
<?php } ?>

<?php } ?>

@section('content')
<style>
    .evo-pop{
        z-index: 10;
    }
    .evo-pointer{
        width: 100%;
        height: 10px;
    }
</style>
    @include("default.moduleheaderwidget")
    <hr>

            <div class='alert alert-info'>Specify entities that handle status in the module dependencies</div>

    @yield('layout_content')


        @endsection

<?php function script(){ ?>

<script src="<?= CLASSJS ?>devups.js"></script>
<script src="<?= CLASSJS ?>model.js"></script>
<script src="<?= CLASSJS ?>ddatatable.js"></script>
<?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
<script src="<?= $jsfile ?>"></script>
<?php } ?>

<?php } ?>

