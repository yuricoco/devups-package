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
                <button type="button" data-toggle="tooltip" title="Example Tooltip" data-placement="bottom"
                        class="btn-shadow mr-3 btn btn-dark">
                    <i class="fa fa-star"></i>
                </button>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Genders</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
    ->Qb(Tree_item::where("tree.name", "department"))
    ->addFilterParam('dfilters', "on")
    ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','department')->getId())
    ->addFilterParam('tablemodel', 'dashboard')
    ->setModel('dashboard')
    ->builddashboardtable()->render() !!}

            </div>

        </div>
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Category Employes</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
    ->Qb(Tree_item::where("tree.name", "category_employee"))
    ->addFilterParam('dfilters', "on")
    ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','category_employee')->getId())
    ->addFilterParam('tablemodel', 'dashboard')
    ->setModel('dashboard')
    ->builddashboardtable()->render() !!}

            </div>

        </div>
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Variables</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
    ->Qb(Tree_item::where("tree.name", "fund_type"))
    ->addFilterParam('dfilters', "on")
    ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','fund_type')->getId())
    ->addFilterParam('tablemodel', 'dashboard')
    ->setModel('dashboard')
    ->builddashboardtable()->render() !!}

            </div>

        </div>
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Niveau detude</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
    ->Qb(Tree_item::where("tree.name", "degree"))
    ->addFilterParam('dfilters', "on")
    ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','degree')->getId())
    ->addFilterParam('tablemodel', 'dashboard')
    ->setModel('dashboard')
    ->builddashboardtable()->render() !!}

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Postes</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
        ->Qb(Tree_item::where("tree.name", "post"))
        ->addFilterParam('dfilters', "on")
        ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','post')->getId())
        ->addFilterParam('tablemodel', 'dashboard')
        ->setModel('dashboard')
        ->builddashboardtable()->render() !!}

            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mt-3">
                <div class="card-title"><h3>Contract</h3></div>

                {!! Tree_itemTable::init(new Tree_item())
    ->Qb(Tree_item::where("tree.name", "contract"))
    ->addFilterParam('dfilters', "on")
    ->addFilterParam('tree_id:eq', Tree::getbyattribut('name','contract')->getId())
    ->addFilterParam('tablemodel', 'dashboard')
    ->setModel('dashboard')
    ->builddashboardtable()->render() !!}

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