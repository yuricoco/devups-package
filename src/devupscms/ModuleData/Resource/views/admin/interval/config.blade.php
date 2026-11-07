@extends('admin.layout')
@section('title', 'List')

@section('layout_content')

    <div class="card">
        <div class="card-body">
            {!! Form::select('name',FormManager::Options_Helper('chain', Tree_item::getmainmenu('barem', 1)),  '',
 ['onchange'=>'model.loadData(this)', 'placeholder'=>'--- select BAREM ---', 'class'=>'form-control']) !!}
        </div>
    </div>
    {!! IntervalTable::init(new Interval())
            ->setModel('config')
            ->buildconfigtable()
            ->render() !!}
@endsection

@section('jsimport')
    <script>
        var groupid = null;
        model.loadData = function (el) {
            console.log(el.value)
            groupid = el.value
            ddatatable.searchparam = "&groupid=" + groupid
            ddatatable.init('interval').page(1)
        }
        model.toggleFundGroup = function (el, idfund) {
            console.log(el.checked)
            Drequest.adminApi('interval/toggle-group').data({
                'checked': el.checked,
                'interval_group': {
                    'interval_id': idfund,
                    'barem_id': groupid,
                }
            }).raw((response) => {
                console.log(response)
                model.notify("Mise a jour enregistrer!", "success");

            })
        }
    </script>
@endsection