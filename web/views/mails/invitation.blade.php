@extends("mails.email")
@section("content")
    <div>
        <h1>{{ t_lang("Bonjour", $lang ?? __default_lang) }} {{ $invitation->name ?? '' }},</h1>
        <p>
            {{ $user->username ?? '' }} {{ t_lang("vous invite a rejoindre", $lang ?? __default_lang) }}
            {{ PROJECT_NAME }}.
        </p>
        @isset($url)
            <p><a href="{{ $url }}">{{ t_lang("Accepter l'invitation", $lang ?? __default_lang) }}</a></p>
        @endisset
    </div>
@endsection
