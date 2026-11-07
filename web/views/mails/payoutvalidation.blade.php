@extends("mails.email")
@section("content")
    <div>
        <h1>Bonjour {{ $user->username }},</h1>

        <p>Votre demande de retrait a ete <b>validee</b>.</p>

        <p>
            Montant : <b>{{ $amount }} {{ $currency }}</b><br>
            Operateur : {{ $payment_method }}<br>
            Numero : {{ $phonenumber }}
        </p>

        <p>Le versement sera effectue sur le numero indique.</p>

        <p>Merci de votre confiance.</p>
    </div>
@endsection