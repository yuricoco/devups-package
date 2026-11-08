@extends('admin.layout')
@section('title', 'List')

@section('layout_content')

    <div id="detail-user" data-url="{{User::classpath("services.php?path=")}}" class="row">
        <div class="col-lg-4 col-md-12  stretch-card">
            <div class="card">
                <div class="card-header-tab card-header">
                    <div class="card-header-title">
                        <i class="header-icon lnr-rocket icon-gradient bg-tempting-azure"> </i>
                        {{$user->username}}
                    </div>
                    <div class="btn-actions-pane-right">
                        <div class="nav">

                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <table class="table">
                        <tr>
                            <th>Email</th>
                            <td>:  {{$user->email}}</td>
                        </tr>
                        <tr>
                            <th>Tel</th>
                            <td>:  {{$user->phonenumber}}</td>
                        </tr>
                        <tr>
                            <th>{{ t('Active') }}</th>
                            <td>:  {{ $user->is_activated ? t('Oui') : t('Non') }}</td>
                        </tr>
                        <tr>
                            <th>{{ t('Derniere connexion') }}</th>
                            <td>:  {{ $user->last_login }}</td>
                        </tr>
                    </table>

                </div>

            </div>
        </div>

        <div class="col-lg-6 col-md-12  stretch-card">
            <h3>Test Push notifications</h3>
            {!! Push_subscriptionTable::init(new Push_subscription())
                ->Qb(function($query) use ($user){
                return $query->where('this.user_id', $user->id);
            })
            ->addFilterParam('dfilters', "on")
            ->addFilterParam('user.id:eq', $user->id)
            ->addFilterParam('tablemodel', 'dashboard')
            ->buildindextable()
            ->render()
                !!}
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