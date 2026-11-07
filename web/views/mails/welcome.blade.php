@extends("mails.email")
@section("content")

        <div style="max-width: 600px; margin: auto; border-radius: 8px; padding: 30px;">

            <h1 style="color: #000;">{{ langt("Bienvenue sur", "Welcome to", $lang) }} {{ PROJECT_NAME }}</h1>

            <p>{{ langt("Bonjour", "Hi", $lang) }} {{ $user->username }},</p>

            <p>{{ langt("Votre compte est maintenant actif. Nous sommes heureux de vous compter parmi nous.",
                        "Your account is now active. We are glad to have you with us.", $lang) }}</p>

            <a href="{{ __env_front }}" style="display: inline-block; margin-top: 15px; padding: 12px 20px; background-color: #1e88e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                {{ langt("Acceder a mon compte", "Go to my account", $lang) }}
            </a>
            <hr>
            <p>{{ langt("Une question ? Repondez simplement a ce mail.", "A question? Just reply to this email.", $lang) }}</p>

            <p style="font-weight: bold;">— {{ langt("L'equipe", "The team", $lang) }} {{ PROJECT_NAME }}</p>

        </div>

@endsection
