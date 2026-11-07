@extends("mails.email")
@section("content")
    <div>
        <h1>Nouvelle demande de retrait</h1>

        <p>
            Utilisateur : <b>{{ $user->username }}</b> ({{ $user->email }})<br>
            Montant : <b>{{ $amount }} {{ $currency }}</b><br>
            Operateur : {{ $payment_method }}<br>
            Numero : {{ $phonenumber }}
        </p>

        <p>Cette demande attend une validation depuis le backoffice.</p>
    </div>
@endsection