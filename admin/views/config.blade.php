@extends('layout.layout')
@section('title', t('Configuration'))


@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-config icon-gradient bg-mean-fruit"></i>
                </div>
                <div>{{ t('Configuration') }}
                    <div class="page-title-subheading">
                        {{ t('Listes de reference (arbres) de l\'application') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse(Tree::allrows() as $tree)
            <div class="col-lg-6">
                <div class="card mt-3">
                    <div class="card-title"><h3>{{ $tree->name }}</h3></div>

                    {!! Tree_itemTable::init(new Tree_item())
                        ->Qb(Tree_item::where("tree.name", $tree->name))
                        ->addFilterParam('dfilters', "on")
                        ->addFilterParam('tree_id:eq', $tree->getId())
                        ->addFilterParam('tablemodel', 'dashboard')
                        ->setModel('dashboard')
                        ->builddashboardtable()->render() !!}
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card mt-3 p-3">{{ t('Aucun arbre pour le moment. Creez-en un depuis le module Tree.') }}</div>
            </div>
        @endforelse
    </div>

@endsection
