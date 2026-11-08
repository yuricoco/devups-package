@extends('admin.layout')
@section('title', 'List')


@section('cssimport')

    {!! Form::addcss(__admin.'plugins/jquery-ui-1.12.1/jquery-ui.min') !!}
    <style>
        .dv-block {
            position: relative;
            padding: 15px;
            margin-bottom: 15px;
        }

        .dv-block:hover {
            border: 1px solid #5b5b5b;
        }

        .dv-block-action {
            display: none;
        }

        .dv-block:hover .dv-block-action {
            display: block;
            position: absolute;
            bottom: 100%;
            right: 0;
            z-index: 10;
        }
        #image-form{
            position: sticky;
            width: 500px;
            bottom: 100px;
            left: 30%;
            z-index: 10;
        }
    </style>

@endsection

@section('content')

    <div class="row">
        <div class="col-lg-9 col-md-12  stretch-card">
            <div class="card">
                <div class="card-header-tab card-header">
                    <div class="">
                        <div class="d-sm-flex justify-content-between align-items-start">
<div class="row">
                            <div class="col-lg-12 col-md-12">
                                {{$cmscontent->reference}}
                            </div>
                            <div class="col-lg-4 col-md-12 text-right">
                                <select onchange="cmscontent.changelang(this)">
                                    @foreach($langs as $lang)
                                        <option value="{{$lang->iso_code}}">{{$lang->iso_code}}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary " onclick="cmscontent.save(this)">save</button>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <div style="position: relative" class="card-body">
                    @foreach($langs as $lang)
                        <h3 id="dv-title-{{$lang->iso_code}}" class="dv-title"> {{ is_array($cmscontent->title) ? ($cmscontent->title[$lang->iso_code] ?? '') : $cmscontent->title }}</h3>
                        <div data-lang="{{$lang->iso_code}}" id="dv-editable-{{$lang->iso_code}}"
                             class="dv-editable lang-{{$lang->iso_code}}">
                            {!! is_array($cmscontent->content) ? ($cmscontent->content[$lang->iso_code] ?? '') : $cmscontent->content !!}
                        </div>
                    @endforeach
                    <table>
                        <tr>
                            <td>
                                <button onclick="cmscontent.addblock()">new block</button>
                            </td>
                            <td>
                                <input accept=".jpg, .png, .jpeg" hidden onchange="cmscontent.setimage(this)" type="file" id="image">
                                <button onclick="document.getElementById('image').click()">add image</button>
                            </td>
                        </tr>
                    </table>

                        <div id="image-form" class="card">
                            <div class="card-header">.

                                <button onclick="cmscontent._dismissimage()" type="button" class="swal2-close"
                                        aria-label="Close this dialog" style="display: block;">×
                                </button>
                            </div>
                            <div class="card-body">
                                <button onclick="cmscontent.setimage()">Update</button>
                                <hr>
                                <input type="text" id="image-link">
                                <input type="text" placeholder="width auto max 100%" id="image-width">
                                <input type="text" placeholder="height auto" id="image-height">
                            </div>
                        </div>

                </div>
            </div>
        </div>

        <div class="col-lg-3">
            {!! CmsresourceTable::init(new Cmsresource())->buildindextable()->render() !!}
        </div>
    </div>
    <div id="commonbox" class="swal2-container swal2-fade swal2-shown" style="display:block; overflow-y: auto;">
        <div role="dialog" aria-labelledby="swal2-title" aria-describedby="swal2-content"
             class="swal2-modal swal2-show dv_modal" tabindex="1"
             style="">
            <div class="main-card mb-3 card  box-container">
                <div class="card-header">.

                    <button onclick="cmscontent._dismissmodal()" type="button" class="swal2-close"
                            aria-label="Close this dialog" style="display: block;">×
                    </button>
                </div>
                <div class="card-body">
                    <button onclick="cmscontent.setcontent()">Update</button>
                    <hr>
                    <textarea id="editor"></textarea>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('jsimport')

    <script>
        var cmscontentid = {{$cmscontent->id}};
        var action_api = "{{ $action }}";
    </script>
    <?= Form::addDformjs() ?>
    {!! Form::addjs(__admin.'plugins/tinymce/tinymce.bundle') !!}
    {!! Form::addjs(__admin.'plugins/jquery-ui-1.12.1/jquery-ui.min') !!}
    <?= Form::addjs(Cmscontent::classpath('Resource/js/cmscontentForm')) ?>

@endsection
