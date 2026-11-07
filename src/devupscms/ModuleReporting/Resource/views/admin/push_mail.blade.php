@extends('admin.layout')
@section('title', 'List')

@section('layout_content')
    <div class="row">
        <div class="col-lg-7">
            <h3>Push notifications </h3>
            {!! Push_emailTable::init(new Push_email())->buildindextable()
->render() !!}
        </div>
        <div class="col-lg-5">

            <h4 class="modal-title">{{t("Contrainte")}}</h4>
            {!! NotificationtypeTable::init(new Notificationtype())->buildpushmailtable()
->Qb(Notificationtype::where("dvups_entity.name", "user"))
->addFilterParam("dvups_entity.name:eq", "user")
->setModel("pushmail")
->render() !!}

        </div>
    </div>
@endsection
