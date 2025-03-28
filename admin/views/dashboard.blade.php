@extends('layout.layout')
@section('title', 'Page Title')


@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-car icon-gradient bg-mean-fruit">
                    </i>
                </div>
                <div>Analytics Dashboard
                    <div class="page-title-subheading">This is an example dashboard created using build-in elements and
                        components.
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <a href="{{route('admin/config')}}" type="button" data-toggle="tooltip" title="Example Tooltip" data-placement="bottom"
                        class="btn-shadow mr-3 btn btn-dark">
                    <i class="fa fa-star"></i>
                </a>
            </div>
        </div>
    </div>


@endsection

@section("jsimport")

    <script src="<?= CLASSJS ?>devups.js"></script>
    <script src="<?= CLASSJS ?>model.js"></script>
    <script src="<?= CLASSJS ?>ddatatable.js"></script>
    <?php foreach (dclass\devups\Controller\Controller::$jsfiles as $jsfile){ ?>
    <script src="<?= $jsfile ?>"></script>
    <?php } ?>

@endsection