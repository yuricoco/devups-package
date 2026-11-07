@extends("mails.email")
@section("content")
    <div>
        <h1>{{ langt("Bonjour", "Hello", $lang ?? __default_lang) }} {{ $invitation->name ?? '' }},</h1>
        <p>
            {{ $user->username ?? '' }} {{ langt("vous invite a rejoindre", "invites you to join", $lang ?? __default_lang) }}
            {{ PROJECT_NAME }}.
        </p>
        @isset($url)
            <p><a href="{{ $url }}">{{ langt("Accepter l'invitation", "Accept the invitation", $lang ?? __default_lang) }}</a></p>
        @endisset
    </div>
@endsection
