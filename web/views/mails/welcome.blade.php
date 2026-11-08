@extends("mails.email")
@section("content")

        <div style="max-width: 600px; margin: auto; border-radius: 8px; padding: 30px;">

            <h1 style="color: #000;">{{ t_lang("Bienvenue sur", $lang ?? __default_lang) }} {{ PROJECT_NAME }}</h1>

            <p>{{ t_lang("Bonjour", $lang ?? __default_lang) }} {{ $user->username }},</p>

            <p>{{ t_lang("Votre compte est maintenant actif. Nous sommes heureux de vous compter parmi nous.", $lang ?? __default_lang) }}</p>

            <a href="{{ __env_front }}" style="display: inline-block; margin-top: 15px; padding: 12px 20px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                {{ t_lang("Acceder a mon compte", $lang ?? __default_lang) }}
            </a>
            <hr>
            <p>{{ t_lang("Une question ? Repondez simplement a ce mail.", $lang ?? __default_lang) }}</p>

            <p style="font-weight: bold;">— {{ t_lang("L'equipe", $lang ?? __default_lang) }} {{ PROJECT_NAME }}</p>

        </div>

@endsection
