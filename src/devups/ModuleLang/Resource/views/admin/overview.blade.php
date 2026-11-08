@extends('admin.layout')

@section('layout_content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel">
                <h3><i class="icon icon-tags"></i> {{ t('Langues') }}</h3>
                <div class="panel-body">
                    <p>
                        {{ t("Les traductions vivent dans des fichiers lang/<iso2>.php, pas en base : cet ecran est en lecture seule.") }}
                    </p>

                    <table class="table">
                        <thead>
                        <tr>
                            <th>{{ t('iso_code') }}</th>
                            <th>{{ t('Name') }}</th>
                            <th>{{ t('Active') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($langs as $lang)
                            <tr>
                                <td>{{ $lang->iso_code }}</td>
                                <td>{{ $lang->name }}</td>
                                <td>{{ $lang->active ? t('Oui') : t('Non') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <hr>
                    <p>
                        {{ t("Pour ajouter/retrouver des cles de traduction : php devups lang:scan (complete lang/*.php depuis le code) et php devups lang:missing (liste les cles non traduites par langue).") }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
