@extends('layout.layout')
@section('title', 'Page Title')

<?php function style(){ ?>

<?php foreach (dclass\devups\Controller\Controller::$cssfiles as $cssfile){ ?>
<link href="<?= $cssfile ?>" rel="stylesheet">
<?php } ?>

<?php } ?>

@section('content')

    <!--begin::Toolbar-->
    @include("default.toolbar")
    <ul class="nav nav-justified">
        <li class="nav-item">
            <a class="nav-link active"
               href="<?= $moduledata->route() ?>">
                <i class="metismenu-icon"></i> <span>Dashboard</span>
            </a>
        </li>
        @foreach ($moduledata->dvups_entity as $entity)
            <li class="nav-item">
                <a class="nav-link active"
                   href="<?=  $entity->route() ?>">
                    <i class="metismenu-icon"></i> <span><?= $entity->getLabel() ?></span>
                </a>
            </li>
        @endforeach
    </ul>
    <hr>
    <!--end::Toolbar-->
    @yield("content_module")

@endsection

<?php function script(){ ?>

<script src="<?= CLASSJS ?>devups.js"></script>
<script src="<?= CLASSJS ?>model.js"></script>
<script src="<?= CLASSJS ?>ddatatable.js"></script>
<?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
<script src="<?= $jsfile ?>"></script>
<?php } ?>

<?php } ?>

