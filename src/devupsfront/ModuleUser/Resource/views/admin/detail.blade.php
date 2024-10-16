@extends('admin.layout')
@section('title', 'List')

@section('layout_content')

    <div id="detail-user" data-url="{{User::classpath("services.php?path=")}}" class="row">
        <div class="col-lg-4 col-md-12  stretch-card">
            <div class="card">
                <div class="card-header-tab card-header">
                    <div class="card-header-title">
                        <i class="header-icon lnr-rocket icon-gradient bg-tempting-azure"> </i>
                        {{$user->firstname}}
                    </div>
                    <div class="btn-actions-pane-right">
                        <div class="nav">

                        </div>
                    </div>
                </div>
                <div class="card-body">

                </div>

            </div>
        </div>

        <div class="col-lg-6 col-md-12  stretch-card">
            <h3>Diponibility</h3>
            {!! DisponibilityTable::init(new Disponibility())->buildusertable()
->setModel("user")
->addFilterParam($user)
->Qb(Disponibility::where($user))
->render() !!}

            <h3>Service_price</h3>
            {!! Service_priceTable::init(new Service_price())->buildusertable()
->setModel("user")
->addFilterParam($user)
->Qb(Service_price::where($user))
->render() !!}
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 col-md-12  stretch-card">
            <h3>Schedule</h3>
            {!! ScheduleTable::init(new Schedule())->buildusertable( )
->setModel("user")
->addFilterParam("provider_id",$user->id)
->render() !!}

        </div>
    </div>

@endsection

@section("jsimport")
    <script>
        model.sendmail = function (el, idnotif, iduser) {
            model.addLoader($(el))
            Drequest.init(__env + "admin/api/user.sendnotification?idnotif=" + idnotif + "&userid=" + iduser)
                .get((response) => {
                    model.removeLoader()
                    alert("Relance envoyer avec succes")
                    console.log(response)
                })
        }
    </script>
@endsection