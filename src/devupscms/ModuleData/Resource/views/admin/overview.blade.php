@extends('admin.layout')
@section('title', 'List')

@section('layout_content')
    <div class="row">
        <div class="col-lg-5">
            {!! StatusTable::init(new Status())->buildindextable()->render() !!}
        </div>
    </div>
@endsection
