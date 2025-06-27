@extends('admin.layout')
@section('title', 'List')

@section('layout_content')
    <div hidden class="row">
        @foreach($moduledata->dvups_entity as $entity)
            @ include("default.entitywidget")
        @endforeach
    </div>

    <div class="row">
        <div class="col-12">

            <!-- Content -->
            <section id="content"
                     xmlns:v-bind="http://www.w3.org/1999/xhtml"
                     xmlns:v-on="http://www.w3.org/1999/xhtml"
                    data-url="{!! $basecontenturl !!}"
                     class="content">
                <!-- Column Center -->
                <div class="row">
                    <div class="col-lg-12">

                        <div class="chute chute-center">
                            <!-- AllCP Info -->
                            <div class="allcp-panels fade-onload">
                                <div class="panel" id="spy3">
                                    <div class="panel-heading">
                                        <div class="topbar-left">
                                            <ol class="breadcrumb">
                                                <li v-if="role =='admin'"  class="breadcrumb-link">

                                                    <div class="input-group mb-3">
                                                        <input v-model="treeedit.name" type="text" class="form-control" placeholder="Nouvelle racine" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                                        <div class="input-group-append">
                                                            <span @click="update(treeedit)" class="btn btn-primary input-group-text" >cree</span>
                                                        </div>
                                                    </div>

                                                </li>
                                                <li class="breadcrumb-link"> | Select a tree</li>
                                                <li class="breadcrumb-link">
                                                    <select class="form-control" v-model="treeselected" @change="init(treeselected, $index)">
                                                        <option > -- select -- </option>
                                                        <option  v-for="(cat, $index) in trees" :value="cat">@{{ cat.name }}</option>
                                                    </select>
                                                </li>
                                                <li class="breadcrumb-link"> | </li>
                                                <li v-if="treeselected.id" class="breadcrumb-link">

                                                    <div class="input-group ">
                                                        <input v-model="treeselected.name" type="text" class="form-control" placeholder="Recipient's username" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                                        <div class="input-group-append">
                                                            <span v-if="role =='admin'" @click="_delete(treeselected.id, $index)" class="input-group-text btn-danger" >
                                                                <i class="fa fa-times"></i>
                                                            </span>
                                                            <span v-if="role =='admin'" @click="update(treeselected)" class="input-group-text" >
                                                                <i class="fa fa-edit"></i></span>
                                                                    <span @click="init(treeselected, $index)" class="input-group-text" >
                                                                <i class="fa fa-eye"></i></span>
                                                            <a :href="'{{Tree_item::classpath('tree-item/index?dfilters=on&tree.name:eq=')}}'+treeselected.name" class="input-group-text" >
                                                                <i class="fa fa-list-alt"></i></a>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="breadcrumb-link">
                                                    <button @click="fillData()" class="btn btn-warning">fill database</button>
                                                </li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-12">

                        <tree_item :key="treeselected.id" v-if="treeselected.id" :langs="langs" :tree="treeselected"></tree_item>

                    </div>
                </div>
                <div id="deletebox" class="swal2-container swal2-fade swal2-shown" style="display:none; overflow-y: auto;">
                    <div role="dialog" aria-labelledby="swal2-title" aria-describedby="swal2-content"
                         class="swal2-modal swal2-show dv_modal" tabindex="1" style="display: inline-flex;">
                        <div style=" width: 100%" class="box-container">
                            <div id="" class="modal-content">
                                <div class="modal-header">
                                    <button onclick="model._dismissmodal()" type="button" class="close" data-dismiss="modal"
                                            aria-label="Close">
                                        <span aria-hidden="true">×</span></button>
                                    <h4 class="modal-title" id="myModalLabel"></h4>
                                </div>
                                <div class="modal-body">

                                    <p>Voulez-vous Supprimer? Ceci supprimera aussi toutes les catégories enfants!</p>
                                    <button @click="confirmdelete('all')" type="button" class="btn btn-danger">
                                        Supprimer Avec les catégories enfants
                                    </button>
                                    <button @click="confirmdelete('no')" type="button" class="btn btn-info">
                                        Les categories enfant monterons d'une hiérarchie
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Column Center -->
            </section>
            <!-- /Content -->

        </div>
    </div>

@endsection 

@section("jsimport")

    {!! Form::addjs(__admin.'plugins/tinymce/tinymce.bundle') !!}
    <script>
        var createcontenturl = ' $addcontenturl !!}';

    </script>
@endsection