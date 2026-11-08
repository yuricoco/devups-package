@extends("mails.email")
@section("content")

    <div style="color:#1f1f1f;line-height:1.25em;max-width:400px;margin:0 auto"
         align="center">
        <h3>
            {{ t_lang("Reinitialisation du mot de passe", $lang ?? __default_lang) }}
        </h3>
        <p style="font-size:0.9em">
            {{ t_lang("Vous avez demande la reinitialisation de votre mot de passe. Cliquez sur le lien ci-dessous pour en choisir un nouveau.", $lang ?? __default_lang) }}
        </p>
        <p style="margin:26px 0">
            <a href="{{ $reset_link }}" style="background-color:#303030;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block">
                {{ t_lang("reset password", $lang ?? __default_lang) }}
            </a>
        </p>
        <p style="font-size:0.75em">
            {{ t_lang("Si vous n'etes pas a l'origine de cette demande, ignorez ce message : votre mot de passe actuel reste valable.", $lang ?? __default_lang) }}
            {{ t_lang("Ce lien expire rapidement.", $lang ?? __default_lang) }}
        </p>
    </div>

@endsection
