@extends("mails.email")
@section("content")
    <div>
        <h1>Votre code d'activation est : {{$activation_code}}</h1>
    </div>
@endsection